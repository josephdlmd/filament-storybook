<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Invitations\AcceptInvitation;
use Josephdlmd\FilamentStorybook\Stories\Invitations\UserInvitation;

class InviteUserStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Invite a user';
    }

    public function description(): string
    {
        return 'Adding a user by email: the admin gives their name, email address and roles, and they get an email with a link to set their own password, so no one else ever knows it. The link works for 7 days and once; sending it again makes a new link and stops the old one. "Invite user" takes the place of the Users page\'s New button (`default`), for whoever has the permission to manage users: each button asks the users policy with `authorize()` (create, update, delete), since a plain action checks nothing. On the list (`list`), a user who hasn\'t set a password yet shows Invited (info) or, after 7 days, Expired (warning, since it needs the admin to act), with "Send again" and "Withdraw invitation", which deletes the invited user after a danger confirmation. Copy `UserInvitation` (the email) and `AcceptInvitation` (the page where they set their password, which is Filament\'s reset-password page) into the app; the users table needs `password` nullable and an `invited_at` timestamp, and the email is queued, so the app needs a queue worker (`composer dev` runs one). Basis: NIST SP 800-63B-4 (Aug 2025: passwords are chosen by the subscriber); the house rules on status badges, confirmations and toasts; the 7 days and who may invite are the owner\'s business standards (2026-10-12).';
    }

    public function examples(): array
    {
        return [UserInvitation::class, AcceptInvitation::class];
    }

    public function records(): array
    {
        return [
            1 => ['name' => 'Ana Reyes', 'email' => 'ana@example.com', 'status' => 'Active'],
            2 => ['name' => 'Ben Cruz', 'email' => 'ben@example.com', 'status' => 'Invited'],
            3 => ['name' => 'Carla Santos', 'email' => 'carla@example.com', 'status' => 'Expired'],
        ];
    }

    public function default(): Action
    {
        return Action::make('invite')
            ->label('Invite user')
            // Only someone the users policy lets create users; anyone else could invite an admin.
            ->authorize('create')
            ->modalDescription('They get an email with a link to set their own password. The link works for 7 days.')
            ->schema([
                TextInput::make('name')
                    ->maxLength(255)
                    ->maxWidth('md')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->maxLength(254)
                    ->extraInputAttributes(['spellcheck' => 'false'])
                    ->maxWidth('md')
                    // On a resource: ->unique(User::class), with 'unique' => 'A user with this email address already exists'.
                    ->required(),
                // Several from five or fewer: a checkbox list (forms-choice). On a resource: ->relationship('roles', 'name').
                CheckboxList::make('roles')
                    ->label('Roles (optional)')
                    ->options(['accounting' => 'Accounting', 'admin' => 'Admin', 'sales' => 'Sales']),
            ])
            ->action(function (array $data): void {
                // On a resource: $user = User::forceCreate([...Arr::only($data, ['name', 'email']), 'password' => null, 'invited_at' => now()]);
                // $user->syncRoles($data['roles']); $user->notify(new UserInvitation);
                Notification::make()
                    ->success()
                    ->title("Invitation sent to {$data['email']}")
                    ->send();
            });
    }

    /** The Users list: who is still invited, with sending again and withdrawing. */
    public function list(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                // On a resource: ->state(fn (?User $record): string => match (true) { $record?->password !== null => 'Active',
                // $record && AcceptInvitation::isOpen($record) => 'Invited', default => 'Expired' }).
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Invited' => 'info',
                        'Expired' => 'warning',
                        default => 'success',
                    }),
            ])
            ->searchPlaceholder('Search by name or email')
            ->filters([
                // On a resource, as the status column works it out: ->query(fn (Builder $query, array $data): Builder => match ($data['value']) {
                // 'Active' => $query->whereNotNull('password'), 'Invited' => $query->whereNull('password')->where('invited_at', '>', now()->subDays(AcceptInvitation::DAYS)),
                // 'Expired' => $query->whereNull('password')->where('invited_at', '<=', now()->subDays(AcceptInvitation::DAYS)), default => $query }).
                SelectFilter::make('status')
                    ->options(['Active' => 'Active', 'Expired' => 'Expired', 'Invited' => 'Invited']),
            ])
            ->recordActions([
                Action::make('sendAgain')
                    ->label('Send again')
                    ->authorize('update')
                    ->visible(fn (?Model $record): bool => $record?->status !== 'Active')
                    ->action(function (Model $record): void {
                        // On a resource: $record->forceFill(['invited_at' => now()])->save(); $record->notify(new UserInvitation);
                        Notification::make()
                            ->success()
                            ->title("Invitation sent again to {$record->email}")
                            ->send();
                    }),
                Action::make('withdraw')
                    ->label('Withdraw invitation')
                    ->color('danger')
                    ->outlined()
                    ->authorize('delete')
                    ->visible(fn (?Model $record): bool => $record?->status !== 'Active')
                    ->requiresConfirmation()
                    ->modalHeading(fn (?Model $record): string => "Withdraw invitation to {$record?->name}?")
                    ->modalDescription('Their link stops working. You can invite them again later.')
                    ->modalSubmitActionLabel('Withdraw')
                    ->action(function (Model $record): void {
                        // On a resource: $record->delete(), only while AcceptInvitation::isOpen($record) or expired, never once they've set a password.
                        Notification::make()
                            ->success()
                            ->title("Invitation to {$record->email} withdrawn")
                            ->send();
                    }),
            ]);
    }
}
