<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Laravel\Console;

use Illuminate\Console\Command;
use StanislasPoisson\FrenchPostalCode\Core\Dataset;
use StanislasPoisson\FrenchPostalCode\Core\Loader;
use StanislasPoisson\FrenchPostalCode\Core\RowCaster;
use StanislasPoisson\FrenchPostalCode\Core\RowWriter;

final class LoadCommand extends Command
{
    protected $description = 'Load the French regions, departments, communes and postal codes into the database';

    protected $signature = 'french-postal-code:load
        {--chunk=1000 : Number of rows written by statement}';

    public function handle(Dataset $dataset, RowWriter $rowWriter): int
    {
        $chunk = $this->option('chunk');

        $loadReport = (new Loader($dataset, $rowWriter, new RowCaster, is_numeric($chunk) ? max(1, (int) $chunk) : 1000))->load(
            function (string $table, int $rows, int $added): void {
                $this->components->twoColumnDetail($table, sprintf('%d rows, %d added', $rows, $added));
            },
        );

        $manifest = $dataset->manifest();

        $this->components->info(sprintf(
            '%d rows loaded, %d added (INSEE COG %s, La Poste %s).',
            $loadReport->rows(),
            $loadReport->added(),
            $manifest['cog_vintage']     ?? 'unknown',
            $manifest['laposte_version'] ?? 'unknown',
        ));

        return self::SUCCESS;
    }
}
