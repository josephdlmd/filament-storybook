<?php

namespace Josephdlmd\FilamentStorybook\Stories\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Josephdlmd\FilamentStorybook\Stories\Documents\SalesInvoice;

/**
 * Each customer's open balance in the ageing buckets as of a date (owner's business standard: days past the due date,
 * Current, 1–30, 31–60, 61–90, over 90), worked out in the database so the report's totals row can add it. An invoice
 * dated after the date isn't in it yet; the balances are today's. Plain date comparisons, so it runs on any database.
 * Copy it into the app, next to the report's page.
 */
class ReceivablesAgeing
{
    /** The bucket columns, keyed as the query names them, with the labels of the statement of account. */
    public const COLUMNS = ['current' => 'Current', 'days_1_30' => '1–30 days', 'days_31_60' => '31–60 days', 'days_61_90' => '61–90 days', 'over_90' => 'Over 90 days'];

    /**
     * The customers' query, reading from a subquery that has the buckets as columns, so the report can sort by them and
     * its totals row (which wraps the query) can add them up.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $customers
     */
    public static function addBuckets(Builder $customers, Carbon $asOf): Builder
    {
        $model = $customers->getModel();
        $day = fn (int $daysAgo): string => $asOf->copy()->subDays($daysAgo)->toDateString();
        $issued = fn (Builder $invoices): Builder => $invoices->where('status', 'posted')->where('balance_due', '>', 0)->where('invoice_date', '<=', $asOf->toDateString());
        $open = fn (): Builder => $issued(SalesInvoice::query()
            ->selectRaw('coalesce(sum(balance_due), 0)')
            ->whereColumn('customer_id', $model->getQualifiedKeyName()));

        $withBuckets = $model->newQuery()
            ->select($model->qualifyColumn('*'))
            ->selectSub($open()->where('due_date', '>=', $day(0)), 'current')
            ->selectSub($open()->whereBetween('due_date', [$day(30), $day(1)]), 'days_1_30')
            ->selectSub($open()->whereBetween('due_date', [$day(60), $day(31)]), 'days_31_60')
            ->selectSub($open()->whereBetween('due_date', [$day(90), $day(61)]), 'days_61_90')
            ->selectSub($open()->where('due_date', '<', $day(90)), 'over_90')
            ->selectSub($open(), 'balance_due')
            ->whereHas('salesInvoices', $issued);

        return $customers->fromSub($withBuckets, $model->getTable());
    }
}
