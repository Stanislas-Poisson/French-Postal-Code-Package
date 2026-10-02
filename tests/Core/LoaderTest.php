<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Core;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use StanislasPoisson\FrenchPostalCode\Core\Dataset;
use StanislasPoisson\FrenchPostalCode\Core\Loader;
use StanislasPoisson\FrenchPostalCode\Core\RowCaster;
use StanislasPoisson\FrenchPostalCode\Tests\Support\InMemoryRowWriter;
use StanislasPoisson\FrenchPostalCode\Tests\Support\TemporaryDataset;

final class LoaderTest extends TestCase
{
    #[Test]
    public function it_deletes_nothing(): void
    {
        $inMemoryRowWriter                   = new InMemoryRowWriter;
        $inMemoryRowWriter->seed('cities', [99 => ['id' => 99, 'postal_code' => '99999']]);

        $temporaryDataset = TemporaryDataset::minimal();

        (new Loader(new Dataset($temporaryDataset->directory), $inMemoryRowWriter))->load();

        $this->assertSame('99999', $inMemoryRowWriter->row('cities', 99)['postal_code']);
        $this->assertSame(3, $inMemoryRowWriter->count('cities'));
    }

    #[Test]
    public function it_gives_the_values_the_type_of_their_column(): void
    {
        $inMemoryRowWriter = new InMemoryRowWriter;

        (new Loader(new Dataset, $inMemoryRowWriter))->load();

        $city = $inMemoryRowWriter->row('cities', 1);

        $this->assertSame(1, $city['id']);
        $this->assertIsFloat($city['latitude']);
        $this->assertIsInt($city['address_count']);
        $this->assertIsString($city['postal_code']);
        $this->assertNull($inMemoryRowWriter->row('regions', 1)['valid_to']);
    }

    #[Test]
    public function it_loads_every_row_of_the_data(): void
    {
        $inMemoryRowWriter = new InMemoryRowWriter;

        $loadReport = (new Loader(new Dataset, $inMemoryRowWriter))->load();

        foreach (Dataset::TABLES as $table) {
            $this->assertSame((new Dataset)->count($table), $inMemoryRowWriter->count($table), $table);
        }

        $this->assertSame($loadReport->rows(), $loadReport->added());
    }

    #[Test]
    public function it_loads_the_tables_in_the_order_of_their_relations(): void
    {
        $inMemoryRowWriter = new InMemoryRowWriter;

        (new Loader(new Dataset, $inMemoryRowWriter))->load();

        $order = array_values(array_unique(array_map(
            static fn (string $entry): string => explode(':', $entry)[1],
            array_filter($inMemoryRowWriter->log, static fn (string $entry): bool => str_starts_with($entry, 'upsert:')),
        )));

        $this->assertSame(Dataset::TABLES, $order);
    }

    #[Test]
    public function it_reports_the_rows_and_the_rows_it_added(): void
    {
        $temporaryDataset = TemporaryDataset::minimal();
        $loader           = new Loader(new Dataset($temporaryDataset->directory), new InMemoryRowWriter);

        $first  = $loader->load();
        $second = $loader->load();

        $this->assertSame(['rows' => 2, 'added' => 2], $first->tables['cities']);
        $this->assertSame(6, $first->rows());
        $this->assertSame(6, $first->added());
        $this->assertSame(6, $second->rows());
        $this->assertSame(0, $second->added());
    }

    #[Test]
    public function it_sets_the_replacement_of_a_city_once_every_city_exists(): void
    {
        $temporaryDataset  = TemporaryDataset::minimal();
        $inMemoryRowWriter = new InMemoryRowWriter;

        // One row per block: the first city is written before the city that replaces it exists.
        (new Loader(new Dataset($temporaryDataset->directory), $inMemoryRowWriter, new RowCaster, 1))->load();

        $this->assertSame(2, $inMemoryRowWriter->row('cities', 1)['replaced_by_city_id']);
        $this->assertNull($inMemoryRowWriter->row('cities', 2)['replaced_by_city_id']);
        $this->assertLessThan(
            (int) array_search('link:1', $inMemoryRowWriter->log, true),
            (int) array_search('upsert:cities:1', array_reverse($inMemoryRowWriter->log, true), true),
        );
    }

    #[Test]
    public function it_tells_which_table_was_loaded(): void
    {
        $calls            = [];
        $temporaryDataset = TemporaryDataset::minimal();

        (new Loader(new Dataset($temporaryDataset->directory), new InMemoryRowWriter))->load(
            static function (string $table, int $rows, int $added) use (&$calls): void {
                $calls[] = $table . ':' . $rows . ':' . $added;
            },
        );

        $this->assertSame(['regions:1:1', 'departments:1:1', 'communes:1:1', 'cities:2:2', 'commune_successions:1:1'], $calls);
    }

    #[Test]
    public function it_updates_a_row_that_exists_and_keeps_its_replacement_link(): void
    {
        $inMemoryRowWriter                   = new InMemoryRowWriter;
        $inMemoryRowWriter->seed('cities', [
            1 => ['id' => 1, 'label' => 'OLD', 'replaced_by_city_id' => 2],
            2 => ['id' => 2, 'label' => 'OLD', 'replaced_by_city_id' => null],
        ]);

        $temporaryDataset = TemporaryDataset::minimal();

        (new Loader(new Dataset($temporaryDataset->directory), $inMemoryRowWriter))->load();

        $this->assertSame('A', $inMemoryRowWriter->row('cities', 2)['label']);
        $this->assertSame(2, $inMemoryRowWriter->row('cities', 1)['replaced_by_city_id']);
    }

    #[Test]
    public function it_writes_each_table_in_its_own_transaction(): void
    {
        $inMemoryRowWriter = new InMemoryRowWriter;

        $temporaryDataset = TemporaryDataset::minimal();

        (new Loader(new Dataset($temporaryDataset->directory), $inMemoryRowWriter))->load();

        $this->assertSame(count(Dataset::TABLES), $inMemoryRowWriter->transactions);
    }

    #[Test]
    public function it_writes_the_rows_in_blocks_of_the_requested_size(): void
    {
        $inMemoryRowWriter = new InMemoryRowWriter;

        (new Loader(new Dataset, $inMemoryRowWriter, new RowCaster, 10000))->load();

        $blocks = array_values(array_filter($inMemoryRowWriter->log, static fn (string $entry): bool => str_starts_with($entry, 'upsert:cities:')));

        $this->assertSame(['upsert:cities:10000', 'upsert:cities:10000', 'upsert:cities:10000', 'upsert:cities:5510'], $blocks);
    }
}
