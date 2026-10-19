<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\Stock;

class AdjustStockStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Adjust stock';
    }

    public function description(): string
    {
        return 'Correcting an item\'s stock: a gray "Adjust stock" button on its View page. The dialog states what is on hand, takes the change (more or fewer, as a signed number: "-3" for three fewer), a reason from four, and an optional note, and sends the adjustment for approval; it changes stock only when approved (`actions-approve-and-reject`), through `Stock::adjust()`, which refuses taking the item below zero. Basis: the owner\'s business standards (2026-10-12: a reason and approval for every adjustment; no negative stock); Carbon forms (radios for up to five options; a modal for fewer than five inputs); the lifecycle\'s approval step (`records-document-lifecycle.sendForApproval`).';
    }

    public function records(): array
    {
        return [1 => ['name' => 'Insulin pens, 3 mL', 'unit' => 'packs', 'on_hand' => '8']];
    }

    public function default(): Action
    {
        return Action::make('adjustStock')
            ->label('Adjust stock')
            ->color('gray')
            ->modalHeading(fn (?Model $record): string => "Adjust stock of {$record?->name}")
            ->modalDescription(fn (?Model $record): string => 'On hand now: '.rtrim(rtrim((string) $record?->on_hand, '0'), '.')." {$record?->unit}. The change applies once the adjustment is approved.")
            ->schema([
                TextInput::make('change')
                    ->label('Change in quantity')
                    ->helperText('For example, -3 for three fewer')
                    ->inputMode('decimal')
                    // A zero written any way ("0.0", "-0") is still no change; not_in:0 compares the text, so it would let those through.
                    ->rules(['numeric', 'decimal:0,3', fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        if (is_numeric($value) && (float) $value == 0) {
                            $fail('Enter a change other than 0');
                        }
                    }])
                    ->maxWidth('xs')
                    ->required()
                    ->validationMessages([
                        'decimal' => 'Enter the change with at most 3 decimal places, such as -2.5',
                    ]),
                Radio::make('reason')
                    ->options([
                        'count' => 'Count correction',
                        'damaged' => 'Damaged',
                        'lost' => 'Lost',
                        'expired' => 'Expired',
                    ])
                    ->required(),
                Textarea::make('note')
                    ->label('Note (optional)')
                    ->rows(2)
                    ->maxLength(500),
            ])
            ->modalSubmitActionLabel('Send for approval')
            ->action(function (Model $record, array $data): void {
                // On a resource: StockAdjustment::create(['item_id' => $record->id, 'change' => $data['change'], 'reason' => $data['reason'],
                // 'note' => $data['note'], 'status' => DocumentStatus::ForApproval]); approving it posts it in DB::transaction(): Stock::adjust($item,
                // $adjustment->change, "Adjustment {$adjustment->number}"), which refuses taking the item below zero.
                Notification::make()
                    ->success()
                    ->title("Adjustment of {$record->name} sent for approval")
                    ->send();
            });
    }
}
