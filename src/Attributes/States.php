<?php

namespace Josephdlmd\FilamentStorybook\Attributes;

use Attribute;

/** The states a screen story shows besides populated (Stories::STATES), switched by the canvas. */
#[Attribute(Attribute::TARGET_METHOD)]
final class States
{
    /** @var list<string> */
    public array $states;

    public function __construct(string ...$states)
    {
        $this->states = array_values($states);
    }
}
