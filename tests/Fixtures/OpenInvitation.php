<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Josephdlmd\FilamentStorybook\Stories\Invitations\AcceptInvitation;

/** AcceptInvitation opened from a working link: Livewire's tests mount a page on their own URL, which has no signature. */
class OpenInvitation extends AcceptInvitation
{
    public static ?Model $user = null;

    public static function invitedUser(Request $request): ?Model
    {
        return static::$user;
    }
}
