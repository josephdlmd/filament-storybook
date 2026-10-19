<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Josephdlmd\FilamentStorybook\Documents\DocumentStatus;

/**
 * Holding an order that would take a customer over their credit limit (owner's business standards): it can't be posted,
 * it goes For approval, and someone who may approve orders posts it or sends it back. What counts is the customer's
 * exposure: unpaid balances of posted invoices, posted orders not yet invoiced, and this order. A customer with no
 * limit set is never held. Ask `over()` to choose the order's button, and again inside the transaction that posts it,
 * with the customer locked (`lockForUpdate()`), so two orders posted at once can't both fit. Copy it into the app.
 */
class CreditLimit
{
    public static function exposure(Model $customer, string $orderTotal): string
    {
        $sum = fn (iterable $amounts): string => collect($amounts)->reduce(fn (string $sum, mixed $amount): string => bcadd($sum, (string) $amount, 2), '0.00');

        $unpaid = $sum($customer->salesInvoices()->where('status', DocumentStatus::Posted)->pluck('balance_due'));
        $uninvoiced = $sum($customer->salesOrders()->where('status', DocumentStatus::Posted)->get()->map(fn (Model $order): string => bcsub((string) $order->total, (string) $order->invoiced_total, 2)));

        return bcadd(bcadd($unpaid, $uninvoiced, 2), $orderTotal, 2);
    }

    /** How far this order would take the customer over their limit, or null when it fits. Reaching the limit exactly fits. */
    public static function over(Model $customer, string $orderTotal): ?string
    {
        if ($customer->credit_limit === null) {
            return null;
        }

        $over = bcsub(static::exposure($customer, $orderTotal), (string) $customer->credit_limit, 2);

        return bccomp($over, '0', 2) === 1 ? $over : null;
    }

    /** Why the order is held, saved on it when it is sent for approval and shown on its View page. */
    public static function reason(Model $customer, string $over): string
    {
        return Number::currency((float) $over, 'PHP')." over {$customer->name}'s ".Number::currency((float) $customer->credit_limit, 'PHP').' credit limit';
    }
}
