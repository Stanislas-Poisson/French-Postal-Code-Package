<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Core;

use Generator;
use InvalidArgumentException;
use JsonException;

/**
 * Reads the data files shipped with the package, without any framework.
 *
 * Each table is a CSV file with a header. An empty value is read as null. The manifest announces the columns and the
 * number of rows of every file, and the reader checks them: a truncated or altered file is an error, not a silent
 * partial load.
 */
final class Dataset
{
    /**
     * The tables, in the order they must be loaded: a table comes after the tables it refers to.
     */
    public const TABLES = ['regions', 'departments', 'communes', 'cities', 'commune_successions'];

    /**
     * @var array{generated_at: string, cog_vintage: string|null, laposte_version: string|null, tables: array<string, array{rows: int, columns: list<string>}>}|null
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
     * @return array{generated_at: string, cog_vintage: string|null, laposte_version: string|null, tables: array<string, array{rows: int, columns: list<string>}>}
     */
    public function manifest(): array
    {
        return $this->manifest ??= $this->readManifest();
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
        $path        = $this->path($table);
        $handle      = @fopen($path, 'rb');

        if (false === $handle) {
            throw DatasetException::missingFile($path);
        }

        try {
            $header = $this->read($handle);

            if ($header !== $description['columns']) {
                throw DatasetException::unexpectedColumns($table, $description['columns'], $header ?? []);
            }

            $count = 0;
            $line  = 1;

            while (null !== ($fields = $this->read($handle))) {
                $line++;

                if (count($fields) !== count($header)) {
                    throw DatasetException::malformedRow($table, $line, count($header), count($fields));
                }

                $count++;

                yield array_combine($header, array_map(static fn (string $value): ?string => '' === $value ? null : $value, $fields));
            }

            if ($count !== $description['rows']) {
                throw DatasetException::unexpectedRowCount($table, $description['rows'], $count);
            }
        }
        finally {
            fclose($handle);
        }
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

        $path = $this->schemas . '/' . $table . '.schema.json';
        $json = @file_get_contents($path);

        if (false === $json) {
            throw DatasetException::missingFile($path);
        }

        try {
            /** @var array{fields?: list<array{name: string, type: string}>} $schema */
            $schema = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (JsonException $jsonException) {
            throw DatasetException::invalidManifest('the schema of "' . $table . '" is invalid: ' . $jsonException->getMessage());
        }

        $types = [];

        foreach ($schema['fields'] ?? [] as $field) {
            $types[$field['name']] = $field['type'];
        }

        return $types;
    }

    /**
     * @return array{rows: int, columns: list<string>}
     */
    private function describe(string $table): array
    {
        if (! in_array($table, self::TABLES, true)) {
            throw DatasetException::unknownTable($table);
        }

        return $this->manifest()['tables'][$table] ?? throw DatasetException::invalidManifest('the table "' . $table . '" is missing');
    }

    /**
     * @param resource $handle
     *
     * @return list<string>|null null at the end of the file
     */
    private function read($handle): ?array
    {
        do {
            $fields = fgetcsv($handle, null, ',', '"', '');
        }
        while ([null] === $fields);

        if (false === $fields) {
            return null;
        }

        return array_map(static fn (?string $field): string => $field ?? '', $fields);
    }

    /**
     * @return array{generated_at: string, cog_vintage: string|null, laposte_version: string|null, tables: array<string, array{rows: int, columns: list<string>}>}
     */
    private function readManifest(): array
    {
        $path = $this->directory . '/manifest.json';
        $json = @file_get_contents($path);

        if (false === $json) {
            throw DatasetException::missingFile($path);
        }

        try {
            $manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (JsonException $jsonException) {
            throw DatasetException::invalidManifest($jsonException->getMessage());
        }

        if (! is_array($manifest) || ! is_array($manifest['tables'] ?? null) || ! is_string($manifest['generated_at'] ?? null)) {
            throw DatasetException::invalidManifest('"generated_at" and "tables" are expected');
        }

        /** @var array{generated_at: string, cog_vintage: string|null, laposte_version: string|null, tables: array<string, array{rows: int, columns: list<string>}>} $manifest */
        return $manifest;
    }
}
