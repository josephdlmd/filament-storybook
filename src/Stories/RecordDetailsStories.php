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
        return 'The summary of a record\'s facts, in the one-quarter column of its View page (`records-view-page`). It is a section headed "Details", with its Edit action on the heading row and one read-only `TextEntry` per fact, label above value. Values use the field patterns\' formats: the status as a badge (`tables-status-badge`), money with `->money(\'PHP\')`, the TIN as stored (`forms-tin`), and dates readable, never ISO. A missing value links to where it\'s added ("Add due date") instead of showing a dash. On a resource page, `EditAction` opens the Edit page, or the resource form in a modal when there is no Edit page. Filament hides it when the policy refuses `update`, so a locked record offers nothing to add. Values are text, not disabled fields, so they keep full contrast and can be selected and copied. Basis: GOV.UK summary card (actions after the card\'s title) and summary list (a link in the value column for missing information); Carbon read-only states and structured list; WCAG 1.3.1 and 1.4.3.';
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
                    ->date()
                    ->belowContent(
                        EditAction::make('addDueDate')
                            ->label('Add due date')
                            ->link()
                            ->hidden(fn (TextEntry $component): bool => filled($component->getState())),
                    ),
                TextEntry::make('total')
                    ->money('PHP'),
                TextEntry::make('balance_due')
                    ->money('PHP'),
            ]);
    }
}
