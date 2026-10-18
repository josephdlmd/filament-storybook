<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

/** A report page whose filters apply as they change, side by side, and a button named after a product. */
class GadgetReport extends Page
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected string $view = 'filament-panels::pages.page';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->statePath('data')
            ->components([
                DatePicker::make('from')->required()->live(),
                DatePicker::make('to')->required()->live(),
                Select::make('year')->options([2025 => '2025', 2026 => '2026'])->live(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('downloadExcel')->label('Download Excel')->color('gray')];
    }
}
