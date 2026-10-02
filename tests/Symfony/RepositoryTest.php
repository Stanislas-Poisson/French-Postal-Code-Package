<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Symfony;

use PHPUnit\Framework\Attributes\Test;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\City;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Commune;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Department;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Region;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\CityRepository;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\CommuneRepository;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\DepartmentRepository;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\RegionRepository;

final class RepositoryTest extends SymfonyTestCase
{
    #[Test]
    public function a_commune_that_was_closed_is_not_current(): void
    {
        self::bootKernel();
        $this->createTables();
        $this->load();

        $commune = $this->entityManager()->getRepository(Commune::class)->findOneBy(['inseeCode' => '85043']);

        $this->assertInstanceOf(Commune::class, $commune);
        $this->assertFalse($commune->isCurrent());
        $this->assertNotNull($commune->getValidTo());
    }

    #[Test]
    public function every_entity_has_its_repository(): void
    {
        self::bootKernel();
        $this->createTables();
        $entityManager = $this->entityManager();

        $this->assertInstanceOf(RegionRepository::class, $entityManager->getRepository(Region::class));
        $this->assertInstanceOf(DepartmentRepository::class, $entityManager->getRepository(Department::class));
        $this->assertInstanceOf(CommuneRepository::class, $entityManager->getRepository(Commune::class));
        $this->assertInstanceOf(CityRepository::class, $entityManager->getRepository(City::class));
    }

    #[Test]
    public function the_current_query_keeps_only_the_rows_that_are_valid(): void
    {
        self::bootKernel();
        $this->createTables();
        $this->load();

        $repository = $this->entityManager()->getRepository(Commune::class);
        $this->assertInstanceOf(CommuneRepository::class, $repository);

        $current = (int) $repository->current()->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();
        $closed  = (int) $repository->createQueryBuilder('e')->select('COUNT(e.id)')->where('e.validTo IS NOT NULL')->getQuery()->getSingleScalarResult();

        $this->assertGreaterThan(0, $closed);
        $this->assertSame($repository->count([]) - $closed, $current);
    }
}
