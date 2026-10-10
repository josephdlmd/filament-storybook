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
        return 'Deleting a record. The button that opens it is low-emphasis danger (outlined); the heading names the record, the description says what else goes and that it cannot be undone, and the solid red confirm button repeats the verb. On a resource, put the same heading and description on `DeleteAction::make()`, built from the record, such as `->modalHeading(fn (Order $record): string => "Delete order {$record->number}?")`; the outlined trigger is already an app-wide default. Basis: Carbon button (danger tertiary when one of several actions, primary danger for the confirming step) and Carbon dialogs; WCAG 2.2 3.3.4.';
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
}
