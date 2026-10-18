<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Josephdlmd\FilamentStorybook\Stories;

class KeyFiguresStories extends Stories
{
    public function title(): string
    {
        return 'Records/Key figures';
    }

    public function description(): string
    {
        return 'A few figures read together at the top of a page of one task or record, such as a bank reconciliation\'s balances: a `Flex` of `TextEntry`s, each a short label over a large value, with at most one gray caption line under it that says what the figure needs ("Must be zero to complete"). They are entries of the page\'s own schema, so they change as the page does; figures that stand alone on a dashboard are the stats widgets of `dashboards-key-figures-and-charts`. Most important first, two to four of them, packed from the start (`grow(false)`) and stacked on a phone. A figure that opens the list behind it takes `->url()`. Basis: Carbon dashboards ("Place the most important at the top of the page"; "Limit the number of metrics"); Carbon chart anatomy (a key figure is "a number with a single word description"); WCAG 1.4.1 (the caption says in words what colour would).';
    }

    public function records(): array
    {
        return [1 => ['statement_balance' => '152340.75', 'book_balance' => '150120.75', 'difference' => '-2220.00']];
    }

    /** A bank reconciliation's balances: the difference says in words that it must be zero. */
    public function default(): Flex
    {
        return Flex::make([
            TextEntry::make('statement_balance')
                ->label('Statement ending balance')
                ->money('PHP')
                ->size(TextSize::Large)
                ->weight(FontWeight::SemiBold)
                ->grow(false),
            TextEntry::make('book_balance')
                ->label('Balance per books')
                ->money('PHP')
                ->size(TextSize::Large)
                ->weight(FontWeight::SemiBold)
                ->grow(false),
            TextEntry::make('difference')
                ->money('PHP')
                ->size(TextSize::Large)
                ->weight(FontWeight::SemiBold)
                ->grow(false)
                ->belowContent(Text::make('Must be zero to complete')->color('gray')),
        ])->from('sm');
    }
}
