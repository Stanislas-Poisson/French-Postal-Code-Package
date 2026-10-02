<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Core;

use RuntimeException;

/**
 * The data of the package cannot be read, or does not match what its manifest announces.
 */
final class DatasetException extends RuntimeException
{
    public static function invalidManifest(string $reason): self
    {
        return new self('The manifest of the data is invalid: ' . $reason);
    }

    public static function malformedRow(string $table, int $line, int $expected, int $found): self
    {
        return new self(sprintf('Line %d of "%s" has %d fields, %d expected.', $line, $table, $found, $expected));
    }

    public static function missingFile(string $path): self
    {
        return new self(sprintf('The data file "%s" cannot be read.', $path));
    }

    /**
     * @param list<string> $expected
     * @param list<string> $found
     */
    public static function unexpectedColumns(string $table, array $expected, array $found): self
    {
        return new self(sprintf(
            'The columns of "%s" are [%s], the manifest announces [%s].',
            $table,
            implode(', ', $found),
            implode(', ', $expected),
        ));
    }

    public static function unexpectedRowCount(string $table, int $expected, int $found): self
    {
        return new self(sprintf('"%s" holds %d rows, the manifest announces %d.', $table, $found, $expected));
    }

    public static function unknownTable(string $table): self
    {
        return new self(sprintf('Unknown table "%s". The tables are: %s.', $table, implode(', ', Dataset::TABLES)));
    }
}
