<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;

class RecordLockedStories extends Stories
{
    public function title(): string
    {
        return 'Records/Locked';
    }

    public function description(): string
    {
        return 'A record the user may no longer change, such as a posted entry, an approved bill or a closed period. Which statuses lock a record is a business fact: each app sets its own, ideally on its status enum, and uses that condition in place of `$record?->status === \'approved\'` in its own `app/Stories`. The policy is the lock: when nothing can change, `update()` and `delete()` return false, so Filament hides Edit and Delete and refuses the Edit page, and the record opens on its View page (`records-view-page`), whose values are read-only text. When some fields can still change (`default`), the locked fields are read-only (`readOnly()`: readable, focusable and copyable at full contrast, styled read-only by the house CSS) inside a group that is not saved, and choice fields, which have no read-only state in Filament, show their value as text. Disabled is only for something unavailable for now, such as while a process runs. Basis: Carbon read-only states (Permissions: the user may view but not modify; read-only stays navigable and passes 4.5:1; never disabled for something users must read) and disabled states (hide an action the user may not take); WCAG 1.4.3 (the contrast exemption covers disabled controls only); WAI-ARIA 1.2 aria-readonly; Filament docs (a read-only field is still submitted unless it is not saved).';
    }

    public function records(): array
    {
        return [1 => [
            'status' => 'approved',
            'reference' => 'SI-20391',
            'bill_date' => '2026-10-02',
            'payment_terms' => 'net_30',
            'amount' => '3363.71',
            'notes' => 'Delivered to the main warehouse',
        ]];
    }

    /** The edit form of a locked record whose notes can still change. */
    public function default(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Group::make([
                    TextInput::make('reference')
                        ->label('Supplier invoice number')
                        ->required()
                        ->maxWidth('xs')
                        ->readOnly(fn (?Model $record): bool => $record?->status === 'approved'),
                    DatePicker::make('bill_date')
                        ->required()
                        ->maxWidth('xs')
                        ->readOnly(fn (?Model $record): bool => $record?->status === 'approved'),
                    Radio::make('payment_terms')
                        ->options([
                            'cod' => 'Cash on delivery',
                            'net_30' => '30 days',
                        ])
                        ->required()
                        ->hidden(fn (?Model $record): bool => $record?->status === 'approved'),
                    TextEntry::make('payment_terms')
                        ->formatStateUsing(fn (string $state): string => ['cod' => 'Cash on delivery', 'net_30' => '30 days'][$state])
                        ->visible(fn (?Model $record): bool => $record?->status === 'approved'),
                    TextInput::make('amount')
                        ->numeric()
                        ->prefix('₱')
                        ->step(0.01)
                        ->rules(['decimal:0,2'])
                        ->required()
                        ->maxWidth('xs')
                        ->readOnly(fn (?Model $record): bool => $record?->status === 'approved'),
                ])
                    ->saved(fn (?Model $record): bool => $record?->status !== 'approved'),
                Textarea::make('notes')
                    ->label('Notes (optional)')
                    ->rows(3),
            ]);
    }

}
