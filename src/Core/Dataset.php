<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Core;

use Generator;
use InvalidArgumentException;

/**
 * Reads the data files shipped with the package, without any framework.
 *
 * Each table is a CSV file with a header. An empty value is read as null. The manifest announces the columns and the
 * number of rows of every file, and the reader checks them: a truncated or altered file is an error, not a silent
 * partial load.
 *
 * @phpstan-import-type Manifest from ManifestReader
 * @phpstan-import-type Table from ManifestReader
 */
final class Dataset
{
    /**
     * The tables, in the order they must be loaded: a table comes after the tables it refers to.
     */
    public const TABLES = ['regions', 'departments', 'communes', 'cities', 'commune_successions'];

    /**
     * @var Manifest|null
     */
    private ?array $manifest = null;

    public function __construct(
        private readonly string $directory = __DIR__ . '/../../data',
        private readonly string $schemas = __DIR__ . '/../../schemas',
    ) {}

    /**
     * The rows of a table, in blocks, for the loaders that write them in several statements.
     *
     * @return Generator<int, list<array<string, string|null>>>
     */
    public function chunks(string $table, int $size = 1000): Generator
    {
        if (1 > $size) {
            throw new InvalidArgumentException('The size of a block must be at least 1.');
        }

        $chunk = [];

        foreach ($this->rows($table) as $row) {
            $chunk[] = $row;

            if (count($chunk) === $size) {
                yield $chunk;

                $chunk = [];
            }
        }

        if ([] !== $chunk) {
            yield $chunk;
        }
    }

    /**
     * @return list<string>
     */
    public function columns(string $table): array
    {
        return $this->describe($table)['columns'];
    }

    public function count(string $table): int
    {
        return $this->describe($table)['rows'];
    }

    /**
     * The versions of the sources the data was built from, and the date of the export.
     *
     * @return Manifest
     */
    public function manifest(): array
    {
        return $this->manifest ??= (new ManifestReader())->read($this->directory);
    }

    public function path(string $table): string
    {
        $this->describe($table);

        return $this->directory . '/' . $table . '.csv';
    }

    /**
     * The rows of a table, one at a time, indexed by column name.
     *
     * @return Generator<int, array<string, string|null>>
     */
    public function rows(string $table): Generator
    {
        $description = $this->describe($table);

        yield from (new CsvRows())->rows($this->path($table), $table, $description['columns'], $description['rows']);
    }

    /**
     * @return list<string>
     */
    public function tables(): array
    {
        return self::TABLES;
    }

    /**
     * The Table Schema type of each column of a table (`integer`, `number`, `date` or `string`).
     *
     * @return array<string, string>
     */
    public function types(string $table): array
    {
        $this->describe($table);

        return (new SchemaReader())->types($this->schemas, $table);
    }

    /**
     * @return Table
     */
    private function describe(string $table): array
    {
        if (! in_array($table, self::TABLES, true)) {
            throw DatasetException::unknownTable($table);
        }

        return $this->manifest()['tables'][$table] ?? throw DatasetException::invalidManifest('the table "' . $table . '" is missing');
    }
}
