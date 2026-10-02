<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use StanislasPoisson\FrenchPostalCode\Core\Dataset;
use StanislasPoisson\FrenchPostalCode\Core\RowWriter;
use StanislasPoisson\FrenchPostalCode\Laravel\Console\LoadCommand;

final class FrenchPostalCodeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // The tables are created by `php artisan migrate`. Their names and their connection come from the configuration.
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../../config/french-postal-code.php' => config_path('french-postal-code.php')], 'french-postal-code-config');

            $this->commands([LoadCommand::class]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/french-postal-code.php', 'french-postal-code');

        $this->app->singleton(Dataset::class);

        $this->app->bind(RowWriter::class, static function (Application $application): RowWriter {
            $connection = config('french-postal-code.connection');
            $prefix     = config('french-postal-code.table_prefix');

            return new EloquentRowWriter(
                $application->make('db')->connection(is_string($connection) && '' !== $connection ? $connection : null),
                is_string($prefix) ? $prefix : 'french_',
            );
        });
    }
}
