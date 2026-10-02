<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Laravel;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use StanislasPoisson\FrenchPostalCode\Core\RowWriter;

/**
 * Writes the rows of the loader with the query builder of Laravel: one statement per block of rows, on any database
 * Laravel supports (MySQL, MariaDB, PostgreSQL, SQLite, SQL Server).
 */
final readonly class EloquentRowWriter implements RowWriter
{
    public function __construct(private ConnectionInterface $connection, private string $prefix) {}

    public function count(string $table): int
    {
        return $this->connection->table($this->prefix . $table)->count();
    }

    public function linkReplacedCities(array $links): void
    {
        foreach ($links as $city => $replacement) {
            $this->connection->table($this->prefix . 'cities')
                ->where('id', $city)
                ->where(static fn (Builder $query) => $query->whereNull('replaced_by_city_id')->orWhere('replaced_by_city_id', '<>', $replacement))
                ->update(['replaced_by_city_id' => $replacement]);
        }
    }

    public function transaction(callable $callback): void
    {
        $this->connection->transaction(static function () use ($callback): void {
            $callback();
        });
    }

    public function upsert(string $table, array $rows, array $updateColumns): void
    {
        $this->connection->table($this->prefix . $table)->upsert($rows, ['id'], $updateColumns);
    }
}
