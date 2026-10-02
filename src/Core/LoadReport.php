<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Core;

/**
 * What a load did, table by table.
 */
final readonly class LoadReport
{
    /**
     * @param array<string, array{rows: int, added: int}> $tables rows read from the data, and rows that were not there yet
     */
    public function __construct(public array $tables) {}

    public function added(): int
    {
        return array_sum(array_column($this->tables, 'added'));
    }

    public function rows(): int
    {
        return array_sum(array_column($this->tables, 'rows'));
    }
}
