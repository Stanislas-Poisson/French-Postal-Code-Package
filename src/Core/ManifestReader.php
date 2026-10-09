<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Core;

use JsonException;

/**
 * Reads the manifest of the data files: the date of the export, the release of the builder, the versions of the sources, and the columns and
 * the number of rows of each table.
 *
 * @phpstan-type Table array{rows: int, columns: list<string>}
 * @phpstan-type Manifest array{generated_at: string, builder_release?: string, cog_vintage: string|null, laposte_version: string|null, tables: array<string, Table>}
 */
final class ManifestReader
{
    /**
     * @return Manifest
     *
     * @throws DatasetException when the manifest is missing or is not what is expected
     */
    public function read(string $directory): array
    {
        $manifest = $this->decode(JsonFile::contents($directory . '/manifest.json'));

        $this->assertManifest($manifest);

        return $manifest;
    }

    /**
     * @phpstan-assert Manifest $manifest
     *
     * @throws DatasetException when the manifest is not what is expected
     */
    private function assertManifest(mixed $manifest): void
    {
        $isValid = is_array($manifest)
            && is_array($manifest['tables'] ?? null)
            && is_string($manifest['generated_at'] ?? null)
            && (! isset($manifest['builder_release']) || is_string($manifest['builder_release']));

        if (! $isValid) {
            throw DatasetException::invalidManifest('"generated_at" and "tables" are expected, "builder_release" must be a string');
        }
    }

    private function decode(string $json): mixed
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (JsonException $jsonException) {
            throw DatasetException::invalidManifest($jsonException->getMessage());
        }
    }
}
