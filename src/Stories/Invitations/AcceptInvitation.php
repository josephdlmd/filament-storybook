<?php

namespace Josephdlmd\FilamentStorybook\Stories\Invitations;

use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\PasswordResetResponse;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use SensitiveParameter;

/**
 * Where an invited user sets their own password: Filament's reset-password page, opened from the invitation's signed
 * link instead of a reset token. Copy it, with `UserInvitation`, into the app, and register it in the panel provider:
 * `->routes(fn () => Route::get('invitation/{user}', AcceptInvitation::class)->name('invitation'))`. The users table
 * needs `password` nullable and an `invited_at` timestamp (cast to datetime): a user with no password is still invited.
 */
class AcceptInvitation extends ResetPassword
{
    /** How long an invitation link works (owner's business standard, 2026-10-12). */
    public const DAYS = 7;

    #[Locked]
    public mixed $userId = null;

    /** When the link this page was opened from was sent: a newer invitation closes the page. */
    #[Locked]
    public ?int $sent = null;

    public function mount(?string $email = null, #[SensitiveParameter] ?string $token = null): void
    {
        $user = static::invitedUser(request());

        if (! $user) {
            static::closedNotification()->send();
            $this->redirect(Filament::getLoginUrl());

            return;
        }

        $this->userId = $user->getKey();
        $this->sent = $user->invited_at->timestamp;
        $this->form->fill(['email' => $user->email]);
    }

    /**
     * The user an invitation link is for, while it works. The link is signed, so its user and send time can't be
     * changed; only the latest one sent works, and only until a password is set.
     */
    public static function invitedUser(Request $request): ?Model
    {
        $user = Filament::auth()->getProvider()->retrieveById($request->route('user'));

        return $request->hasValidSignature() && $user && static::isOpen($user) && $user->invited_at->timestamp === (int) $request->query('sent') ? $user : null;
    }

    /** An invitation is open until its user sets a password, for `DAYS` days from when it was last sent. */
    public static function isOpen(Model $user): bool
    {
        return $user->password === null && $user->invited_at?->copy()->addDays(static::DAYS)->isFuture();
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        // Checked again: the page may have stayed open past the expiry, or the invitation been withdrawn or sent again since.
        $user = Filament::auth()->getProvider()->retrieveById($this->userId);

        if (! $user || ! static::isOpen($user) || $user->invited_at->timestamp !== $this->sent) {
            static::closedNotification()->send();

            return null;
        }

        $user->forceFill(['password' => Hash::make($this->form->getState()['password']), 'email_verified_at' => now()])->save();
        Filament::auth()->login($user);
        $this->redirect(Filament::getUrl());

        return null;
    }

    protected static function closedNotification(): Notification
    {
        return Notification::make()
            ->danger()
            ->title('This invitation no longer works')
            ->body('It has expired, been used or been withdrawn. Ask your administrator to send a new one.');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Set your password';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Set your password';
    }

    public function getResetPasswordFormAction(): Action
    {
        return parent::getResetPasswordFormAction()->label('Set password');
    }
}
