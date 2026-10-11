<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;

class NotificationStories extends Stories
{
    public function title(): string
    {
        return 'Feedback/Notifications';
    }

    public function description(): string
    {
        return 'Feedback on what the user just did. A toast says how a task went; it has no story kind of its own, so each toast story is the button that sends it: press it to see the toast. The title is short, says the most important thing and has no full stop; for a failure it says what was not done. The body is one or two sentences that don\'t repeat the title and, after a failure, say how to fix it. Colour follows the outcome: success, warning (done, but something may not be as expected), danger (failed). A failure, and a toast with an action, stay until dismissed; that is an app-wide default, so don\'t add `->persistent()`. On a resource\'s Create and Edit pages Filament already sends a success toast ("Created", "Saved"), which meets the rule; name the record with `getSavedNotificationTitle()` or `getCreatedNotificationTitle()` on the page, or with `->successNotificationTitle()` on an action. A message about part of a page that stays until it is resolved is not a toast but an inline `Callout` just above the field or button it concerns (`inline`), info or warning only, never success or danger; a `Callout` isn\'t announced to screen readers, so it is only for a message that is there when the page loads. Use callouts sparingly. Basis: Carbon notification usage and accessibility (Sep 2026: the callout variant "only includes informational and warning statuses"; "place the callout above the relevant button or input"), notifications pattern (Aug 2026) and Callout component docs; WCAG 4.1.3.';
    }

    public function records(): array
    {
        return [1 => ['name' => 'Abra Medical Supply']];
    }

    /** Success after saving, from an action: the title says what happened. */
    public function default(): Action
    {
        return Action::make('save')
            ->label('Save')
            ->successNotificationTitle(fn (?Model $record): string => "{$record?->name} saved")
            ->action(fn (Action $action) => $action->success());
    }

    /** Saved, but the user should follow up: a warning whose body says what to do next. */
    public function savedWithWarning(): Action
    {
        return Action::make('save')
            ->label('Save')
            ->action(fn () => Notification::make()
                ->warning()
                ->title('Abra Medical Supply saved')
                ->body('It has no TIN yet. Add one before you record its invoices.')
                ->send());
    }

    /** Nothing was saved: danger, saying what failed and how to fix it. It stays open until dismissed. */
    public function notSaved(): Action
    {
        return Action::make('save')
            ->label('Save')
            ->action(function (Action $action): void {
                Notification::make()
                    ->danger()
                    ->title('Abra Medical Supply not saved')
                    ->body('Someone else changed it while you were editing. Check their changes and save again.')
                    ->send();

                $action->halt();
            });
    }

    /** One optional action of one or two words, which takes the user where they can act; the toast stays until dismissed. */
    public function withAction(): Action
    {
        return Action::make('create')
            ->label('Create')
            ->action(fn () => Notification::make()
                ->success()
                ->title('Abra Medical Supply created')
                ->actions([
                    Action::make('view')
                        ->label('View supplier')
                        // On a resource: SupplierResource::getUrl('view', ['record' => $record])
                        ->url('#'),
                ])
                ->send());
    }

    /** A message about part of the page, there when it loads and until it is resolved: info or warning, just above the field or button it concerns. Not for feedback on an action, which screen readers wouldn't hear. */
    public function inline(): Callout
    {
        return Callout::make('No delivery date')
            ->description('Add a delivery date before this order ships.')
            ->warning();
    }
}
