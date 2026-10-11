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
        });

        $schema->create('sales_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('status');
        });

        $schema->create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_invoice_id');
            $table->string('status');
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
        ]);

        // Invoice 001232 has a live payment; 001234 has only a void one.
        DB::connection('storybook')->table('payments')->insert([
            ['sales_invoice_id' => 2, 'status' => 'posted'],
            ['sales_invoice_id' => 3, 'status' => 'void'],
        ]);
    }
}
