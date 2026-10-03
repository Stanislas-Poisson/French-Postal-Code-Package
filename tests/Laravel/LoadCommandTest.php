<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Laravel;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use StanislasPoisson\FrenchPostalCode\Core\Dataset;
use StanislasPoisson\FrenchPostalCode\Laravel\Models\City;
use StanislasPoisson\FrenchPostalCode\Laravel\Models\Commune;
use StanislasPoisson\FrenchPostalCode\Laravel\Models\CommuneSuccession;
use StanislasPoisson\FrenchPostalCode\Laravel\Models\Department;
use StanislasPoisson\FrenchPostalCode\Laravel\Models\Region;
use StanislasPoisson\FrenchPostalCode\Tests\Support\TemporaryDataset;

final class LoadCommandTest extends TestCase
{
    #[Test]
    public function it_changes_nothing_when_it_is_run_twice(): void
    {
        $this->migrate();
        $this->command('french-postal-code:load')->assertSuccessful();

        $this->command('french-postal-code:load')->expectsOutputToContain('0 added')->assertSuccessful();

        self::assertSame((new Dataset)->count('cities'), City::query()->count());
    }

    #[Test]
    public function it_does_not_let_a_city_that_replaces_another_one_be_deleted(): void
    {
        $temporaryDataset = TemporaryDataset::minimal();
        $this->app?->instance(Dataset::class, new Dataset($temporaryDataset->directory, __DIR__ . '/../../schemas'));
        $this->migrate();
        $this->command('french-postal-code:load')->assertSuccessful();

        $this->expectException(QueryException::class);

        City::query()->findOrFail(2)->delete();
    }

    #[Test]
    public function it_gives_the_models_the_types_of_their_columns(): void
    {
        $this->migrate();
        $this->command('french-postal-code:load')->assertSuccessful();

        $city = City::query()->firstOrFail();

        self::assertSame('double', gettype($city->latitude));
        self::assertSame('integer', gettype($city->address_count));
        self::assertSame('1943-01-01', City::query()->orderBy('valid_from')->firstOrFail()->valid_from->format('Y-m-d'));
        self::assertNull($city->valid_to);
        self::assertSame('french_cities', $city->getTable());
        self::assertFalse($city->incrementing);
    }

    #[Test]
    public function it_ignores_a_chunk_that_is_not_a_number(): void
    {
        $this->migrate();

        $this->command('french-postal-code:load', ['--chunk' => 'many'])->assertSuccessful();

        self::assertSame((new Dataset)->count('regions'), Region::query()->count());
    }

    #[Test]
    public function it_keeps_only_the_valid_rows_in_the_current_scope(): void
    {
        $this->migrate();
        $this->command('french-postal-code:load')->assertSuccessful();

        $current = Commune::query()->current()->count();
        $closed  = Commune::query()->whereNotNull('valid_to')->count();

        self::assertGreaterThan(0, $closed);
        self::assertSame(Commune::query()->count() - $closed, $current);
        self::assertSame(CommuneSuccession::query()->count(), CommuneSuccession::query()->current()->count());
    }

    #[Test]
    public function it_keeps_the_rows_it_does_not_know(): void
    {
        $this->migrate();
        $this->command('french-postal-code:load')->assertSuccessful();
        $commune = Commune::query()->firstOrFail();
        City::query()->create(['id' => 999_999, 'commune_id' => $commune->id, 'postal_code' => '99999', 'valid_from' => '2026-01-01']);

        $this->command('french-postal-code:load')->assertSuccessful();

        self::assertNotNull(City::query()->find(999_999));
    }

    #[Test]
    public function it_loads_into_the_tables_of_the_configuration(): void
    {
        config(['french-postal-code.table_prefix' => 'fpc_', 'french-postal-code.connection' => 'second']);

        $this->migrate();
        $this->command('french-postal-code:load')->assertSuccessful();

        self::assertSame((new Dataset)->count('regions'), DB::connection('second')->table('fpc_regions')->count());
        self::assertSame((new Dataset)->count('regions'), Region::query()->count());
        self::assertSame('fpc_regions', (new Region)->getTable());
        self::assertSame('second', (new Region)->getConnectionName());
    }

    #[Test]
    public function it_loads_the_whole_dataset(): void
    {
        $this->migrate();

        $this->command('french-postal-code:load')
            ->expectsOutputToContain('cities')
            ->expectsOutputToContain('rows loaded')
            ->assertSuccessful();

        $dataset = new Dataset;

        self::assertSame($dataset->count('regions'), Region::query()->count());
        self::assertSame($dataset->count('departments'), Department::query()->count());
        self::assertSame($dataset->count('communes'), Commune::query()->count());
        self::assertSame($dataset->count('cities'), City::query()->count());
        self::assertSame($dataset->count('commune_successions'), CommuneSuccession::query()->count());
    }

    #[Test]
    public function it_restores_a_row_that_was_changed(): void
    {
        $this->migrate();
        $this->command('french-postal-code:load')->assertSuccessful();
        $city  = City::query()->firstOrFail();
        $label = $city->label;
        $city->update(['label' => 'CHANGED']);

        $this->command('french-postal-code:load')->assertSuccessful();

        self::assertSame($label, City::query()->findOrFail($city->id)->label);
    }

    #[Test]
    public function it_sets_the_replacement_of_a_city_that_has_a_lower_identifier(): void
    {
        $temporaryDataset = TemporaryDataset::minimal();
        $this->app?->instance(Dataset::class, new Dataset($temporaryDataset->directory, __DIR__ . '/../../schemas'));

        $this->migrate();

        // One row per statement: the city 1 is written before the city 2 that replaces it, with foreign keys enforced.
        $this->command('french-postal-code:load', ['--chunk' => 1])->assertSuccessful();

        $replaced = City::query()->findOrFail(1);

        self::assertSame(2, $replaced->replaced_by_city_id);
        self::assertSame(2, $replaced->replacedBy()->firstOrFail()->id);
        self::assertSame([2], City::query()->current()->pluck('id')->all());
    }

    #[Test]
    public function it_walks_from_a_city_up_to_its_region(): void
    {
        $this->migrate();
        $this->command('french-postal-code:load')->assertSuccessful();

        $city       = City::query()->where('postal_code', '37200')->firstOrFail();
        $commune    = $city->commune()->firstOrFail();
        $department = $commune->department()->firstOrFail();
        $region     = $department->region()->firstOrFail();

        self::assertSame('Tours', $city->commune?->name);
        self::assertSame('Centre-Val de Loire', $region->name);
        self::assertTrue($commune->cities()->whereKey($city->id)->exists());
        self::assertTrue($department->communes()->whereKey($commune->id)->exists());
        self::assertTrue($region->departments()->whereKey($department->id)->exists());
    }
}
