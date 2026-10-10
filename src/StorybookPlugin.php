<?php

namespace Josephdlmd\FilamentStorybook;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Panel;
use Josephdlmd\FilamentStorybook\Pages\Storybook;

class StorybookPlugin implements Plugin
{
    protected bool $houseDefaults = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-storybook';
    }

    /** Filament defaults that break a sourced rule (docs/rules.md), corrected app-wide. */
    public function houseDefaults(bool $condition = true): static
    {
        $this->houseDefaults = $condition;

        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel->pages([Storybook::class]);

        if ($this->houseDefaults) {
            static::applyHouseDefaults();
        }
    }

    public function boot(Panel $panel): void {}

    public static function applyHouseDefaults(): void
    {
        // Red only on the button that confirms a loss, never on the one that opens it (GOV.UK button).
        foreach ([DeleteAction::class, ForceDeleteAction::class, DeleteBulkAction::class, ForceDeleteBulkAction::class] as $action) {
            $action::configureUsing(fn (Action $action) => $action
                ->color('gray')
                ->modalSubmitAction(fn (Action $submit): Action => $submit->color('danger')));
        }

        // Errors say what to do with the field's own label: "Enter name", not "The name field is required" (GOV.UK error message).
        Field::configureUsing(fn (Field $field) => $field->validationMessages([
            'required' => fn (Field $component): string => static::requiredMessage($component),
        ]));
    }

    public static function requiredMessage(Field $field): string
    {
        $verb = match (true) {
            $field instanceof Select, $field instanceof Radio, $field instanceof CheckboxList,
            $field instanceof ToggleButtons, $field instanceof Checkbox, $field instanceof BaseFileUpload => 'Select',
            default => 'Enter',
        };

        $label = (string) $field->getLabel();

        // Keep acronyms such as "TIN" as written; lower-case an ordinary first word.
        return $verb.' '.(preg_match('/^\p{Lu}{2}/u', $label) ? $label : lcfirst($label));
    }
}
