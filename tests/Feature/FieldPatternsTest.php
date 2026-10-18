<?php

use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\Stories\MoneyInputStories;
use Josephdlmd\FilamentStorybook\Stories\RecordFormStories;
use Josephdlmd\FilamentStorybook\Stories\ContactStories;
use Josephdlmd\FilamentStorybook\Stories\PercentageStories;
use Josephdlmd\FilamentStorybook\Stories\TinInputStories;
use Livewire\Livewire;

it('accepts a TIN typed any reasonable way and stores one format', function (string $typed, ?string $stored): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => TinInputStories::class, 'story' => 'required'])->set('data.tin', $typed);

    $stored !== null
        ? expect($canvas->instance()->form->getState())->toBe(['tin' => $stored])
        : expect(fn () => $canvas->instance()->form->getState())->toThrow(Illuminate\Validation\ValidationException::class, 'Enter a TIN in the correct format');
})->with([
    'dashes, head office' => ['123-456-789-000', '123-456-789-000'],
    'spaces, branch' => ['123 456 789 002', '123-456-789-002'],
    'digits only' => ['123456789000', '123-456-789-000'],
    'dots and stray spaces' => [' 123.456.789.000 ', '123-456-789-000'],
    'missing branch code' => ['123-456-789', null],
    'letters' => ['ABC-456-789-000', null],
]);

it('accepts a Philippine phone number typed any reasonable way and stores it as +63', function (string $typed, ?string $stored): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => ContactStories::class, 'story' => 'phone'])->set('data.phone', $typed);

    $stored !== null
        ? expect($canvas->instance()->form->getState())->toBe(['phone' => $stored])
        : expect(fn () => $canvas->instance()->form->getState())->toThrow(Illuminate\Validation\ValidationException::class, 'Enter a phone number, like 0917 123 4567');
})->with([
    'mobile' => ['0917 123 4567', '+63 917 123 4567'],
    'mobile, +63 and dashes' => ['+63 917-123-4567', '+63 917 123 4567'],
    'mobile, +63 and the 0' => ['+63 0917 123 4567', '+63 917 123 4567'],
    'Metro Manila landline' => ['(02) 8123 4567', '+63 2 8123 4567'],
    'provincial landline' => ['(032) 123-4567', '+63 32 123 4567'],
    'area code 63' => ['(063) 221 1234', '+63 63 221 1234'],
    'too short' => ['0917 123 456', null],
    'foreign' => ['+1 415 555 0100', null],
]);

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

it('checks a TIN in its stored form, so unique() catches one typed without dashes', function (): void {
    $tin = (new Josephdlmd\FilamentStorybook\Stories\TinInputStories)->required();

    expect($tin->mutateStateForValidation('123456789000'))->toBe('123-456-789-000')
        ->and($tin->mutateStateForValidation(' 123.456 789-000 '))->toBe('123-456-789-000')
        // Not a TIN: left as typed, for the format error.
        ->and($tin->mutateStateForValidation('123-456-789'))->toBe('123-456-789');
});

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
    'an empty field that sets a message of its own for another rule' => ['tin', '', 'Enter TIN'],
    'a TIN in the wrong format' => ['tin', '12345', 'Enter a TIN in the correct format: 12 digits, including the branch code'],
]);

it('ignores spaces pasted before or after an amount', function (): void {
    $canvas = Livewire::test(StoryCanvas::class, ['stories' => Josephdlmd\FilamentStorybook\Stories\MoneyInputStories::class, 'story' => 'default'])->set('data.amount', ' 1250.50 ');

    // numeric() hands the model a number, as before.
    expect($canvas->instance()->form->getState())->toBe(['amount' => 1250.5]);
});
