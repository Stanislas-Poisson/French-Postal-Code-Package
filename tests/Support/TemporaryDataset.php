<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Support;

/**
 * A small data directory written to the temporary folder, to test the reader on files that are wrong on purpose.
 */
final class TemporaryDataset
{
    public readonly string $directory;

    public function __construct()
    {
        $this->directory = sys_get_temp_dir() . '/french-postal-code-' . bin2hex(random_bytes(6));

        mkdir($this->directory);
    }

    public function __destruct()
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->directory);
    }

    /**
     * @param array<string, array{rows: int, columns: list<string>}> $tables
     */
    public function manifest(array $tables): self
    {
        return $this->write('manifest.json', json_encode(['generated_at' => '2026-10-02', 'cog_vintage' => '2026', 'laposte_version' => '2026-10', 'tables' => $tables], JSON_THROW_ON_ERROR));
    }

    public function write(string $file, string $content): self
    {
        file_put_contents($this->directory . '/' . $file, $content);

        return $this;
    }
}
