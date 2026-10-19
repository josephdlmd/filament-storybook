<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** An app's user, with an authenticator app as its second factor (no secret saved, so two-factor is off for it). */
class User extends Authenticatable implements FilamentUser, HasAppAuthentication
{
    use Notifiable;

    protected $guarded = [];

    protected $casts = ['invited_at' => 'datetime'];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return null;
    }

    public function saveAppAuthenticationSecret(?string $secret): void {}

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }
}
