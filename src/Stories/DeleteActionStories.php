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
        return 'Deleting a record. The heading names the record, the description says what else goes and that it cannot be undone, and the confirm button repeats the verb. Only the confirm button is red. On a resource, put the same settings on `DeleteAction::make()`, built from the record, such as `->modalHeading(fn (Order $record): string => "Delete order {$record->number}?")`; the gray trigger and red confirm are already app-wide defaults. Basis: WCAG 2.2 3.3.4, Carbon dialogs, USWDS modal, NN/g confirmation dialogs (reviewed 2026), GOV.UK button (warning style only on the final step).';
    }

    public function default(): Action
    {
        return Action::make('delete')
            ->label('Delete')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Delete order 1042?')
            ->modalDescription('Its 3 payments are deleted too. This cannot be undone.')
            ->modalSubmitActionLabel('Delete')
            ->modalSubmitAction(fn (Action $action): Action => $action->color('danger'))
            ->action(fn () => null);
    }
}
