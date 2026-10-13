<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\DocumentStatus;
use Josephdlmd\FilamentStorybook\Stories\Documents\EmailDocument;
use Josephdlmd\FilamentStorybook\Stories\Documents\PrintedDocument;

class EmailDocumentStories extends Stories
{
    public function title(): string
    {
        return 'Actions/Email document';
    }

    public function description(): string
    {
        return 'Sending a posted document to the customer or supplier as its PDF: a gray "Email" button beside "Download PDF" on the View page. The dialog fills in the billing contact\'s email address, which the user can change, and an optional message, and says what is sent and from where; nothing is sent until the user clicks Email. The email goes from the company\'s address set on the Settings page, with the PDF from `PrintedDocument` attached, queued by `EmailDocument` since making the PDF takes a few seconds; a toast says it is on its way, and a notification says when it has gone or that it hasn\'t. Setup, once per app, as for imports (`actions-import-export`): `php artisan make:notifications-table` (on PostgreSQL, change its `data` column to `json`: Filament's bell reads it as JSON), `php artisan migrate`, `->databaseNotifications()` on the panel, and a queue worker. Emailed: a sales invoice\'s copy, a purchase order, a payment acknowledgement ("Payment received (copy)", for a collection, whose BIR form stays the official receipt) and a statement of account. Basis: the owner\'s business standards (2026-10-12: which documents, the billing contact, sent only on a click, from the company\'s address); Carbon button (one primary action; this one is gray); Carbon notifications pattern (work that takes time ends with a notification); WCAG 3.3.7 (the address is filled in, not typed again).';
    }

    public function examples(): array
    {
        return [EmailDocument::class, PrintedDocument::class];
    }

    public function records(): array
    {
        return [1 => [
            'status' => DocumentStatus::Posted,
            'number' => '001232',
            'customer' => ['name' => 'Cebu Diagnostics Laboratory', 'billing_email' => 'accounts@cebudiagnostics.ph'],
        ]];
    }

    public function default(): Action
    {
        return Action::make('email')
            ->label('Email')
            ->color('gray')
            ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::Posted)
            ->modalHeading(fn (?Model $record): string => "Email sales invoice {$record?->number}")
            ->modalDescription(fn (): string => 'Sent with its PDF attached, from '.(config('company.email') ?? 'the company\'s address in Settings').'.')
            ->fillForm(fn (?Model $record): array => ['to' => data_get($record, 'customer.billing_email')])
            ->schema([
                TextInput::make('to')
                    ->label('Email address')
                    ->email()
                    ->maxLength(254)
                    ->extraInputAttributes(['spellcheck' => 'false'])
                    ->maxWidth('md')
                    ->required(),
                Textarea::make('message')
                    ->label('Message (optional)')
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->action(function (Model $record, array $data): void {
                // On a resource: EmailDocument::dispatch(PrintedDocument::salesInvoice($record), $data['to'], $data['message'], auth()->user());
                Notification::make()
                    ->success()
                    ->title("Sending sales invoice {$record->number}")
                    ->body('You\'ll get a notification when it has gone.')
                    ->send();
            });
    }
}
