<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails a document as its PDF from the company's address (`config('company.email')`, set on the Settings page), queued
 * since making the PDF takes a few seconds, and tells the user who sent it when it has gone, or that it hasn't. Copy it
 * into the app; dispatch it with the document's data from `PrintedDocument`.
 */
class EmailDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param  array<string, mixed>  $document */
    public function __construct(public array $document, public string $to, public ?string $message, public Model $sender) {}

    public function handle(): void
    {
        $company = config('company');
        $name = "{$this->document['title']} {$this->document['number']}";

        Mail::raw(filled($this->message) ? $this->message : "Attached is {$name}.", fn (Message $mail) => $mail
            ->to($this->to)
            ->from($company['email'], $company['name'])
            ->subject("{$name} from {$company['name']}")
            ->attachData(PrintedDocument::content($this->document), PrintedDocument::fileName($this->document), ['mime' => 'application/pdf']));

        Notification::make()
            ->success()
            ->title(ucfirst("{$name} emailed to {$this->to}"))
            ->sendToDatabase($this->sender);
    }

    public function failed(?Throwable $exception): void
    {
        Notification::make()
            ->danger()
            ->title(ucfirst("{$this->document['title']} {$this->document['number']} not emailed"))
            ->body('Check the email address and send it again. If it fails again, tell your administrator.')
            ->sendToDatabase($this->sender);
    }
}
