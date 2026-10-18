<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;

class RecordNeighboursStories extends Stories
{
    public function title(): string
    {
        return 'Records/Previous and next';
    }

    public function description(): string
    {
        return 'Going through a series of records one after another, such as checking a month of sales invoices against their paper: links to the record before and after this one in number order, at the end of its View page, Previous at the start and Next at the end, each naming the record it opens ("Previous invoice 001231"). Put the `Actions` last in the resource\'s `infolist()`, below the content, and give each link the View page\'s URL. On the first record there is no Previous link and on the last no Next, never a disabled one. Numbers that sort as text (zero-padded, as `DocumentNumber` makes them) give the order; a draft without a number has no neighbours. Not for a list: that is the table\'s own pagination. Basis: GOV.UK pagination (Jan 2026: the block style "to let users navigate through related content that has been split across multiple pages"; link labels "give context on what the neighbouring pages are about"; "Do not show the previous page link on the first page – and do not show the next page link on the last page"; "Add the pagination component after the content").';
    }

    // Not imported, so the snippet's imports stay what a resource needs.
    public function model(): ?string
    {
        return Documents\SalesInvoice::class;
    }

    public function records(): array
    {
        return [1 => ['id' => 2, 'number' => '001232', 'status' => 'posted']];
    }

    /** At the end of an invoice's View page: the invoices before and after it. */
    public function default(): Actions
    {
        // The record before or after this one in number order, or null at an end of the series.
        $neighbour = fn (Model $record, bool $next): ?Model => blank($record->number) ? null : $record::query()
            ->where('number', $next ? '>' : '<', $record->number)
            ->orderBy('number', $next ? 'asc' : 'desc')
            ->first();

        return Actions::make([
            Action::make('previous')
                ->label(fn (Model $record): string => 'Previous invoice '.$neighbour($record, false)?->number)
                ->icon(Heroicon::ChevronLeft)
                ->link()
                // On the resource: SalesInvoiceResource::getUrl('view', ['record' => $neighbour($record, false)])
                ->url(fn (Model $record): string => '#'.$neighbour($record, false)?->number)
                ->visible(fn (Model $record): bool => $neighbour($record, false) !== null),
            Action::make('next')
                ->label(fn (Model $record): string => 'Next invoice '.$neighbour($record, true)?->number)
                ->icon(Heroicon::ChevronRight)
                ->iconPosition(IconPosition::After)
                ->link()
                ->url(fn (Model $record): string => '#'.$neighbour($record, true)?->number)
                ->visible(fn (Model $record): bool => $neighbour($record, true) !== null),
        ])
            ->alignBetween()
            ->key('neighbours');
    }
}
