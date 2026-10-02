<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use StanislasPoisson\FrenchPostalCode\Core\Dataset;

/**
 * The data shipped with the package holds together: what a loader inserts is what the foreign keys expect.
 */
final class DataIntegrityTest extends TestCase
{
    #[Test]
    public function a_city_is_unique_for_a_commune_a_postal_code_and_a_start_of_validity(): void
    {
        $keys = array_map(static fn (array $row): string => $row['commune_id'] . '|' . $row['postal_code'] . '|' . $row['valid_from'], $this->rows('cities'));

        $this->assertSame(count($keys), count(array_unique($keys)));
    }

    #[Test]
    public function a_commune_code_is_unique_for_a_given_start_of_validity(): void
    {
        $keys = array_map(static fn (array $row): string => $row['insee_code'] . '|' . $row['valid_from'], $this->rows('communes'));

        $this->assertSame(count($keys), count(array_unique($keys)));
    }

    #[Test]
    public function a_row_is_never_valid_to_a_date_before_its_start(): void
    {
        foreach (['regions', 'departments', 'communes', 'cities'] as $table) {
            foreach ($this->rows($table) as $row) {
                $this->assertTrue(null === $row['valid_to'] || $row['valid_to'] >= $row['valid_from'], $table . ' ' . $row['id']);
            }
        }
    }

    #[Test]
    public function every_city_has_a_point_and_a_known_source(): void
    {
        foreach ($this->rows('cities') as $row) {
            $this->assertNotNull($row['latitude'], 'city ' . $row['id']);
            $this->assertNotNull($row['longitude'], 'city ' . $row['id']);
            $this->assertContains($row['coordinate_source'], ['ban', 'nominatim', 'commune_centre']);
        }
    }

    #[Test]
    public function every_file_holds_the_rows_the_manifest_announces(): void
    {
        $dataset = new Dataset;

        foreach (Dataset::TABLES as $table) {
            $this->assertCount($dataset->count($table), $this->rows($table), $table);
        }
    }

    #[Test]
    public function every_relation_points_to_an_existing_row(): void
    {
        $this->assertReferences('departments', 'region_id', 'regions');
        $this->assertReferences('communes', 'department_id', 'departments');
        $this->assertReferences('cities', 'commune_id', 'communes');
        $this->assertReferences('cities', 'replaced_by_city_id', 'cities');
    }

    #[Test]
    public function identifiers_are_unique(): void
    {
        foreach (Dataset::TABLES as $table) {
            $identifiers = array_column($this->rows($table), 'id');

            $this->assertSame(count($identifiers), count(array_unique($identifiers)), $table);
        }
    }

    private function assertReferences(string $table, string $column, string $target): void
    {
        $targets = array_flip(array_map(static fn (?string $id): string => (string) $id, array_column($this->rows($target), 'id')));

        foreach ($this->rows($table) as $row) {
            $this->assertTrue(null === $row[$column] || isset($targets[$row[$column]]), sprintf('%s.%s = %s', $table, $column, (string) $row[$column]));
        }
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function rows(string $table): array
    {
        /** @var array<string, list<array<string, string|null>>> $cache */
        static $cache = [];

        return $cache[$table] ??= iterator_to_array((new Dataset)->rows($table), false);
    }
}
