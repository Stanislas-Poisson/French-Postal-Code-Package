<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Core;

/**
 * What the loader needs from a database layer. Each adapter (Eloquent, Doctrine) provides one.
 */
interface RowWriter
{
    /**
     * Counts the rows of a table.
     */
    public function count(string $table): int;

    /**
     * Sets `replaced_by_city_id` of cities that already exist, once every city exists.
     * Only the rows whose value differs are written.
     *
     * @param array<int, int> $links identifier of a city => identifier of the city that replaces it
     */
    public function linkReplacedCities(array $links): void;

    /**
     * Runs the callback in a transaction: either everything it writes is kept, or nothing.
     *
     * @param callable(): void $callback
     */
    public function transaction(callable $callback): void;

    /**
     * Inserts the rows, or updates the columns listed in `$updateColumns` of those whose `id` exists.
     *
     * @param list<array<string, float|int|string|null>> $rows
     * @param list<string>                               $updateColumns
     */
    public function upsert(string $table, array $rows, array $updateColumns): void;
}
