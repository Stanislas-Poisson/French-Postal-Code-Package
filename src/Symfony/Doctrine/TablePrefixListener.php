<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Region;

/**
 * Puts the prefix of the configuration in front of the name of the tables of the package.
 *
 * Many applications already own a `cities` or a `regions` table. The prefix is added when Doctrine reads the mapping,
 * so the schema tool, the migrations and the queries all see the prefixed names.
 */
final readonly class TablePrefixListener
{
    private const string ENTITIES = 'StanislasPoisson\FrenchPostalCode\Symfony\Entity\\';

    public function __construct(private string $prefix) {}

    /**
     * The namespace of the entities of the package, without the trailing separator.
     */
    public static function namespace(): string
    {
        return substr(Region::class, 0, (int) strrpos(Region::class, '\\'));
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $loadClassMetadataEventArgs): void
    {
        $classMetadata = $loadClassMetadataEventArgs->getClassMetadata();

        if (! str_starts_with($classMetadata->getName(), self::ENTITIES) || '' === $this->prefix) {
            return;
        }

        $classMetadata->setPrimaryTable([
            ...$classMetadata->table,
            'name' => $this->prefix . $classMetadata->getTableName(),
        ]);
    }
}
