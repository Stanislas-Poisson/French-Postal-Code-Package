<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use LogicException;
use StanislasPoisson\FrenchPostalCode\Core\RowWriter;

/**
 * Writes the rows of the loader with DBAL: one statement per block of rows.
 *
 * DBAL has no portable upsert, so the statement depends on the platform: `ON DUPLICATE KEY UPDATE` for MySQL and
 * MariaDB, `ON CONFLICT DO UPDATE` for PostgreSQL and SQLite. Another platform is refused, not guessed.
 */
final readonly class DbalRowWriter implements RowWriter
{
    public function __construct(private Connection $connection, private string $prefix) {}

    public function count(string $table): int
    {
        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM ' . $this->table($table));

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * @param array<int, int> $links identifier of a city => identifier of the city that replaces it
     */
    public function linkReplacedCities(array $links): void
    {
        $sql = sprintf(
            'UPDATE %1$s SET %2$s = ? WHERE %3$s = ? AND (%2$s IS NULL OR %2$s <> ?)',
            $this->table('cities'),
            $this->quote('replaced_by_city_id'),
            $this->quote('id'),
        );

        foreach ($links as $city => $replacement) {
            $this->connection->executeStatement(
                $sql,
                [$replacement, $city, $replacement],
                [ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::INTEGER],
            );
        }
    }

    public function transaction(callable $callback): void
    {
        $this->connection->transactional(static function () use ($callback): void {
            $callback();
        });
    }

    /**
     * @param list<array<string, float|int|string|null>> $rows
     * @param list<string>                               $updateColumns
     */
    public function upsert(string $table, array $rows, array $updateColumns): void
    {
        if ([] === $rows) {
            return;
        }

        $columns      = array_keys($rows[0]);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s%s',
            $this->table($table),
            implode(', ', array_map($this->quote(...), $columns)),
            implode(', ', array_fill(0, count($rows), $placeholders)),
            $this->onConflict($updateColumns),
        );

        $parameters = [];
        $types      = [];

        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $parameters[] = $row[$column];
                $types[]      = match (true) {
                    null === $row[$column]    => ParameterType::NULL,
                    is_int($row[$column])     => ParameterType::INTEGER,
                    default                   => ParameterType::STRING,
                };
            }
        }

        $this->connection->executeStatement($sql, $parameters, $types);
    }

    /**
     * @param list<string> $updateColumns
     */
    private function onConflict(array $updateColumns): string
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof AbstractMySQLPlatform) {
            return ' ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(
                fn (string $column): string => $this->quote($column) . ' = VALUES(' . $this->quote($column) . ')',
                $updateColumns,
            ));
        }

        if ($platform instanceof PostgreSQLPlatform || $platform instanceof SQLitePlatform) {
            return ' ON CONFLICT (' . $this->quote('id') . ') DO UPDATE SET ' . implode(', ', array_map(
                fn (string $column): string => $this->quote($column) . ' = EXCLUDED.' . $this->quote($column),
                $updateColumns,
            ));
        }

        throw new LogicException(sprintf(
            'The platform %s is not supported: the package loads data into MySQL, MariaDB, PostgreSQL and SQLite.',
            $platform::class,
        ));
    }

    private function quote(string $identifier): string
    {
        return $this->connection->getDatabasePlatform()->quoteSingleIdentifier($identifier);
    }

    private function table(string $table): string
    {
        return $this->quote($this->prefix . $table);
    }
}
