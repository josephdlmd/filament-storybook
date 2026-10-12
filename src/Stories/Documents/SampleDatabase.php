<?php

namespace Josephdlmd\FilamentStorybook\Stories\Documents;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The in-memory database the example models keep their sample rows in, so the Storybook page can search and
 * validate against real tables without touching the app's database.
 */
class SampleDatabase
{
    public static function boot(): void
    {
        // shortcut: the rows are built once per PHP process; a long-lived worker (Octane) that reconnects between requests would lose them.
        config(['database.connections.storybook' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);

        $schema = Schema::connection('storybook');

        if ($schema->hasTable('customers')) {
            return;
        }

        $schema->create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->string('tin')->nullable();
            $table->softDeletes();
        });

        $schema->create('sales_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->foreignId('customer_id');
            $table->string('status');
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('invoiced_total', 15, 2)->default(0);
            $table->string('approval_reason')->nullable();
        });

        $schema->create('sales_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('status');
            $table->foreignId('customer_id')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('vatable_sales', 15, 2)->default(0);
            $table->decimal('vat', 15, 2)->default(0);
            $table->decimal('zero_rated_sales', 15, 2)->default(0);
            $table->decimal('exempt_sales', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
        });

        $schema->create('withholding_codes', function (Blueprint $table): void {
            $table->string('code')->primary();
            $table->string('description');
            $table->decimal('rate', 5, 2);
            $table->string('kind');
            $table->string('withheld_by');
        });

        $schema->create('items', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('unit');
            $table->decimal('on_hand', 15, 3)->default(0);
            $table->decimal('average_cost', 15, 4)->default(0);
            $table->decimal('reorder_level', 15, 3)->nullable();
        });

        $schema->create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id');
            $table->string('document');
            $table->date('moved_on');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('on_hand', 15, 3);
        });

        $schema->create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->string('status');
            $table->string('supplier');
            $table->date('order_date');
            $table->date('due_date')->nullable();
            $table->decimal('vatable_purchases', 15, 2)->default(0);
            $table->decimal('vat', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
        });

        $schema->create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_invoice_id');
            $table->string('number')->unique();
            $table->string('status');
            $table->date('paid_on')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('tax_withheld', 15, 2)->default(0);
            $table->decimal('vat_withheld', 15, 2)->default(0);
            $table->string('reference')->nullable();
            // As on the customer's 2307: the VAT-exclusive amount withheld on, the income tax's ATC, and when it arrived.
            $table->decimal('income_payment', 15, 2)->default(0);
            $table->string('tax_atc')->nullable();
            $table->date('received_2307_on')->nullable();
        });

        DB::connection('storybook')->table('customers')->insert([
            ['code' => 'C-0001', 'name' => 'Abra Medical Supply'],
            ['code' => 'C-0002', 'name' => 'Bicol Health Centre'],
            ['code' => 'C-0003', 'name' => 'Cebu Diagnostics Laboratory'],
            ['code' => 'C-0004', 'name' => 'Davao Family Clinic'],
            ['code' => 'C-0005', 'name' => 'Iloilo Dental Group'],
            ['code' => 'C-0006', 'name' => 'Laguna Community Hospital'],
            ['code' => 'C-0007', 'name' => 'Pampanga Eye Center'],
            ['code' => 'C-0008', 'name' => 'Quezon City Wellness Pharmacy'],
        ]);

        DB::connection('storybook')->table('sales_invoices')->insert([
            ['number' => '001231', 'status' => 'void'],
            ['number' => '001232', 'status' => 'posted'],
            ['number' => '001234', 'status' => 'posted'],
            ['number' => null, 'status' => 'draft'],
        ]);

        // The reports' figures: amounts, dates and balances on the posted invoices, and a few more of them.
        $invoice = fn (int $customer, string $date, string $due, string $vatable, string $vat, string $exempt, string $total, string $balance): array => ['customer_id' => $customer, 'invoice_date' => $date, 'due_date' => $due, 'vatable_sales' => $vatable, 'vat' => $vat, 'exempt_sales' => $exempt, 'total' => $total, 'balance_due' => $balance];
        DB::connection('storybook')->table('sales_invoices')->where('number', '001232')->update($invoice(3, '2026-10-11', '2026-11-10', '3058.93', '367.07', '2900.00', '6326.00', '0.00'));
        DB::connection('storybook')->table('sales_invoices')->where('number', '001234')->update($invoice(2, '2026-10-14', '2026-11-13', '3003.31', '360.40', '0.00', '3363.71', '3363.71'));
        DB::connection('storybook')->table('sales_invoices')->insert([
            ['number' => '000901', 'status' => 'posted', ...$invoice(1, '2026-06-01', '2026-07-01', '4464.29', '535.71', '0.00', '5000.00', '5000.00')],
            ['number' => '000902', 'status' => 'posted', ...$invoice(1, '2026-08-20', '2026-09-19', '795.09', '95.41', '0.00', '890.50', '890.50')],
            ['number' => '000903', 'status' => 'posted', ...$invoice(3, '2026-09-25', '2026-10-25', '10000.00', '1200.00', '0.00', '11200.00', '11200.00')],
        ]);

        // Only the codes the business standards name; the CPA confirms the rest.
        DB::connection('storybook')->table('withholding_codes')->insert([
            ['code' => 'WC158', 'description' => 'Goods bought by a top withholding agent', 'rate' => '1.00', 'kind' => 'income', 'withheld_by' => 'us'],
            ['code' => 'WC160', 'description' => 'Services bought by a top withholding agent', 'rate' => '2.00', 'kind' => 'income', 'withheld_by' => 'us'],
            ['code' => 'WV010', 'description' => 'VAT withheld by a government office', 'rate' => '5.00', 'kind' => 'vat', 'withheld_by' => 'customer'],
        ]);

        DB::connection('storybook')->table('items')->insert([
            ['code' => 'I-0001', 'name' => 'Nitrile examination gloves, medium', 'unit' => 'boxes', 'on_hand' => '140', 'average_cost' => '212.5000', 'reorder_level' => '50'],
            ['code' => 'I-0002', 'name' => 'Insulin pens, 3 mL', 'unit' => 'packs', 'on_hand' => '8', 'average_cost' => '1105.0000', 'reorder_level' => '10'],
            ['code' => 'I-0003', 'name' => 'Isopropyl alcohol 70%, 500 mL', 'unit' => 'bottles', 'on_hand' => '36', 'average_cost' => '58.2500', 'reorder_level' => '24'],
        ]);

        DB::connection('storybook')->table('stock_movements')->insert([
            ['item_id' => 1, 'document' => 'Receiving report RR-000006', 'moved_on' => '2026-09-20', 'quantity' => '100', 'unit_cost' => '210.0000', 'on_hand' => '100'],
            ['item_id' => 1, 'document' => 'Delivery DR-000030', 'moved_on' => '2026-09-28', 'quantity' => '-40', 'unit_cost' => '210.0000', 'on_hand' => '60'],
            ['item_id' => 1, 'document' => 'Receiving report RR-000007', 'moved_on' => '2026-10-05', 'quantity' => '80', 'unit_cost' => '214.3750', 'on_hand' => '140'],
        ]);

        // Abra's credit limit, and an order of theirs posted but not yet invoiced: with its unpaid invoices, ₱205,890.50 is taken.
        DB::connection('storybook')->table('customers')->where('code', 'C-0001')->update(['credit_limit' => '250000.00']);
        DB::connection('storybook')->table('sales_orders')->insert([
            ['number' => 'SO-000011', 'customer_id' => 1, 'status' => 'posted', 'total' => '200000.00', 'invoiced_total' => '0.00'],
        ]);

        DB::connection('storybook')->table('purchase_orders')->insert([
            ['number' => 'PO-000041', 'status' => 'posted', 'supplier' => 'Benguet Trading', 'order_date' => '2026-09-28', 'due_date' => '2026-10-28', 'vatable_purchases' => '4730.36', 'vat' => '567.64', 'total' => '5298.00', 'balance_due' => '0.00'],
            ['number' => 'PO-000042', 'status' => 'posted', 'supplier' => 'Benguet Trading', 'order_date' => '2026-10-11', 'due_date' => '2026-11-10', 'vatable_purchases' => '4730.36', 'vat' => '567.64', 'total' => '5298.00', 'balance_due' => '5298.00'],
            ['number' => 'PO-000043', 'status' => 'posted', 'supplier' => 'Luzon Medical Gases', 'order_date' => '2026-08-15', 'due_date' => '2026-09-14', 'vatable_purchases' => '12500.00', 'vat' => '1500.00', 'total' => '14000.00', 'balance_due' => '7000.00'],
        ]);

        // Invoice 001232 has a live payment; 001234 has only a void one.
        DB::connection('storybook')->table('payments')->insert([
            ['sales_invoice_id' => 2, 'number' => '004516', 'status' => 'posted', 'paid_on' => '2026-11-08', 'amount' => '6298.69', 'tax_withheld' => '27.31', 'vat_withheld' => '0.00', 'reference' => 'Check 0047731', 'income_payment' => '2731.00', 'tax_atc' => 'WC158', 'received_2307_on' => null],
            ['sales_invoice_id' => 3, 'number' => '004517', 'status' => 'void', 'paid_on' => '2026-11-09', 'amount' => '3363.71', 'tax_withheld' => '0.00', 'vat_withheld' => '0.00', 'reference' => 'Cash', 'income_payment' => '0.00', 'tax_atc' => null, 'received_2307_on' => null],
            // Customer withholding for the SAWT: Abra's 2307 for July and Cebu's for October are in; Abra's for October isn't yet.
            // Invoice 001234 keeps only its void payment, for the void tests.
            ['sales_invoice_id' => 5, 'number' => '004530', 'status' => 'posted', 'paid_on' => '2026-07-15', 'amount' => '4955.36', 'tax_withheld' => '44.64', 'vat_withheld' => '0.00', 'reference' => 'Check 0045120', 'income_payment' => '4464.00', 'tax_atc' => 'WC158', 'received_2307_on' => '2026-08-10'],
            ['sales_invoice_id' => 7, 'number' => '004531', 'status' => 'posted', 'paid_on' => '2026-10-02', 'amount' => '3183.51', 'tax_withheld' => '30.03', 'vat_withheld' => '150.17', 'reference' => 'Check 0071214', 'income_payment' => '3003.31', 'tax_atc' => 'WC158', 'received_2307_on' => '2026-10-20'],
            ['sales_invoice_id' => 6, 'number' => '004532', 'status' => 'posted', 'paid_on' => '2026-10-09', 'amount' => '882.55', 'tax_withheld' => '7.95', 'vat_withheld' => '0.00', 'reference' => 'Check 0045198', 'income_payment' => '795.09', 'tax_atc' => 'WC158', 'received_2307_on' => null],
        ]);

        foreach (['C-0001' => '234-567-890-000', 'C-0002' => '345-678-901-000', 'C-0003' => '987-654-321-000'] as $code => $tin) {
            DB::connection('storybook')->table('customers')->where('code', $code)->update(['tin' => $tin]);
        }
    }
}
