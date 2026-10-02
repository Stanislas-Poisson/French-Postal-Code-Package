<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Symfony;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use PHPUnit\Framework\Attributes\Test;

final class SchemaTest extends SymfonyTestCase
{
    private const TABLES = ['regions', 'departments', 'communes', 'cities', 'commune_successions'];

    #[Test]
    public function it_can_map_the_tables_without_prefix(): void
    {
        self::bootKernel(['prefix' => '']);
        $this->createTables();

        $this->assertTrue($this->entityManager()->getConnection()->createSchemaManager()->tablesExist(['cities']));
    }

    #[Test]
    public function it_maps_the_five_tables_with_the_default_prefix(): void
    {
        self::bootKernel();
        $this->createTables();

        $schemaManager = $this->entityManager()->getConnection()->createSchemaManager();

        foreach (self::TABLES as $table) {
            $this->assertTrue($schemaManager->tablesExist(['french_' . $table]), $table);
        }
    }

    #[Test]
    public function it_maps_the_tables_with_the_prefix_of_the_configuration(): void
    {
        self::bootKernel(['prefix' => 'fpc_']);
        $this->createTables();

        $schemaManager = $this->entityManager()->getConnection()->createSchemaManager();

        foreach (self::TABLES as $table) {
            $this->assertTrue($schemaManager->tablesExist(['fpc_' . $table]), $table);
            $this->assertFalse($schemaManager->tablesExist(['french_' . $table]), $table);
        }
    }

    #[Test]
    public function the_database_refuses_a_city_replaced_by_a_city_that_does_not_exist(): void
    {
        self::bootKernel();
        $this->createTables();
        $connection = $this->entityManager()->getConnection();
        $connection->insert('french_communes', ['id' => 1, 'insee_code' => '01001', 'kind' => 'COM', 'name' => 'A', 'slug' => 'a', 'valid_from' => '1943-01-01']);

        $this->expectException(ForeignKeyConstraintViolationException::class);

        $connection->insert('french_cities', ['id' => 1, 'commune_id' => 1, 'postal_code' => '01400', 'valid_from' => '2026-01-01', 'replaced_by_city_id' => 2]);
    }

    #[Test]
    public function the_database_refuses_a_city_without_commune(): void
    {
        self::bootKernel();
        $this->createTables();

        $this->expectException(ForeignKeyConstraintViolationException::class);

        $this->entityManager()->getConnection()->insert('french_cities', ['id' => 1, 'commune_id' => 999, 'postal_code' => '01400', 'valid_from' => '2026-01-01']);
    }
}
