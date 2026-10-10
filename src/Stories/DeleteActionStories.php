<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Josephdlmd\FilamentStorybook\Stories;

class DeleteActionStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Delete';
    }

    public function description(): string
    {
        return 'Deleting a record. The button that opens it is low-emphasis danger (outlined); the heading repeats the button\'s verb and names the record, the description says the consequence (what else goes, and that it cannot be undone; or, for a model with `SoftDeletes`, how to get it back, as `restorable` shows), and the solid red confirm button uses the same verb. On a resource, put the same heading and description on `DeleteAction::make()`, built from the record, such as `->modalHeading(fn (?Order $record): string => "Delete order {$record?->number}?")` (nullable, because a table calls row-action closures without a record while it has no rows); the outlined trigger is already an app-wide default. Basis: Carbon button (danger tertiary when one of several actions, primary danger for the confirming step), Carbon dialogs and modal (the title uses the button\'s label); WCAG 2.2 3.3.4.';
    }

    public function default(): Action
    {
        return Action::make('delete')
            ->label('Delete')
            ->color('danger')
            ->outlined()
            ->requiresConfirmation()
            ->modalHeading('Delete order 1042?')
            ->modalDescription('Its 3 payments are deleted too. This cannot be undone.')
            ->modalSubmitActionLabel('Delete')
            ->action(fn () => null);
    }

    /** For a model with `SoftDeletes`: Filament's delete can be reversed, so say how instead of "cannot be undone". */
    public function restorable(): Action
    {
        return Action::make('delete')
            ->label('Delete')
            ->color('danger')
            ->outlined()
            ->requiresConfirmation()
            ->modalHeading('Delete order 1042?')
            ->modalDescription('You can restore it from the list, filtered by Deleted records.')
            ->modalSubmitActionLabel('Delete')
            ->action(fn () => null);
    }
}
