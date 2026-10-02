<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Support;

/**
 * The database the adapter tests run on: SQLite in memory by default, or a real server for the `full-data` job.
 *
 * TEST_DB is one of sqlite, mysql, mariadb or pgsql. TEST_DB_HOST, TEST_DB_PORT, TEST_DB_NAME, TEST_DB_USER and
 * TEST_DB_PASSWORD tell where the server is.
 */
final class TestDatabase
{
    /**
     * The connection for Doctrine DBAL.
     *
     * @return array<string, mixed>
     */
    public static function doctrine(): array
    {
        if (self::isSqlite()) {
            return ['driver' => 'pdo_sqlite', 'memory' => true];
        }

        return [
            'driver'   => 'pgsql' === self::driver() ? 'pdo_pgsql' : 'pdo_mysql',
            'host'     => self::env('TEST_DB_HOST', '127.0.0.1'),
            'port'     => (int) self::env('TEST_DB_PORT', self::defaultPort()),
            'dbname'   => self::env('TEST_DB_NAME', 'french_postal_code'),
            'user'     => self::env('TEST_DB_USER', 'root'),
            'password' => self::env('TEST_DB_PASSWORD', ''),
            'charset'  => 'pgsql' === self::driver() ? 'utf8' : 'utf8mb4',
        ];
    }

    public static function isSqlite(): bool
    {
        return 'sqlite' === self::driver();
    }

    /**
     * The connection for Laravel.
     *
     * @return array<string, mixed>
     */
    public static function laravel(): array
    {
        if (self::isSqlite()) {
            // Foreign keys are enforced, as on MySQL and PostgreSQL.
            return ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        }

        return [
            'driver'                  => self::driver(),
            'host'                    => self::env('TEST_DB_HOST', '127.0.0.1'),
            'port'                    => self::env('TEST_DB_PORT', self::defaultPort()),
            'database'                => self::env('TEST_DB_NAME', 'french_postal_code'),
            'username'                => self::env('TEST_DB_USER', 'root'),
            'password'                => self::env('TEST_DB_PASSWORD', ''),
            'charset'                 => 'pgsql' === self::driver() ? 'utf8' : 'utf8mb4',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ];
    }

    private static function defaultPort(): string
    {
        return 'pgsql' === self::driver() ? '5432' : '3306';
    }

    private static function driver(): string
    {
        return self::env('TEST_DB', 'sqlite');
    }

    private static function env(string $name, string $default): string
    {
        $value = getenv($name);

        return is_string($value) && '' !== $value ? $value : $default;
    }
}
