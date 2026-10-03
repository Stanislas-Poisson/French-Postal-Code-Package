<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Symfony;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use LogicException;
use StanislasPoisson\FrenchPostalCode\Core\Dataset;
use StanislasPoisson\FrenchPostalCode\Core\RowWriter;
use StanislasPoisson\FrenchPostalCode\Tests\Support\TestDatabase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class SymfonyTestCase extends KernelTestCase
{
    private static bool $cacheCleaned = false;

    /**
     * @param array<mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        // Compile the container once per run, so that what the bundle registers is exercised (and covered) every time.
        if (! self::$cacheCleaned) {
            (new Filesystem)->remove(sys_get_temp_dir() . '/french-postal-code-symfony');
            self::$cacheCleaned = true;
        }

        return new TestKernel(is_string($options['prefix'] ?? null) ? $options['prefix'] : 'french_');
    }

    /**
     * Creates the tables of the package from the mapping, as `doctrine:schema:create` or a migration would.
     * Foreign keys are enforced, as on MySQL and PostgreSQL.
     */
    protected function createTables(): void
    {
        $entityManager = $this->entityManager();
        $schemaTool    = new SchemaTool($entityManager);

        if (TestDatabase::isSqlite()) {
            $entityManager->getConnection()->executeStatement('PRAGMA foreign_keys = ON');
        }
        else {
            // A server keeps its tables from one test to the next, SQLite in memory does not.
            $schemaTool->dropDatabase();
        }

        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
    }

    protected function dataset(): Dataset
    {
        $dataset = static::getContainer()->get('french_postal_code.dataset');

        return $dataset instanceof Dataset ? $dataset : throw new LogicException('The dataset service is missing.');
    }

    protected function entityManager(): EntityManagerInterface
    {
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        return $entityManager instanceof EntityManagerInterface ? $entityManager : throw new LogicException('The entity manager is missing.');
    }

    /**
     * @param array<mixed> $options
     */
    protected function load(array $options = []): CommandTester
    {
        // The kernel is nullable in Symfony 7 and 8, and not in 6.4: ask the container, which answers the same everywhere.
        $kernel = static::getContainer()->get('kernel');

        if (! $kernel instanceof KernelInterface) {
            throw new LogicException('The kernel service is missing.');
        }

        $commandTester = new CommandTester((new Application($kernel))->find('french-postal-code:load'));
        $commandTester->execute($options);

        return $commandTester;
    }

    protected function rowWriter(): RowWriter
    {
        $rowWriter = static::getContainer()->get('french_postal_code.row_writer');

        return $rowWriter instanceof RowWriter ? $rowWriter : throw new LogicException('The row writer service is missing.');
    }

    /**
     * The first value of the first row of a query, as a number.
     */
    protected function scalar(string $sql): int
    {
        $value = $this->entityManager()->getConnection()->fetchOne($sql);

        return is_numeric($value) ? (int) $value : 0;
    }
}
