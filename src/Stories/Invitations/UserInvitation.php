<?php

namespace Josephdlmd\FilamentStorybook\Stories\Invitations;

use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * The invitation email: a signed link to `AcceptInvitation` that works for its `DAYS` days and stops working when a
 * newer one is sent. Send it after setting the user's `invited_at` to now. Copy it into the app.
 *
 * Queued, so a slow or failing mail server doesn't hold up or break the action after the user is saved; the job reloads
 * the user, so its link is for the newest `invited_at`.
 */
class UserInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expires = $notifiable->invited_at->copy()->addDays(AcceptInvitation::DAYS);
        $app = config('app.name');

        return (new MailMessage)
            ->subject("You're invited to {$app}")
            ->line("You've been invited to {$app}. Set your password to log in.")
            ->action('Set your password', URL::temporarySignedRoute(
                Filament::getCurrentOrDefaultPanel()->generateRouteName('invitation'),
                $expires,
                ['user' => $notifiable->getKey(), 'sent' => $notifiable->invited_at->timestamp],
            ))
            ->line("The link works until {$expires->toDayDateTimeString()} ({$expires->format('T')}). If it has expired, ask whoever invited you to send a new one.");
    }
}
