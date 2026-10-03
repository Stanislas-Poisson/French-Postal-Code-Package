<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Command;

use StanislasPoisson\FrenchPostalCode\Core\Dataset;
use StanislasPoisson\FrenchPostalCode\Core\Loader;
use StanislasPoisson\FrenchPostalCode\Core\RowCaster;
use StanislasPoisson\FrenchPostalCode\Core\RowWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'french-postal-code:load',
    description: 'Load the French regions, departments, communes and postal codes into the database',
)]
final class LoadCommand extends Command
{
    public function __construct(private readonly Dataset $dataset, private readonly RowWriter $rowWriter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Number of rows written by statement', '1000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $chunk        = $input->getOption('chunk');

        $size         = is_numeric($chunk) ? max(1, (int) $chunk) : 1000;
        $loadReport   = (new Loader($this->dataset, $this->rowWriter, new RowCaster, $size))->load(
            static function (string $table, int $rows, int $added) use ($symfonyStyle): void {
                $symfonyStyle->writeln(sprintf(' %-22s %d rows, %d added', $table, $rows, $added));
            },
        );

        $manifest = $this->dataset->manifest();

        $symfonyStyle->success(sprintf(
            '%d rows loaded, %d added (INSEE COG %s, La Poste %s).',
            $loadReport->rows(),
            $loadReport->added(),
            $manifest['cog_vintage']     ?? 'unknown',
            $manifest['laposte_version'] ?? 'unknown',
        ));

        return Command::SUCCESS;
    }
}
