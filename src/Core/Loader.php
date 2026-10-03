<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Core;

/**
 * Loads the data into a database, whatever the framework.
 *
 * - The tables are loaded in the order of their relations.
 * - A row is inserted with its original identifier, or updated when its identifier exists: running the load twice
 *   changes nothing, and a new version of the data updates the rows and adds the new ones.
 * - Nothing is ever deleted: a closed row has a `valid_to` date, so the foreign keys of an application stay valid.
 * - Each table is loaded in one transaction.
 * - `replaced_by_city_id` points to a city that may have a higher identifier, so it is not written with the cities:
 *   it is set in a second pass, once every city exists. This keeps the database foreign key.
 */
final readonly class Loader
{
    private const REPLACED_BY = 'replaced_by_city_id';

    public function __construct(
        private Dataset $dataset,
        private RowWriter $rowWriter,
        private RowCaster $rowCaster = new RowCaster,
        private int $chunkSize = 1000,
    ) {}

    /**
     * @param (callable(string, int, int): void)|null $onTable called after each table with its name, its rows and the rows that were added
     */
    public function load(?callable $onTable = null): LoadReport
    {
        $tables = [];

        foreach (Dataset::TABLES as $table) {
            $tables[$table] = $this->loadTable($table);

            if (null !== $onTable) {
                $onTable($table, $tables[$table]['rows'], $tables[$table]['added']);
            }
        }

        return new LoadReport($tables);
    }

    /**
     * @return array{rows: int, added: int}
     */
    private function loadTable(string $table): array
    {
        $before  = $this->rowWriter->count($table);
        $types   = $this->dataset->types($table);
        $columns = array_values(array_filter(
            $this->dataset->columns($table),
            static fn (string $column): bool => 'id' !== $column
                && ('cities' !== $table || self::REPLACED_BY !== $column),
        ));
        $links = [];

        $this->rowWriter->transaction(function () use ($table, $types, $columns, &$links): void {
            foreach ($this->dataset->chunks($table, $this->chunkSize) as $chunk) {
                $rows = [];

                foreach ($chunk as $row) {
                    $cast = $this->rowCaster->cast($row, $types);

                    if ('cities' === $table && is_int($cast[self::REPLACED_BY] ?? null)) {
                        $links[(int) $cast['id']] = $cast[self::REPLACED_BY];
                        $cast[self::REPLACED_BY]  = null;
                    }

                    $rows[] = $cast;
                }

                $this->rowWriter->upsert($table, $rows, $columns);
            }

            if ([] !== $links) {
                $this->rowWriter->linkReplacedCities($links);
            }
        });

        return ['rows' => $this->dataset->count($table), 'added' => $this->rowWriter->count($table) - $before];
    }
}
