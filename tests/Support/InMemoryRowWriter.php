<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Support;

use LogicException;
use StanislasPoisson\FrenchPostalCode\Core\RowWriter;

/**
 * A database made of arrays that behaves like the real ones where it matters: an upsert keeps the columns it is not
 * told to update, and a replacement link is refused when its target does not exist, as a foreign key would.
 */
final class InMemoryRowWriter implements RowWriter
{
    /**
     * @var list<string>
     */
    public array $log = [];

    /**
     * @var array<string, array<int, array<string, float|int|string|null>>>
     */
    public array $tables = [];

    public int $transactions = 0;

    public function count(string $table): int
    {
        return count($this->tables[$table] ?? []);
    }

    public function linkReplacedCities(array $links): void
    {
        $this->log[] = 'link:' . count($links);

        foreach ($links as $city => $replacement) {
            if (! isset($this->tables['cities'][$replacement])) {
                throw new LogicException('Foreign key: the city ' . $replacement . ' does not exist.');
            }

            $this->tables['cities'][$city]['replaced_by_city_id'] = $replacement;
        }
    }

    /**
     * @return array<string, float|int|string|null>
     */
    public function row(string $table, int $id): array
    {
        return $this->tables[$table][$id] ?? throw new LogicException('No row ' . $id . ' in ' . $table . '.');
    }

    /**
     * Puts rows in a table before a load, as if an earlier load had written them.
     *
     * @param array<int, array<string, float|int|string|null>> $rows
     */
    public function seed(string $table, array $rows): void
    {
        $this->tables[$table] = $rows;
    }

    public function transaction(callable $callback): void
    {
        $this->transactions++;
        $this->log[] = 'transaction';

        $callback();
    }

    public function upsert(string $table, array $rows, array $updateColumns): void
    {
        $this->log[] = 'upsert:' . $table . ':' . count($rows);

        foreach ($rows as $row) {
            $id = (int) $row['id'];

            if (isset($this->tables[$table][$id])) {
                foreach ($updateColumns as $column) {
                    $this->tables[$table][$id][$column] = $row[$column];
                }

                continue;
            }

            $this->tables[$table][$id] = $row;
        }
    }
}
