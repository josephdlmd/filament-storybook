<?php

namespace Josephdlmd\FilamentStorybook\Attributes;

use Attribute;

/** A part of a screen that holds its own data (history, notes, related records, a widget): it owes only the states it declares. */
#[Attribute(Attribute::TARGET_METHOD)]
final class Section {}
