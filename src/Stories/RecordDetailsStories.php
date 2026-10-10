<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Josephdlmd\FilamentStorybook\Stories;

class RecordDetailsStories extends Stories
{
    public function title(): string
    {
        return 'Records/Details';
    }

    public function description(): string
    {
        return 'A record\'s facts as a list of labels and values, in the summary column of its View page (`records-view-page`): a section headed "Details", with Edit in its header, and one read-only `TextEntry` per fact, label above value. Values use the field patterns\' formats: the status as a badge (`tables-status-badge`), money with `->money(\'PHP\')`, the TIN as stored (`forms-tin`), dates in the app\'s locale. On a resource page, `EditAction` opens the Edit page, or the resource form in a modal when there is no Edit page, and Filament hides it when the policy refuses `update`. No qualifying source sets how a missing value shows, so an empty value shows nothing. Values are text, not disabled fields, so they keep full contrast and can be selected and copied. Basis: Carbon structured list (terms and definitions, read-only) and read-only states (static information is text); WCAG 1.3.1 and 1.4.3.';
    }

    public function records(): array
    {
        return [1 => [
            'number' => 'BILL-0042',
            'status' => 'for_approval',
            'supplier' => 'Abra Medical Supply',
            'supplier_tin' => '123-456-789-000',
            'bill_date' => '2026-10-02',
            'due_date' => null,
            'total' => '3363.71',
            'balance_due' => '2363.71',
        ]];
    }

    public function default(): Section
    {
        return Section::make('Details')
            ->afterHeader([
                EditAction::make()->color('gray'),
            ])
            ->schema([
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->headline())
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'for_approval' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('supplier'),
                TextEntry::make('supplier_tin')
                    ->label('Supplier TIN'),
                TextEntry::make('bill_date')
                    ->date(),
                TextEntry::make('due_date')
                    ->date(),
                TextEntry::make('total')
                    ->money('PHP'),
                TextEntry::make('balance_due')
                    ->money('PHP'),
            ]);
    }
}
