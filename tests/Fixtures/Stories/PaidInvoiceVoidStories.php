<?php

namespace Josephdlmd\FilamentStorybook\Tests\Fixtures\Stories;

use Josephdlmd\FilamentStorybook\Stories\VoidStories;

/** The void pattern on an invoice that still has payments. */
class PaidInvoiceVoidStories extends VoidStories
{
    public function title(): string
    {
        return 'Fixtures/Paid invoice void';
    }

    public function records(): array
    {
        return [1 => ['id' => 2, 'number' => '001232', 'status' => 'posted']];
    }
}
