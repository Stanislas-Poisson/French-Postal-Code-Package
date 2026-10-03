<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Laravel;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use StanislasPoisson\FrenchPostalCode\Tests\Support\TestDatabase;

final class MigrationTest extends TestCase
{
    private const TABLES = ['regions', 'departments', 'communes', 'cities', 'commune_successions'];

    #[Test]
    public function it_can_create_the_tables_without_prefix(): void
    {
        config(['french-postal-code.table_prefix' => '']);

        $this->migrate();

        self::assertTrue(Schema::hasTable('cities'));
    }

    #[Test]
    public function it_creates_the_tables_on_the_connection_of_the_configuration(): void
    {
        config(['french-postal-code.connection' => 'second']);

        $this->migrate();

        self::assertTrue(Schema::connection('second')->hasTable('french_cities'));

        // On a server, both connections of the tests lead to the same database.
        if (TestDatabase::isSqlite()) {
            self::assertFalse(Schema::connection('testing')->hasTable('french_cities'));
        }
    }

    #[Test]
    public function it_creates_the_tables_with_the_default_prefix(): void
    {
        $this->migrate();

        foreach (self::TABLES as $table) {
            self::assertTrue(Schema::hasTable('french_' . $table), $table);
        }
    }

    #[Test]
    public function it_creates_the_tables_with_the_prefix_of_the_configuration(): void
    {
        config(['french-postal-code.table_prefix' => 'fpc_']);

        $this->migrate();

        foreach (self::TABLES as $table) {
            self::assertTrue(Schema::hasTable('fpc_' . $table), $table);
            self::assertFalse(Schema::hasTable('french_' . $table), $table);
        }
    }

    #[Test]
    public function it_drops_the_tables_when_the_migration_is_rolled_back(): void
    {
        $this->migrate();
        $this->command('migrate:rollback')->run();

        foreach (self::TABLES as $table) {
            self::assertFalse(Schema::hasTable('french_' . $table), $table);
        }
    }

    #[Test]
    public function the_database_refuses_a_city_replaced_by_a_city_that_does_not_exist(): void
    {
        $this->migrate();
        DB::table('french_regions')->insert(['id' => 1, 'code' => '01', 'name' => 'A', 'slug' => 'a', 'valid_from' => '1943-01-01']);
        DB::table('french_communes')->insert(['id' => 1, 'insee_code' => '01001', 'kind' => 'COM', 'name' => 'A', 'slug' => 'a', 'valid_from' => '1943-01-01']);

        $this->expectException(QueryException::class);

        DB::table('french_cities')->insert(['id' => 1, 'commune_id' => 1, 'postal_code' => '01400', 'valid_from' => '2026-01-01', 'replaced_by_city_id' => 2]);
    }

    #[Test]
    public function the_database_refuses_a_city_without_commune(): void
    {
        $this->migrate();

        $this->expectException(QueryException::class);

        DB::table('french_cities')->insert(['id' => 1, 'commune_id' => 999, 'postal_code' => '01400', 'valid_from' => '2026-01-01']);
    }
}
