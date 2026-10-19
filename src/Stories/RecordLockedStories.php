<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Attributes\Record;
use Josephdlmd\FilamentStorybook\Attributes\States;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\Documents\DocumentStatus;

class RecordLockedStories extends Stories
{
    public function title(): string
    {
        return 'Records/Locked';
    }

    public function description(): string
    {
        return 'A record the user may no longer change, such as a posted document, an order waiting for approval or a closed period. Which statuses lock a record is a business fact: each app sets its own, ideally on its status enum, and uses that condition in place of `$record?->status === \'for_approval\'` in its own `app/Stories`. The policy is the lock: when nothing can change, `update()` and `delete()` return false, so Filament hides Edit and Delete and refuses the Edit page, and the record opens on its View page (`records-view-page`), whose values are read-only text. When some fields can still change (`default`: an order waiting for approval, whose figures are locked so the approver decides on what was sent, while its notes stay open), the locked fields are read-only (`readOnly()`: readable, focusable and copyable at full contrast, styled read-only by the house CSS) inside a group that is not saved, and choice fields, which have no read-only state in Filament, show their value as text. Disabled is only for something unavailable for now, such as while a process runs. Basis: Carbon read-only states (Permissions: the user may view but not modify; read-only stays navigable and passes 4.5:1; never disabled for something users must read) and disabled states (hide an action the user may not take); WCAG 1.4.3 (the contrast exemption covers disabled controls only); WAI-ARIA 1.2 aria-readonly; Filament docs (a read-only field is still submitted unless it is not saved).';
    }

    public function records(): array
    {
        return [1 => [
            'status' => DocumentStatus::ForApproval,
            'supplier' => 'Abra Medical Supply',
            'order_date' => '2026-10-02',
            'payment_terms' => 'net_30',
            'total' => '3649.21',
            'notes' => 'Delivered to the main warehouse',
        ]];
    }

    /** The edit form of a locked record whose notes can still change. */
    #[Record]
    #[States('loading', 'error', 'forbidden')]
    public function default(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Group::make([
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->options([1 => 'SUP-0001 Abra Medical Supply', 2 => 'SUP-0002 Benguet Trading'])
                        ->searchable()
                        ->required()
                        ->maxWidth('md')
                        ->hidden(fn (?Model $record): bool => $record?->status === DocumentStatus::ForApproval),
                    TextEntry::make('supplier')
                        ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::ForApproval),
                    DatePicker::make('order_date')
                        ->required()
                        ->maxWidth('xs')
                        ->readOnly(fn (?Model $record): bool => $record?->status === DocumentStatus::ForApproval),
                    Radio::make('payment_terms')
                        ->options([
                            'cod' => 'Cash on delivery',
                            'net_30' => '30 days',
                            'net_60' => '60 days',
                        ])
                        ->required()
                        ->hidden(fn (?Model $record): bool => $record?->status === DocumentStatus::ForApproval),
                    TextEntry::make('payment_terms')
                        ->formatStateUsing(fn (string $state): string => ['cod' => 'Cash on delivery', 'net_30' => '30 days', 'net_60' => '60 days'][$state])
                        ->visible(fn (?Model $record): bool => $record?->status === DocumentStatus::ForApproval),
                    TextInput::make('total')
                        ->numeric()
                        ->prefix('₱')
                        ->step(0.01)
                        ->minValue(0)
                        ->rules(['decimal:0,2'])
                        ->required()
                        ->maxWidth('xs')
                        ->validationMessages(['decimal' => 'Enter the total with at most 2 decimal places, such as 3649.21'])
                        ->readOnly(fn (?Model $record): bool => $record?->status === DocumentStatus::ForApproval),
                ])
                    ->saved(fn (?Model $record): bool => $record?->status !== DocumentStatus::ForApproval),
                Textarea::make('notes')
                    ->label('Notes (optional)')
                    ->rows(3),
            ]);
    }

}
