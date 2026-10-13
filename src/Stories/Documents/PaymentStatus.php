<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * How much of a document is paid, a field of its own beside its status (`actions-record-payment`), with its word and
 * colour (Carbon status indicators). Copy it into `app/Enums` and cast the model's `payment_status` to it.
 */
enum PaymentStatus: string implements HasColor, HasLabel
{
    case Unpaid = 'unpaid';
    case PartlyPaid = 'partly_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::PartlyPaid => 'Partly paid',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unpaid => 'gray',
            self::PartlyPaid => 'info',
            self::Paid => 'success',
            self::Overdue => 'danger',
        };
    }
}
