<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Symfony;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use PHPUnit\Framework\Attributes\Test;
use StanislasPoisson\FrenchPostalCode\Core\Dataset;
use StanislasPoisson\FrenchPostalCode\Core\Loader;
use StanislasPoisson\FrenchPostalCode\Core\RowCaster;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\City;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Commune;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\CommuneSuccession;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Department;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Region;
use StanislasPoisson\FrenchPostalCode\Tests\Support\TemporaryDataset;

final class LoadCommandTest extends SymfonyTestCase
{
    #[Test]
    public function it_changes_nothing_when_it_is_run_twice(): void
    {
        $this->prepare();
        $this->load();

        $commandTester = $this->load();

        $this->assertStringContainsString('0 added', $commandTester->getDisplay());
        $this->assertSame((new Dataset)->count('cities'), $this->countRows(City::class));
    }

    #[Test]
    public function it_does_not_let_a_city_that_replaces_another_one_be_deleted(): void
    {
        $this->prepare();
        $temporaryDataset = TemporaryDataset::minimal();
        (new Loader(new Dataset($temporaryDataset->directory, __DIR__ . '/../../schemas'), $this->rowWriter()))->load();

        $this->expectException(ForeignKeyConstraintViolationException::class);

        $this->entityManager()->getConnection()->delete('french_cities', ['id' => 2]);
    }

    #[Test]
    public function it_gives_the_entities_the_types_of_their_columns(): void
    {
        $this->prepare();
        $this->load();

        $city = $this->entityManager()->getRepository(City::class)->find(1);

        $this->assertInstanceOf(City::class, $city);
        $this->assertSame('double', gettype($city->getLatitude()));
        $this->assertSame('integer', gettype($city->getAddressCount()));
        $this->assertSame('1943-01-01', $city->getValidFrom()->format('Y-m-d'));
        $this->assertNull($city->getValidTo());
        $this->assertTrue($city->isCurrent());
        $this->assertNull($city->getReplacedBy());
    }

    #[Test]
    public function it_ignores_a_chunk_that_is_not_a_number(): void
    {
        $this->prepare();

        $commandTester = $this->load(['--chunk' => 'many']);

        $commandTester->assertCommandIsSuccessful();
        $this->assertSame((new Dataset)->count('regions'), $this->countRows(Region::class));
    }

    #[Test]
    public function it_keeps_the_rows_it_does_not_know(): void
    {
        $this->prepare();
        $this->load();
        $connection = $this->entityManager()->getConnection();
        $commune    = $this->scalar('SELECT MIN(id) FROM french_communes');
        $connection->insert('french_cities', ['id' => 999_999, 'commune_id' => $commune, 'postal_code' => '99999', 'valid_from' => '2026-01-01']);

        $this->load();

        $this->assertSame(1, $this->scalar('SELECT COUNT(*) FROM french_cities WHERE id = 999999'));
    }

    #[Test]
    public function it_loads_into_the_tables_of_the_configuration(): void
    {
        $this->prepare('fpc_');

        $this->load();

        $this->assertSame((new Dataset)->count('regions'), $this->scalar('SELECT COUNT(*) FROM fpc_regions'));
        $this->assertSame((new Dataset)->count('regions'), $this->countRows(Region::class));
    }

    #[Test]
    public function it_loads_the_whole_dataset(): void
    {
        $this->prepare();

        $commandTester = $this->load();

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('rows loaded', $commandTester->getDisplay());
        $this->assertStringContainsString('cities', $commandTester->getDisplay());

        $dataset = new Dataset;

        $this->assertSame($dataset->count('regions'), $this->countRows(Region::class));
        $this->assertSame($dataset->count('departments'), $this->countRows(Department::class));
        $this->assertSame($dataset->count('communes'), $this->countRows(Commune::class));
        $this->assertSame($dataset->count('cities'), $this->countRows(City::class));
        $this->assertSame($dataset->count('commune_successions'), $this->countRows(CommuneSuccession::class));
    }

    #[Test]
    public function it_restores_a_row_that_was_changed(): void
    {
        $this->prepare();
        $this->load();
        $connection = $this->entityManager()->getConnection();
        $label      = $connection->fetchOne('SELECT label FROM french_cities WHERE id = 1');
        $connection->update('french_cities', ['label' => 'CHANGED'], ['id' => 1]);

        $this->load();

        $this->assertSame($label, $connection->fetchOne('SELECT label FROM french_cities WHERE id = 1'));
    }

    #[Test]
    public function it_sets_the_replacement_of_a_city_that_has_a_lower_identifier(): void
    {
        $this->prepare();
        $temporaryDataset = TemporaryDataset::minimal();

        // One row per statement: the city 1 is written before the city 2 that replaces it, with foreign keys enforced.
        (new Loader(new Dataset($temporaryDataset->directory, __DIR__ . '/../../schemas'), $this->rowWriter(), new RowCaster, 1))->load();

        $replaced = $this->entityManager()->getRepository(City::class)->find(1);

        $this->assertInstanceOf(City::class, $replaced);
        $this->assertSame(2, $replaced->getReplacedBy()?->getId());
        $this->assertFalse($replaced->isCurrent());
    }

    #[Test]
    public function it_walks_from_a_city_up_to_its_region(): void
    {
        $this->prepare();
        $this->load();

        $city = $this->entityManager()->getRepository(City::class)->findOneBy(['postalCode' => '37200']);

        $this->assertInstanceOf(City::class, $city);
        $this->assertSame('Tours', $city->getCommune()->getName());
        $this->assertSame('Centre-Val de Loire', $city->getCommune()->getDepartment()?->getRegion()?->getName());
        $this->assertTrue($city->getCommune()->getCities()->contains($city));
        $this->assertTrue($city->getCommune()->getDepartment()->getCommunes()->contains($city->getCommune()));
        $this->assertTrue($city->getCommune()->getDepartment()->getRegion()->getDepartments()->contains($city->getCommune()->getDepartment()));
    }

    /**
     * @param class-string $entity
     */
    private function countRows(string $entity): int
    {
        return $this->entityManager()->getRepository($entity)->count([]);
    }

    private function prepare(string $prefix = 'french_'): void
    {
        self::bootKernel(['prefix' => $prefix]);
        $this->createTables();
    }
}
