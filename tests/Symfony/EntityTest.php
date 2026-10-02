<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Symfony;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use StanislasPoisson\FrenchPostalCode\Symfony\Doctrine\TablePrefixListener;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\City;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Commune;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\CommuneSuccession;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Department;
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\Region;

final class EntityTest extends SymfonyTestCase
{
    #[Test]
    public function every_accessor_of_a_loaded_row_answers(): void
    {
        self::bootKernel();
        $this->createTables();
        $this->load();
        $entityManager = $this->entityManager();

        // The first row of each entity, and a commune that was closed.
        $rows = [
            $entityManager->getRepository(Region::class)->findOneBy([]),
            $entityManager->getRepository(Department::class)->findOneBy([]),
            $entityManager->getRepository(Commune::class)->findOneBy([]),
            $entityManager->getRepository(Commune::class)->findOneBy(['inseeCode' => '85043']),
            $entityManager->getRepository(City::class)->findOneBy([]),
            $entityManager->getRepository(CommuneSuccession::class)->findOneBy([]),
        ];

        $called = 0;

        foreach ($rows as $row) {
            $this->assertIsObject($row);

            foreach ((new ReflectionClass($row))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (preg_match('/^(get|is)[A-Z]/', $method->getName()) && 0 === $method->getNumberOfRequiredParameters() && $method->getDeclaringClass()->getName() === $row::class || str_contains($row::class, '\Proxies\\')) {
                    $method->invoke($row);
                    $called++;
                }
            }
        }

        $this->assertGreaterThan(30, $called);
    }

    #[Test]
    public function the_listener_puts_the_prefix_in_front_of_the_tables_of_the_package_only(): void
    {
        self::bootKernel();
        $entityManager = $this->entityManager();

        $own = new ClassMetadata(City::class);
        $own->setPrimaryTable(['name' => 'cities']);
        $foreign = new ClassMetadata(SchemaTool::class);
        $foreign->setPrimaryTable(['name' => 'cities']);

        $listener = new TablePrefixListener('x_');
        $listener->loadClassMetadata(new LoadClassMetadataEventArgs($own, $entityManager));
        $listener->loadClassMetadata(new LoadClassMetadataEventArgs($foreign, $entityManager));

        $this->assertSame('x_cities', $own->getTableName());
        $this->assertSame('cities', $foreign->getTableName());
        $this->assertSame('StanislasPoisson\FrenchPostalCode\Symfony\Entity', TablePrefixListener::namespace());
    }
}
