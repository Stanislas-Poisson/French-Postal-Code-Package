<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Repository;

use Doctrine\Persistence\ManagerRegistry;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Department;

/**
 * @extends ValidityRepository<Department>
 */
final class DepartmentRepository extends ValidityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Department::class);
    }
}
