<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Stories;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Josephdlmd\FilamentStorybook\Stories;
use RuntimeException;

class BrokenStories extends Stories
{
    public function title(): string
    {
        return 'Broken/Story';
    }

    public function works(): TextInput
    {
        return TextInput::make('name')->placeholder('Jane Cruz');
    }

    public function throws(): TextInput
    {
        throw new RuntimeException('Story exploded');
    }

    public function untyped()
    {
        return TextInput::make('name');
    }

    public function records(): array
    {
        return [1 => ['code' => 'A-1']];
    }

    public function failsWhileRendering(): TextColumn
    {
        return TextColumn::make('code')
            ->formatStateUsing(fn (string $state): string => $state->upper());
    }

    /** Renders, but a screen reader cannot name the button. */
    public function unnamedIconButton(): Action
    {
        return Action::make('delete')->iconButton()->icon('heroicon-o-trash')->label('');
    }

    /** Renders, but its labels and its notification break the writing rules. */
    public function vagueAndTitleCase(): Action
    {
        return Action::make('save')
            ->label('Submit')
            ->modalHeading('Save Order')
            ->modalDescription('Lorem ipsum dolor sit amet.')
            ->action(fn () => Notification::make()->title('Saved.')->send());
    }

    /** Renders while it has rows, but its row action can't take the null an empty table passes. */
    public function nonNullableRecord(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('code')])
            ->recordActions([Action::make('open')->url(fn (Model $record): string => '#'.$record->code)]);
    }

    /** Renders, but sets its own spacing instead of the theme's. */
    public function ownSpacing(): TextInput
    {
        return TextInput::make('code')->extraAttributes(['class' => 'mt-3 px-[13px]'])->extraInputAttributes(['style' => 'padding: 3px']);
    }

    /** Renders, but sets its own type and colour instead of the theme's. */
    public function ownTypeAndColour(): TextColumn
    {
        return TextColumn::make('code')->extraAttributes(['class' => 'text-lg font-bold text-red-600']);
    }

    /** Renders, but explains itself in a tooltip that shows on hover only. */
    public function hoverOnlyTooltip(): TextColumn
    {
        return TextColumn::make('code')->tooltip('The supplier\'s own code');
    }

    /** Renders, but an icon-only action has no tooltip. */
    public function iconOnlyAction(): Action
    {
        return Action::make('print')->label('Print')->icon('heroicon-o-printer')->iconButton();
    }

    /** Renders, but a success message is a callout, which isn't announced. */
    public function successCallout(): Callout
    {
        return Callout::make('Order 1042 saved')->success();
    }

    /** Renders, but a menu opens inside another menu. */
    public function nestedMenus(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('code')])
            ->recordActions([ActionGroup::make([Action::make('open'), ActionGroup::make([Action::make('archive')])])]);
    }

    /** Renders, but its options sit side by side and a wizard step's name is too long. */
    public function crampedChoices(): Wizard
    {
        return Wizard::make([
            Step::make('Choose the supplier now')->schema([
                Radio::make('terms')->options(['cod' => 'Cash on delivery', 'net_30' => '30 days'])->inline()->required(),
            ]),
        ]);
    }

    /** Renders, but a collapsible section starts open. */
    public function openCollapsible(): Section
    {
        return Section::make('Delivery')->collapsible()->schema([TextInput::make('address')]);
    }

    /** Renders, but works out money with floats. */
    public function floatMoney(): TextColumn
    {
        return TextColumn::make('total')
            ->state(fn (Model $record): float => round($record->quantity * $record->unit_price, 2));
    }
}
