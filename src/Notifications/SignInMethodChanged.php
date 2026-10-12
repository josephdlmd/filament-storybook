<?php

namespace Josephdlmd\FilamentStorybook\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the account's owner, by email, that a way to log in was added or removed, so a change they didn't make is
 * noticed (NIST SP 800-63B-4: notify the subscriber when an authenticator is bound or removed).
 */
class SignInMethodChanged extends Notification
{
    /** @param  string  $change  What happened, as the end of a sentence: "turned on", "turned off". */
    public function __construct(public string $method, public string $change) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(ucfirst("{$this->method} {$this->change}"))
            ->line(ucfirst("{$this->method} was {$this->change} for your account on ".now()->toDayDateTimeString().' ('.now()->format('T').').'))
            ->line('If you didn\'t do this, change your password and tell your administrator at once.');
    }
}
