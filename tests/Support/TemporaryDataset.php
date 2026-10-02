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
     * A tiny dataset with every table: two cities, the first one replaced by the second, which has a higher identifier.
     */
    public static function minimal(): self
    {
        $files = [
            'regions'             => "id,code,name,slug,valid_from,valid_to\n1,01,Alpha,alpha,1943-01-01,\n",
            'departments'         => "id,region_id,code,type,name,slug,valid_from,valid_to\n1,1,01,department,Ain,ain,1943-01-01,\n",
            'communes'            => "id,department_id,insee_code,kind,name,slug,centre_latitude,centre_longitude,valid_from,valid_to\n1,1,01001,COM,A,a,46.1,4.9,1943-01-01,\n",
            'cities'              => "id,commune_id,postal_code,label,latitude,longitude,address_count,coordinate_source,valid_from,valid_to,replaced_by_city_id\n1,1,01400,A,46.1,4.9,10,ban,1943-01-01,2026-06-01,2\n2,1,01401,A,46.2,4.8,0,commune_centre,2026-06-01,,\n",
            'commune_successions' => "id,from_code,to_code,kind,effective_date\n1,01001,01002,renamed,2020-01-01\n",
        ];

        $dataset = new self;
        $tables  = [];

        foreach ($files as $table => $content) {
            $dataset->write($table . '.csv', $content);

            $lines          = explode("\n", trim($content));
            $tables[$table] = ['rows' => count($lines) - 1, 'columns' => explode(',', $lines[0])];
        }

        return $dataset->manifest($tables);
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
