<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * A document's one status field (`records-document-lifecycle`), with its word and colour (Carbon status indicators), so a
 * badge needs only `->badge()` and a select or filter only `->options(DocumentStatus::class)`. Copy it into `app/Enums`
 * and cast the model's `status` to it.
 */
enum DocumentStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case ForApproval = 'for_approval';
    case Posted = 'posted';
    case Void = 'void';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::ForApproval => 'For approval',
            self::Posted => 'Posted',
            self::Void => 'Void',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::ForApproval => 'info',
            self::Posted => 'success',
            self::Void => 'danger',
        };
    }
}
