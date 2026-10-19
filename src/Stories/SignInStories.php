<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Josephdlmd\FilamentStorybook\Attributes\States;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Auth\FailedLogInPreview;
use Josephdlmd\FilamentStorybook\Stories\Auth\LogInPreview;
use Josephdlmd\FilamentStorybook\Stories\Auth\RequestPasswordResetPreview;
use Josephdlmd\FilamentStorybook\Stories\Auth\ResetPasswordPreview;
use Josephdlmd\FilamentStorybook\Stories\Auth\TwoFactorPreview;

class SignInStories extends Stories
{
    public function title(): string
    {
        return 'Forms/Sign in';
    }

    public function description(): string
    {
        return 'How users get in: Filament\'s own log-in, forgotten-password, reset-password and two-factor pages, which the house defaults word ("Log in", "Incorrect email or password. Try again.", "Security code") and whose password rules they set. Build nothing: turn the pages on in the panel provider, `->login()`, `->passwordReset()` and `->multiFactorAuthentication([AppAuthentication::make()->recoverable()])`, and keep Filament\'s classes; the stories show them, through preview classes that never sign anyone in or send an email. A wrong email or password gets one message for both, and the password field is cleared. New users are invited and set their own password (`actions-invite-a-user`), so there is no sign-up page. A guest page has no data to load and forbids no one, so it shows populated and error. Basis: Carbon login (Aug 2026: "Log in"; one message for a wrong ID or password; a "Forgot password" link); NIST SP 800-63B-4 (passwords, multi-factor and recovery); WCAG 3.3.8 (no cognitive test: pasting and password managers work).';
    }

    /** The log-in page, `->login()`: email address, password, Remember me, and the forgotten-password link. */
    #[States('error')]
    public function default(): LogInPreview
    {
        // In the panel provider: ->login(). Filament's own page; nothing to copy.
        return new LogInPreview;
    }

    /** After a wrong email or password: one message for both, under the email address, and the password cleared. */
    #[States('error')]
    public function failed(): FailedLogInPreview
    {
        // In the panel provider: ->login(); the house defaults word the message. Filament's own page; nothing to copy.
        return new FailedLogInPreview;
    }

    /** The second step, `->multiFactorAuthentication([...])`: the code from the user's authenticator app, or a recovery code. */
    #[States('error')]
    public function twoFactor(): TwoFactorPreview
    {
        // In the panel provider: ->multiFactorAuthentication([AppAuthentication::make()->recoverable()]). Filament's own page; nothing to copy.
        return new TwoFactorPreview;
    }

    /** Forgotten password, `->passwordReset()`: the email address to send the reset link to. */
    #[States('error')]
    public function forgottenPassword(): RequestPasswordResetPreview
    {
        // In the panel provider: ->passwordReset(). Filament's own page; nothing to copy.
        return new RequestPasswordResetPreview;
    }

    /** The page the reset link opens: a new password, typed twice, with its minimum length in the helper text. */
    #[States('error')]
    public function resetPassword(): ResetPasswordPreview
    {
        // In the panel provider: ->passwordReset(); the email's link opens it. Filament's own page; nothing to copy.
        return new ResetPasswordPreview;
    }
}
