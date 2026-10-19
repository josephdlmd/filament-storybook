<?php

namespace Josephdlmd\FilamentStorybook\Attributes;

use Attribute;

/** A screen of one record (a View page, a record form, a wizard, a settings page): it owes every state but empty. */
#[Attribute(Attribute::TARGET_METHOD)]
final class Record {}
