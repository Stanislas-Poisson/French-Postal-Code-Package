<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Core;

use JsonException;

/**
 * Reads the Table Schema of a table.
 */
final class SchemaReader
{
    /**
     * The type of each column of a table (`integer`, `number`, `date` or `string`).
     *
     * @return array<string, string>
     *
     * @throws DatasetException when the schema is missing or is not valid JSON
     */
    public function types(string $schemas, string $table): array
    {
        $json = JsonFile::contents($schemas . '/' . $table . '.schema.json');

        try {
            /** @var array{fields?: list<array{name: string, type: string}>} $schema */
            $schema = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (JsonException $jsonException) {
            throw DatasetException::invalidManifest(
                'the schema of "' . $table . '" is invalid: ' . $jsonException->getMessage(),
            );
        }

        $types = [];

        foreach ($schema['fields'] ?? [] as $field) {
            $types[$field['name']] = $field['type'];
        }

        return $types;
    }
}
