<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Repository;

use Doctrine\Persistence\ManagerRegistry;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Region;

/**
 * @extends ValidityRepository<Region>
 */
final class RegionRepository extends ValidityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Region::class);
    }
}
