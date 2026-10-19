<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Number;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;
use Josephdlmd\FilamentStorybook\Documents\PaymentStatus;
use Josephdlmd\FilamentStorybook\Stories;

class PaymentStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Record payment';
    }

    public function description(): string
    {
        return 'Recording a payment, in full or in part, against a posted document\'s balance due: a collection from a customer against a sales invoice (`default`), or a payment to a supplier against a purchase order (`supplierPayment`). Put it in the View page\'s `getHeaderActions()`, shown while a balance is due; each payment is a record of its own, listed on the document\'s View page (`records-related-records`). Each is a modal of four inputs or fewer, with the balance due in the amount\'s helper text, and the amount is checked against the balance on the server; the error says the most it can be. A collection is recorded under the serial of the receipt it was written on, checked against the serials already used (in a resource, `->unique(Payment::class, \'number\', ignoreRecord: false)` with the app\'s own model, since Filament otherwise skips the row whose id matches the invoice\'s; the story checks the sample one). A supplier payment is recorded under the next PV number (`records-document-lifecycle.withSeries`). When saving, lock the document\'s row, check again that it is still posted and that the amount fits its balance (two users may record payments, or void it, at once), then lower its balance due, all in one transaction. Give `payments.number` a unique index: it is the real guard against a serial two users enter at once, and the action catches its error, keeps the form open and says what to check. The balance\'s status is Unpaid (gray), Partly paid (blue), Paid (green) or Overdue (red), each with its word (`paymentStatus`). An app that keeps Philippine books records what the customer withholds with filament-storybook-ph (`actions-record-payment-with-withholding`). Business standards from the owner (2026-10-11). Basis: Carbon forms (Aug 2026: a dialog form for fewer than five inputs), GOV.UK error message (Jan 2024: say the limit, as in "must be 35 characters or less"), Carbon status indicators (Aug 2026: gray for not started, blue for workflow progress, green for success, red for danger), USWDS table (numbers right-aligned).';
    }

    // Not imported, so the snippet's imports stay what a resource needs.
    public function examples(): array
    {
        return [PaymentStatus::class];
    }

    public function model(): ?string
    {
        return Documents\SalesInvoice::class;
    }

    public function records(): array
    {
        return [
            1 => ['id' => 1, 'status' => DocumentStatus::Posted, 'number' => '001232', 'total' => '112000.00', 'balance_due' => '112000.00', 'payment_status' => PaymentStatus::Unpaid, 'supplier' => 'Benguet Trading'],
            2 => ['status' => DocumentStatus::Posted, 'number' => '001233', 'total' => '3363.71', 'balance_due' => '2363.71', 'payment_status' => PaymentStatus::PartlyPaid],
            3 => ['status' => DocumentStatus::Posted, 'number' => '001229', 'total' => '5600.00', 'balance_due' => '0.00', 'payment_status' => PaymentStatus::Paid],
            4 => ['status' => DocumentStatus::Posted, 'number' => '001214', 'total' => '890.50', 'balance_due' => '890.50', 'payment_status' => PaymentStatus::Overdue],
        ];
    }

    /** A collection from a customer, under the serial of the receipt it was written on. */
    public function default(): Action
    {
        return Action::make('recordPayment')
            ->label('Record payment')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Posted && bccomp((string) $record?->balance_due, '0', 2) > 0)
            ->modalHeading(fn (?Model $record): string => "Record payment for invoice {$record?->number}")
            ->schema([
                DatePicker::make('paid_on')
                    ->label('Payment date')
                    ->default(today())
                    ->maxDate(today())
                    ->required()
                    ->maxWidth('xs'),
                TextInput::make('number')
                    ->label('Receipt number')
                    ->helperText('The serial printed on the collection receipt you wrote')
                    ->required()
                    ->maxLength(20)
                    ->unique(Documents\Payment::class, 'number', ignoreRecord: false)
                    ->maxWidth('xs')
                    ->validationMessages(['unique' => 'Enter the serial of the receipt you wrote; receipt :input is already recorded']),
                TextInput::make('amount')
                    ->label('Amount received')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.01)
                    ->minValue(0)
                    ->required()
                    ->maxWidth('xs')
                    ->default(fn (?Model $record): string => (string) $record?->balance_due)
                    ->helperText(fn (?Model $record): string => 'Balance due '.Number::currency((float) $record?->balance_due, 'PHP'))
                    ->rules([
                        'decimal:0,2',
                        fn (?Model $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            if (bccomp(number_format((float) $value, 2, '.', ''), '0', 2) <= 0) {
                                $fail('Enter the amount received');
                            } elseif (bccomp(number_format((float) $value, 2, '.', ''), (string) $record?->balance_due, 2) > 0) {
                                $fail('Amount received must be '.Number::currency((float) $record?->balance_due, 'PHP').' or less, the balance due');
                            }
                        },
                    ])
                    ->validationMessages(['decimal' => 'Enter the amount with at most 2 decimal places, such as 1250.50']),
                TextInput::make('reference')
                    ->label('Reference (optional)')
                    ->helperText('A check number or bank reference')
                    ->maxLength(50)
                    ->maxWidth('xs'),
            ])
            ->modalSubmitActionLabel('Record payment')
            ->successNotificationTitle(fn (array $data): string => "Receipt {$data['number']} recorded")
            ->failureNotificationTitle(fn (array $data): string => "Receipt {$data['number']} not recorded")
            ->action(function (Model $record, array $data, Action $action): void {
                try {
                    // On a resource, inside DB::transaction(): lock the invoice with lockForUpdate(), check again that it is posted and the amount fits its balance,
                    // and lower balance_due by the amount, with bcmath.
                    $record->payments()->create([...$data, 'status' => DocumentStatus::Posted]);
                } catch (UniqueConstraintViolationException) {
                    // The unique index on payments.number caught a serial another user recorded after this form was checked.
                    $action->failureNotificationBody("Receipt {$data['number']} was just recorded by someone else. Check the serial on the receipt you wrote.");
                    $action->sendFailureNotification();
                    $action->halt();
                }

                $action->success();
            });
    }

    /** A payment to a supplier against a purchase order's balance due. */
    public function supplierPayment(): Action
    {
        return Action::make('recordPayment')
            ->label('Record payment')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Posted && bccomp((string) $record?->balance_due, '0', 2) > 0)
            ->modalHeading(fn (?Model $record): string => "Record payment to {$record?->supplier}")
            ->schema([
                DatePicker::make('paid_on')
                    ->label('Payment date')
                    ->default(today())
                    ->maxDate(today())
                    ->required()
                    ->maxWidth('xs'),
                TextInput::make('amount')
                    ->label('Amount paid')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.01)
                    ->minValue(0.01)
                    ->maxValue(fn (?Model $record): string => (string) $record?->balance_due)
                    ->rules(['decimal:0,2'])
                    ->required()
                    ->maxWidth('xs')
                    ->helperText(fn (?Model $record): string => 'Balance due '.Number::currency((float) $record?->balance_due, 'PHP'))
                    ->validationMessages([
                        'min' => 'Amount paid must be ₱0.01 or more',
                        'max' => fn (?Model $record): string => 'Amount paid must be '.Number::currency((float) $record?->balance_due, 'PHP').' or less, the balance due',
                        'decimal' => 'Enter the amount with at most 2 decimal places, such as 1250.50',
                    ]),
                TextInput::make('reference')
                    ->label('Reference (optional)')
                    ->helperText('A check number or bank reference')
                    ->maxLength(50)
                    ->maxWidth('xs'),
            ])
            ->modalSubmitActionLabel('Record payment')
            ->successNotificationTitle(fn (?Model $record): string => "Payment to {$record?->supplier} recorded")
            ->action(function (Model $record, array $data, Action $action): void {
                // On a resource, in DB::transaction(): lock the order with lockForUpdate(), check again that it is posted and the amount fits its balance,
                // create the payment under DocumentNumber::next('PV'), and lower balance_due by the amount.
                $action->success();
            });
    }

    /** How much of a document is paid, in a list: the word and its colour, from `PaymentStatus`. */
    public function paymentStatus(): TextColumn
    {
        return TextColumn::make('payment_status')
            ->badge();
    }
}
