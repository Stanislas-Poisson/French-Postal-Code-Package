<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Laravel;

use Illuminate\Foundation\Application;
use Illuminate\Testing\PendingCommand;
use LogicException;
use Orchestra\Testbench\TestCase as Orchestra;
use StanislasPoisson\FrenchPostalCode\Laravel\FrenchPostalCodeServiceProvider;

abstract class TestCase extends Orchestra
{
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

        foreach (['testing', 'second'] as $connection) {
            // Foreign keys are enforced, as on MySQL and PostgreSQL.
            $app['config']->set('database.connections.' . $connection, [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ]);
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
