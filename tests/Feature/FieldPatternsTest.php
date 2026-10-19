<?php

use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\MoneyInputStories;
use Josephdlmd\FilamentStorybook\Stories\RecordFormStories;
use Josephdlmd\FilamentStorybook\Stories\ContactStories;
use Josephdlmd\FilamentStorybook\Stories\PercentageStories;
use Livewire\Livewire;

it('accepts a percentage with or without a typed % and up to 2 decimals', function (string $typed, ?string $stored, ?string $error = null): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => PercentageStories::class, 'story' => 'default'])->set('data.discount', $typed);

    $stored !== null
        ? expect($canvas->instance()->form->getState())->toBe(['discount' => $stored])
        : expect(fn () => $canvas->instance()->form->getState())->toThrow(Illuminate\Validation\ValidationException::class, $error);
})->with([
    'plain' => ['12.5', '12.5'],
    'typed %' => ['12.5 %', '12.5'],
    'three decimals' => ['12.125', null, 'Enter the discount with at most 2 decimal places'],
    'over 100' => ['120', null, 'Discount must be 100 or less'],
]);

it('holds amounts to centavos, unit costs to 4 decimals and quantities to 3, on the server', function (string $stories, string $field, string $typed, bool $valid): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => $stories, 'story' => 'default'])->set("data.{$field}", $typed);

    $valid
        ? expect($canvas->instance()->form->getState()[$field])->toEqual($typed)
        : expect(fn () => $canvas->instance()->form->getState())->toThrow(Illuminate\Validation\ValidationException::class, 'decimal places');
})->with([
    'amount, centavos' => [Josephdlmd\FilamentStorybook\Stories\MoneyInputStories::class, 'amount', '1250.50', true],
    'amount, a third decimal' => [Josephdlmd\FilamentStorybook\Stories\MoneyInputStories::class, 'amount', '1.234', false],
    'record form credit limit, a third decimal' => [RecordFormStories::class, 'credit_limit', '250000.555', false],
    'unit cost, 4 decimals' => [Josephdlmd\FilamentStorybook\Stories\UnitCostStories::class, 'unit_cost', '89.2857', true],
    'unit cost, a fifth decimal' => [Josephdlmd\FilamentStorybook\Stories\UnitCostStories::class, 'unit_cost', '89.28571', false],
    'quantity, 3 decimals' => [Josephdlmd\FilamentStorybook\Stories\QuantityStories::class, 'quantity', '12.125', true],
    'quantity, a fourth decimal' => [Josephdlmd\FilamentStorybook\Stories\QuantityStories::class, 'quantity', '12.1255', false],
]);

it('words every error as GOV.UK does, even on a field that sets a message of its own', function (string $field, string $typed, string $error): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => RecordFormStories::class, 'story' => 'default'])->set("data.{$field}", $typed);

    expect(fn () => $canvas->instance()->form->getState())->toThrow(Illuminate\Validation\ValidationException::class, $error);
})->with([
    'an amount that is not a number' => ['credit_limit', 'abc', 'Credit limit must be a number'],
    'an amount below zero' => ['credit_limit', '-5', 'Credit limit must be 0 or more'],
]);

it('ignores spaces pasted before or after an amount', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\MoneyInputStories::class, 'story' => 'default'])->set('data.amount', ' 1250.50 ');

    // numeric() hands the model a number, as before.
    expect($canvas->instance()->form->getState())->toBe(['amount' => 1250.5]);
});
