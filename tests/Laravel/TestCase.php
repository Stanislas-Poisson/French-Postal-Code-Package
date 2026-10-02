<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Laravel;

use Illuminate\Foundation\Application;
use Illuminate\Testing\PendingCommand;
use LogicException;
use Orchestra\Testbench\TestCase as Orchestra;
use StanislasPoisson\FrenchPostalCode\Laravel\FrenchPostalCodeServiceProvider;
use StanislasPoisson\FrenchPostalCode\Tests\Support\TestDatabase;

abstract class TestCase extends Orchestra
{
    private const CONNECTIONS = ['testing', 'second'];

    protected function setUp(): void
    {
        parent::setUp();

        // A server keeps its tables from one test to the next, SQLite in memory does not.
        if (! TestDatabase::isSqlite()) {
            foreach (self::CONNECTIONS as $connection) {
                $this->command('db:wipe', ['--database' => $connection])->run();

                // The command disconnects the connection, and a schema builder cannot reopen it by itself.
                $this->app?->make('db')->purge($connection);
            }
        }
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function command(string $command, array $parameters = []): PendingCommand
    {
        $pendingCommand = $this->artisan($command, $parameters);

        if (! $pendingCommand instanceof PendingCommand) {
            throw new LogicException('The command "' . $command . '" was expected to be mocked by the test.');
        }

        return $pendingCommand;
    }

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');

        foreach (self::CONNECTIONS as $connection) {
            $app['config']->set('database.connections.' . $connection, TestDatabase::laravel());
        }
    }

    /**
     * @param Application $app
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [FrenchPostalCodeServiceProvider::class];
    }

    protected function migrate(): void
    {
        $this->command('migrate')->run();
    }
}
