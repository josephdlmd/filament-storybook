<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\Fulfilment;

class NextDocumentStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Create next document';
    }

    public function description(): string
    {
        return 'Turning a posted document into the next one: a quotation into an order (`createOrder`), an order into deliveries (`default`), a delivery into its invoice (`createInvoice`). The button is the View page\'s primary action, since it is the next step, shown while there is something left to do; it makes a draft of the next document with the lines copied, their prices from the one before, and opens it for review, so nothing is typed twice. An order can be delivered in parts: "Create delivery" opens a slide-over of the order\'s lines with what is left of each, its quantity starting there; lowering it delivers less, and 0 leaves the line out. Each delivery gets one invoice, with the delivery\'s quantities; create it in a transaction with the delivery locked and checked again, so two users never invoice it twice. On the draft, a copied line\'s unit price is read-only and its quantity can only go down, to what is left; read-only values are still submitted, so set the prices again from the source when saving, and refuse a quantity over what is left inside the posting transaction, with the source\'s lines locked (`Fulfilment`). An order shows its delivery status (`deliveryStatus`), and each document links to the one it came from and the ones made from it (`records-related-records`). Basis: the owner\'s business standards (2026-10-12: quotation, order, delivery, invoice; deliveries in parts; one invoice per delivery; quantities down only, prices locked); WCAG 3.3.7 (what was entered isn\'t asked again); Carbon button (Sep 2026: the next step is the primary action); Carbon forms (Aug 2026: a side panel "for repeated requests for user input which require the user needs to reference the affected information").';
    }

    public function examples(): array
    {
        return [Fulfilment::class];
    }

    public function records(): array
    {
        return [
            1 => ['status' => DocumentStatus::Posted, 'number' => 'SO-000012', 'delivery_status' => 'Partly delivered', 'invoice_id' => null, 'lines' => [
                ['id' => 1, 'description' => 'Nitrile examination gloves, medium, box of 100', 'quantity' => '100', 'delivered' => '40', 'unit_price' => '285.50'],
                ['id' => 2, 'description' => 'Insulin pens, 3 mL, pack of 5', 'vat_class' => 'exempt', 'quantity' => '20', 'delivered' => '20', 'unit_price' => '1450.00'],
                ['id' => 3, 'description' => 'Isopropyl alcohol 70%, 500 mL', 'quantity' => '12.5', 'delivered' => '0', 'unit_price' => '89.2857'],
            ]],
            2 => ['delivery_status' => 'Not delivered'],
            3 => ['delivery_status' => 'Delivered'],
        ];
    }

    /** An order into a delivery, in part or in full: what is left of each line, to deliver now or later. */
    public function default(): Action
    {
        return Action::make('createDelivery')
            ->label('Create delivery')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Posted && Fulfilment::status($record->lines ?? []) !== 'Delivered')
            ->slideOver()
            ->modalHeading(fn (?Model $record): string => "Create delivery for order {$record?->number}")
            ->modalDescription('Each quantity starts at what is left to deliver. Lower it to deliver less now, or enter 0 to leave the line for later.')
            // The order's open lines, in order; the repeater can't add, remove or reorder them, so the action matches each
            // quantity to its line by position, with no hidden field the browser could change.
            ->fillForm(fn (?Model $record): array => ['lines' => collect($record->lines ?? [])
                ->reject(fn (array $line): bool => bccomp(Fulfilment::remaining($line['quantity'], $line['delivered']), '0', 3) <= 0)
                ->map(fn (array $line): array => [
                    'description' => $line['description'],
                    'left' => Fulfilment::remaining($line['quantity'], $line['delivered']),
                    'quantity' => Fulfilment::remaining($line['quantity'], $line['delivered']),
                ])
                ->values()
                ->all()])
            ->schema([
                Repeater::make('lines')
                    ->table([
                        TableColumn::make('Description')->alignStart(),
                        TableColumn::make('Left to deliver')->alignEnd()->width('9rem'),
                        TableColumn::make('Quantity')->alignEnd()->width('8rem'),
                    ])
                    ->schema([
                        TextEntry::make('description'),
                        TextEntry::make('left')
                            ->numeric(maxDecimalPlaces: 3)
                            ->alignEnd(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn (Get $get): ?string => $get('left'))
                            ->rules(['decimal:0,3'])
                            ->required()
                            ->extraInputAttributes(['style' => 'text-align: end'])
                            ->validationMessages([
                                'max' => 'Enter :max or less, what is left to deliver',
                                'decimal' => 'Enter the quantity with at most 3 decimal places, such as 12.5',
                            ]),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false),
            ])
            ->action(function (Model $record, array $data): void {
                // On a resource, in DB::transaction(): lock the order's lines, take the open ones in the same order, and refuse any
                // quantity over what is left now; then $quantities = $open->pluck('id')->combine(collect($data['lines'])->pluck('quantity'));
                // $delivery = $record->deliveries()->create(['status' => DocumentStatus::Draft, 'lines' => Fulfilment::nextLines($open, $quantities->all())]);
                // and open it: $action->redirect(DeliveryResource::getUrl('edit', ['record' => $delivery])), with Action $action in this closure.
                Notification::make()
                    ->success()
                    ->title("Delivery created for order {$record->number}")
                    ->send();
            });
    }

    /** A posted quotation into an order: every line copied, no questions asked. */
    public function createOrder(): Action
    {
        return Action::make('createOrder')
            ->label('Create order')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Posted)
            ->action(function (Model $record): void {
                // On a resource: $order = SalesOrder::create([... the quotation's customer and terms, 'status' => DocumentStatus::Draft,
                // 'lines' => Fulfilment::nextLines($record->lines, $record->lines->pluck('quantity', 'id')->all())]);
                // then open it: $action->redirect(SalesOrderResource::getUrl('edit', ['record' => $order])), with Action $action in this closure.
                Notification::make()
                    ->success()
                    ->title('Order created')
                    ->send();
            });
    }

    /** A posted delivery into its one invoice, with the delivery's quantities and the order's prices. */
    public function createInvoice(): Action
    {
        return Action::make('createInvoice')
            ->label('Create invoice')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Posted && blank($record?->invoice_id))
            ->action(function (Model $record): void {
                // On a resource, in DB::transaction(): read the delivery again with lockForUpdate(); if its invoice_id is no longer blank,
                // someone else has invoiced it, so send a failure notification saying so and halt. Otherwise $invoice = SalesInvoice::create([...,
                // 'status' => DocumentStatus::Draft, 'lines' => Fulfilment::nextLines($record->lines, $record->lines->pluck('quantity', 'id')->all())]);
                // $record->update(['invoice_id' => $invoice->id]). Then open the invoice.
                Notification::make()
                    ->success()
                    ->title('Invoice created')
                    ->send();
            });
    }

    /** An order's delivery status on its list and View page. */
    public function deliveryStatus(): TextColumn
    {
        return TextColumn::make('delivery_status')
            // On a resource: ->state(fn (?SalesOrder $record): ?string => $record ? Fulfilment::status($record->lines) : null).
            ->badge()
            ->color(fn (string $state): string => match ($state) {
                'Partly delivered' => 'info',
                'Delivered' => 'success',
                default => 'gray',
            });
    }
}
