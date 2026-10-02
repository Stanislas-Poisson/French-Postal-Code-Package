<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Repository;

use Doctrine\Persistence\ManagerRegistry;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Commune;

/**
 * @extends ValidityRepository<Commune>
 */
final class CommuneRepository extends ValidityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Commune::class);
    }
}
