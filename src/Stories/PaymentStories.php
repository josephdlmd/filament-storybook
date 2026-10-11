<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\DocumentTotals;

class PaymentStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Record payment';
    }

    public function description(): string
    {
        return 'Recording a payment, in full or in part, against a posted document\'s balance due: a collection from a customer against a sales invoice (`default`), or a payment to a supplier against what the business owes them (`supplierPayment`). Put it in the View page\'s `getHeaderActions()`, shown while a balance is due; each payment is a record of its own, listed on the document\'s View page (`records-related-records`). The form is a modal (fewer than five inputs) with the balance due in the amount\'s helper text. What it settles (the amount, plus any tax withheld) is checked against the balance on the server, and the error says the most it can be. Withholding tax is taken here, never on the document. From a customer, the user types the tax withheld from the customer\'s BIR Form 2307. To a supplier, the app works it out from the rate (1% for goods, 2% for services) on the VAT-exclusive share of the amount settled, with `DocumentTotals::withholding()` (the payment that settles the bill takes what is left, so the bill\'s payments together withhold exactly the rate on its total less VAT), and shows it and the cash to pay. When saving, lock the document\'s row, check the amounts against its balance again (two users may record payments at once), then lower its balance due, all in one transaction. The balance\'s status is Unpaid (gray), Partly paid (blue), Paid (green) or Overdue (red), each with its word (`paymentStatus`). Business standards from the owner (2026-10-11): payments both ways, withholding on payment, the rates, the 2307. Basis: Carbon forms (Aug 2026: a dialog for fewer than five inputs), GOV.UK error message (Jan 2024: say the limit, as in "must be 35 characters or less"), Carbon status indicators (Aug 2026: gray for not started, blue for workflow progress, green for success, red for danger), USWDS table (numbers right-aligned).';
    }

    public function examples(): array
    {
        return [DocumentTotals::class];
    }

    public function records(): array
    {
        return [
            1 => ['status' => 'posted', 'number' => 'SI-000123', 'total' => '3363.71', 'vat' => '360.40', 'balance_due' => '2363.71', 'tax_withheld' => '8.93', 'payment_status' => 'partly_paid', 'supplier' => 'Benguet Trading'],
            2 => ['status' => 'posted', 'number' => 'SI-000124', 'total' => '1120.00', 'vat' => '120.00', 'balance_due' => '1120.00', 'payment_status' => 'unpaid'],
            3 => ['status' => 'posted', 'number' => 'SI-000119', 'total' => '5600.00', 'vat' => '600.00', 'balance_due' => '0.00', 'payment_status' => 'paid'],
            4 => ['status' => 'posted', 'number' => 'SI-000102', 'total' => '890.50', 'vat' => '95.41', 'balance_due' => '890.50', 'payment_status' => 'overdue'],
        ];
    }

    /** A collection from a customer, with the tax withheld typed from their BIR Form 2307. */
    public function default(): Action
    {
        return Action::make('recordPayment')
            ->label('Record payment')
            ->visible(fn (?Model $record): bool => $record?->status === 'posted' && bccomp((string) $record?->balance_due, '0', 2) > 0)
            ->modalHeading(fn (?Model $record): string => "Record payment for invoice {$record?->number}")
            ->schema([
                DatePicker::make('paid_on')
                    ->label('Payment date')
                    ->default(today())
                    ->maxDate(today())
                    ->required()
                    ->maxWidth('xs'),
                TextInput::make('amount')
                    ->label('Amount received')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.01)
                    ->minValue(0)
                    ->required()
                    ->maxWidth('xs')
                    ->helperText(fn (?Model $record): string => 'Balance due '.Number::currency((float) $record?->balance_due, 'PHP'))
                    ->rules([
                        'decimal:0,2',
                        fn (Get $get, ?Model $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                            $settled = bcadd(number_format((float) $value, 2, '.', ''), number_format((float) $get('tax_withheld'), 2, '.', ''), 2);

                            if (bccomp($settled, '0', 2) <= 0) {
                                $fail('Enter the amount received, the tax withheld, or both');
                            } elseif (bccomp($settled, (string) $record?->balance_due, 2) > 0) {
                                $fail('Amount received and tax withheld together must be '.Number::currency((float) $record?->balance_due, 'PHP').' or less, the balance due');
                            }
                        },
                    ])
                    ->validationMessages(['decimal' => 'Enter the amount with at most 2 decimal places, such as 1250.50']),
                TextInput::make('tax_withheld')
                    ->label('Tax withheld (optional)')
                    ->helperText('From the customer\'s BIR Form 2307')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.01)
                    ->minValue(0)
                    ->rules(['decimal:0,2'])
                    ->maxWidth('xs')
                    ->validationMessages(['decimal' => 'Enter the tax withheld with at most 2 decimal places, such as 30.03']),
                TextInput::make('reference')
                    ->label('Reference number (optional)')
                    ->helperText('For example, the check or deposit slip number')
                    ->maxLength(50)
                    ->maxWidth('xs'),
            ])
            ->modalSubmitActionLabel('Record payment')
            ->successNotificationTitle('Payment recorded')
            ->action(function (Model $record, array $data, Action $action): void {
                // On a resource, in DB::transaction(): lock the invoice with lockForUpdate(), check the amounts against its balance again,
                // create the payment, and lower balance_due by the amount plus the tax withheld, with bcmath.
                $action->success();
            });
    }

    /** A payment to a supplier: the app works out the tax withheld from the rate, and shows the cash to pay. */
    public function supplierPayment(): Action
    {
        return Action::make('recordPayment')
            ->label('Record payment')
            ->visible(fn (?Model $record): bool => $record?->status === 'posted' && bccomp((string) $record?->balance_due, '0', 2) > 0)
            ->modalHeading(fn (?Model $record): string => "Record payment to {$record?->supplier}")
            ->schema([
                DatePicker::make('paid_on')
                    ->label('Payment date')
                    ->default(today())
                    ->maxDate(today())
                    ->required()
                    ->maxWidth('xs'),
                Radio::make('withholding_rate')
                    ->options([
                        '0.01' => '1%, for goods',
                        '0.02' => '2%, for services',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('amount')
                    ->label('Amount settled')
                    ->numeric()
                    ->prefix('₱')
                    ->step(0.01)
                    ->minValue(0.01)
                    ->maxValue(fn (?Model $record): string => (string) $record?->balance_due)
                    ->rules(['decimal:0,2'])
                    ->required()
                    ->live(onBlur: true)
                    ->maxWidth('xs')
                    ->helperText(fn (?Model $record): string => 'Balance due '.Number::currency((float) $record?->balance_due, 'PHP').', including the tax withheld')
                    ->validationMessages([
                        'min' => 'Amount settled must be ₱0.01 or more',
                        'max' => fn (?Model $record): string => 'Amount settled must be '.Number::currency((float) $record?->balance_due, 'PHP').' or less, the balance due',
                        'decimal' => 'Enter the amount with at most 2 decimal places, such as 1250.50',
                    ]),
                TextEntry::make('tax_withheld')
                    ->state(fn (Get $get, ?Model $record): ?string => is_numeric($get('amount')) && filled($get('withholding_rate'))
                        ? DocumentTotals::withholding(number_format((float) $get('amount'), 2, '.', ''), (string) $record?->total, (string) $record?->vat, $get('withholding_rate'), (string) $record?->balance_due, (string) $record?->tax_withheld)
                        : null)
                    ->money('PHP'),
                TextEntry::make('cash_paid')
                    ->label('Cash to pay')
                    ->state(fn (Get $get, ?Model $record): ?string => is_numeric($get('amount')) && filled($get('withholding_rate'))
                        ? bcsub(number_format((float) $get('amount'), 2, '.', ''), DocumentTotals::withholding(number_format((float) $get('amount'), 2, '.', ''), (string) $record?->total, (string) $record?->vat, $get('withholding_rate'), (string) $record?->balance_due, (string) $record?->tax_withheld), 2)
                        : null)
                    ->money('PHP'),
                TextInput::make('reference')
                    ->label('Reference number (optional)')
                    ->helperText('For example, the check number')
                    ->maxLength(50)
                    ->maxWidth('xs'),
            ])
            ->modalSubmitActionLabel('Record payment')
            ->successNotificationTitle('Payment recorded')
            ->action(function (Model $record, array $data, Action $action): void {
                // On a resource, in DB::transaction(): lock the bill with lockForUpdate(), check the amount against its balance again,
                // work out the tax withheld again with DocumentTotals::withholding(), create the payment, and lower balance_due by the amount.
                $action->success();
            });
    }

    /** How much of a document is paid, in a list: the word and its colour. */
    public function paymentStatus(): TextColumn
    {
        return TextColumn::make('payment_status')
            ->badge()
            ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->ucfirst())
            ->color(fn (string $state): string => match ($state) {
                'partly_paid' => 'info',
                'paid' => 'success',
                'overdue' => 'danger',
                default => 'gray',
            });
    }
}
