<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * The repositories of the entities that have a period of validity.
 *
 * @template T of object
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class ValidityRepository extends ServiceEntityRepository
{
    /**
     * Only the rows that are valid today.
     */
    public function current(string $alias = 'e'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)->andWhere($alias . '.validTo IS NULL');
    }
}
