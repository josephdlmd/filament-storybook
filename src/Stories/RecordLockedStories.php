<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
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
        return 'A record that can no longer change, such as a posted entry, an approved bill or a closed period. Which statuses lock a record is a business fact: each app sets its own, ideally on its status enum, and uses that condition in place of `$record?->status === \'approved\'` in its own `app/Stories`. The policy is the lock: when nothing can change, `update()` and `delete()` return false, so Filament hides Edit and Delete and refuses the Edit page. The record then opens on its View page, which says why first in the main column (`reason`): what state it is in and what to do instead. Never let Edit vanish without a reason. When some fields can still change (`default`), the form says what is locked, the locked fields are read-only (`readOnly()`, so they stay readable, focusable and copyable at full contrast) inside a group that is not saved, and choice fields, which have no read-only state in Filament, show their value as text. Disabled is only for a control that becomes available after the user does something on the same screen. Basis: Carbon read-only states (Locked; read-only stays navigable, text passes 4.5:1, never disabled for something users must read) and disabled states (hide what the user may not do; an inline notification says how to re-enable); WCAG 1.4.3 (the contrast exemption covers disabled controls only); WAI-ARIA 1.2 aria-readonly (still focusable and copyable); Filament docs (a read-only field is still submitted unless it is not saved).';
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
                Callout::make('This bill is approved, so only its notes can change')
                    ->description('To correct anything else, cancel the bill and record a new one')
                    ->info()
                    ->visible(fn (?Model $record): bool => $record?->status === 'approved'),
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

    /** Why a record that can't change at all has no Edit: first in the main column of its View page. */
    public function reason(): Callout
    {
        return Callout::make('This bill is approved, so it can\'t be changed')
            ->description('To correct it, cancel it and record a new bill')
            ->info()
            ->visible(fn (?Model $record): bool => $record?->status === 'approved');
    }
}
