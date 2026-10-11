<?php

namespace Josephdlmd\FilamentStorybook\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Josephdlmd\FilamentStorybook\FilamentStorybookServiceProvider;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\FixturePanelProvider;
use Josephdlmd\FilamentStorybook\Tests\Fixtures\User;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentStorybookServiceProvider::class,
            FixturePanelProvider::class,
            \Spatie\LaravelPdf\PdfServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('company', ['name' => 'Sample Medical Trading Corporation', 'address' => '12 Rizal Avenue, Quezon City 1100', 'tin' => '123-456-789-000', 'vat' => 'VAT-registered', 'top_withholding_agent' => true]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('gadgets', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->foreignId('owner_id')->nullable();
            $table->timestamps();
        });

        Schema::create('gadget_parts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gadget_id');
            $table->string('name');
            $table->timestamps();
        });
    }
}
