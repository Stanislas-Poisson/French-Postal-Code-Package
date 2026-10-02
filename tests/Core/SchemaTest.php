<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Core;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use StanislasPoisson\FrenchPostalCode\Core\Dataset;
use StanislasPoisson\FrenchPostalCode\Tests\Support\TableSchemaValidator;

/**
 * Every row of the data conforms to the Table Schema of its file, and the schemas describe the columns of the manifest.
 */
final class SchemaTest extends TestCase
{
    #[Test]
    public function every_row_conforms_to_the_schema_of_its_file(): void
    {
        $dataset = new Dataset;

        foreach (Dataset::TABLES as $table) {
            $schema = $this->schema($table);

            foreach ($dataset->rows($table) as $row) {
                $this->assertSame([], TableSchemaValidator::violations($schema, $row), $table . ' ' . ($row['id'] ?? ''));
            }
        }
    }

    #[Test]
    public function every_table_has_a_schema_with_the_columns_of_the_manifest(): void
    {
        $dataset = new Dataset;

        foreach (Dataset::TABLES as $table) {
            $names = array_map(static fn (array $field): string => $field['name'], $this->schema($table)['fields']);

            $this->assertSame($dataset->columns($table), $names, $table);
        }
    }

    #[Test]
    public function the_validator_reports_what_a_row_gets_wrong(): void
    {
        $schema = $this->schema('cities');

        $violations = TableSchemaValidator::violations($schema, [
            'id'                => 'abc',
            'commune_id'        => null,
            'postal_code'       => '3720',
            'latitude'          => '120.5',
            'longitude'         => '1.0',
            'address_count'     => '-1',
            'coordinate_source' => 'google',
            'valid_from'        => '2026-13-45',
        ]);

        $this->assertContains('id is not a integer: abc', $violations);
        $this->assertContains('commune_id is required', $violations);
        $this->assertContains('postal_code does not match its pattern: 3720', $violations);
        $this->assertContains('latitude is above its maximum: 120.5', $violations);
        $this->assertContains('address_count is below its minimum: -1', $violations);
        $this->assertContains('coordinate_source is not an allowed value: google', $violations);
        $this->assertContains('valid_from is not a date: 2026-13-45', $violations);
    }

    /**
     * @return array{fields: list<array{name: string, type: string, constraints?: array<string, mixed>}>}
     */
    private function schema(string $table): array
    {
        /** @var array{fields: list<array{name: string, type: string, constraints?: array<string, mixed>}>} $schema */
        $schema = json_decode((string) file_get_contents(__DIR__ . '/../../schemas/' . $table . '.schema.json'), true, 512, JSON_THROW_ON_ERROR);

        return $schema;
    }
}
