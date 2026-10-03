<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Core;

use Generator;

/**
 * Reads the rows of a CSV file with a header, and checks them against what the manifest announces.
 */
final class CsvRows
{
    /**
     * @param list<string> $columns the columns that the manifest announces
     * @param int          $rows    the number of rows that the manifest announces
     *
     * @return Generator<int, array<string, string|null>> the rows, one at a time, indexed by column name
     *
     * @throws DatasetException when the file is missing, truncated or altered
     */
    public function rows(string $path, string $table, array $columns, int $rows): Generator
    {
        $handle = is_file($path) ? fopen($path, 'rb') : false;

        if (false === $handle) {
            throw DatasetException::missingFile($path);
        }

        try {
            yield from $this->read($handle, $table, $columns, $rows);
        }
        finally {
            fclose($handle);
        }
    }

    /**
     * @param list<string> $header
     * @param list<string> $fields
     *
     * @return array<string, string|null>
     */
    private function combine(array $header, array $fields, string $table, int $line): array
    {
        if (count($fields) !== count($header)) {
            throw DatasetException::malformedRow($table, $line, count($header), count($fields));
        }

        return array_combine(
            $header,
            array_map(static fn (string $value): ?string => '' === $value ? null : $value, $fields),
        );
    }

    /**
     * @param resource $handle
     *
     * @return list<string>|null null at the end of the file
     */
    private function line($handle): ?array
    {
        do {
            $fields = fgetcsv($handle, null, ',', '"', '');
        }
        while ([null] === $fields);

        return false === $fields ? null : array_map(static fn (?string $field): string => $field ?? '', $fields);
    }

    /**
     * @param resource     $handle
     * @param list<string> $columns
     *
     * @return Generator<int, array<string, string|null>>
     */
    private function read($handle, string $table, array $columns, int $rows): Generator
    {
        $header = $this->line($handle) ?? [];

        if ($header !== $columns) {
            throw DatasetException::unexpectedColumns($table, $columns, $header);
        }

        $count = 0;

        while (null !== ($fields = $this->line($handle))) {
            yield $this->combine($header, $fields, $table, $count + 2);

            $count++;
        }

        if ($count !== $rows) {
            throw DatasetException::unexpectedRowCount($table, $rows, $count);
        }
    }
}
