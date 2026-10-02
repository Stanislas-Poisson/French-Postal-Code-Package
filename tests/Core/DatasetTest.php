<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Core;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use StanislasPoisson\FrenchPostalCode\Core\Dataset;
use StanislasPoisson\FrenchPostalCode\Core\DatasetException;
use StanislasPoisson\FrenchPostalCode\Tests\Support\TemporaryDataset;

final class DatasetTest extends TestCase
{
    #[Test]
    public function a_schema_without_fields_gives_no_type(): void
    {
        $temporaryDataset = TemporaryDataset::minimal()->write('regions.schema.json', '{}');

        $this->assertSame([], (new Dataset($temporaryDataset->directory, $temporaryDataset->directory))->types('regions'));
    }

    #[Test]
    public function it_gives_the_path_of_the_file_of_a_table(): void
    {
        $this->assertFileExists((new Dataset)->path('cities'));
    }

    #[Test]
    public function it_knows_the_columns_and_the_number_of_rows_of_a_table(): void
    {
        $dataset = new Dataset;

        $this->assertSame(['id', 'code', 'name', 'slug', 'valid_from', 'valid_to'], $dataset->columns('regions'));
        $this->assertSame(18, $dataset->count('regions'));
    }

    #[Test]
    public function it_knows_the_type_of_each_column_from_the_schema(): void
    {
        $types = (new Dataset)->types('cities');

        $this->assertSame('integer', $types['id']);
        $this->assertSame('number', $types['latitude']);
        $this->assertSame('string', $types['postal_code']);
        $this->assertSame('date', $types['valid_from']);
    }

    #[Test]
    public function it_lists_the_tables_in_the_order_they_are_loaded(): void
    {
        $this->assertSame(['regions', 'departments', 'communes', 'cities', 'commune_successions'], (new Dataset)->tables());
    }

    #[Test]
    public function it_reads_a_table_in_one_block_when_it_is_smaller_than_the_size(): void
    {
        $sizes = array_map(count(...), iterator_to_array((new Dataset)->chunks('regions'), false));

        $this->assertSame([18], $sizes);
    }

    #[Test]
    public function it_reads_the_manifest_of_the_data_shipped_with_the_package(): void
    {
        $manifest = (new Dataset)->manifest();

        $this->assertSame(Dataset::TABLES, array_keys($manifest['tables']));
        $this->assertNotSame('', $manifest['generated_at']);
        $this->assertNotNull($manifest['cog_vintage']);
    }

    #[Test]
    public function it_reads_the_rows_by_column_name_with_null_for_a_missing_value(): void
    {
        $rows = iterator_to_array((new Dataset)->rows('regions'), false);

        $this->assertCount(18, $rows);
        $this->assertSame('Guadeloupe', $rows[0]['name']);
        $this->assertNull($rows[0]['valid_to']);
    }

    #[Test]
    public function it_reads_the_rows_in_blocks_of_the_requested_size(): void
    {
        $sizes = array_map(count(...), iterator_to_array((new Dataset)->chunks('regions', 7), false));

        $this->assertSame([7, 7, 4], $sizes);
    }

    #[Test]
    public function it_refuses_a_block_size_below_one(): void
    {
        $this->expectException(InvalidArgumentException::class);

        iterator_to_array((new Dataset)->chunks('regions', 0));
    }

    #[Test]
    public function it_refuses_a_directory_without_manifest(): void
    {
        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('manifest.json" cannot be read');

        (new Dataset((new TemporaryDataset)->directory))->manifest();
    }

    #[Test]
    public function it_refuses_a_file_whose_columns_are_not_the_announced_ones(): void
    {
        $temporaryDataset = (new TemporaryDataset)
            ->manifest(['regions' => ['rows' => 1, 'columns' => ['id', 'code']]])
            ->write('regions.csv', "id,name\n1,Alsace\n");

        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('The columns of "regions" are [id, name], the manifest announces [id, code]');

        iterator_to_array((new Dataset($temporaryDataset->directory))->rows('regions'));
    }

    #[Test]
    public function it_refuses_a_manifest_that_is_not_json(): void
    {
        $temporaryDataset = (new TemporaryDataset)->write('manifest.json', '{not json');

        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('The manifest of the data is invalid');

        (new Dataset($temporaryDataset->directory))->manifest();
    }

    #[Test]
    public function it_refuses_a_manifest_that_misses_a_table(): void
    {
        $temporaryDataset = (new TemporaryDataset)->manifest(['regions' => ['rows' => 1, 'columns' => ['id']]]);

        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('the table "departments" is missing');

        (new Dataset($temporaryDataset->directory))->count('departments');
    }

    #[Test]
    public function it_refuses_a_manifest_without_tables(): void
    {
        $temporaryDataset = (new TemporaryDataset)->write('manifest.json', '{"generated_at": "2026-10-02"}');

        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('"generated_at" and "tables" are expected');

        (new Dataset($temporaryDataset->directory))->manifest();
    }

    #[Test]
    public function it_refuses_a_row_with_the_wrong_number_of_fields(): void
    {
        $temporaryDataset = (new TemporaryDataset)
            ->manifest(['regions' => ['rows' => 2, 'columns' => ['id', 'code']]])
            ->write('regions.csv', "id,code\n1,01\n2\n");

        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('Line 3 of "regions" has 1 fields, 2 expected');

        iterator_to_array((new Dataset($temporaryDataset->directory))->rows('regions'));
    }

    #[Test]
    public function it_refuses_a_schema_that_is_not_json(): void
    {
        $temporaryDataset = TemporaryDataset::minimal()->write('regions.schema.json', '{not json');

        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('the schema of "regions" is invalid');

        (new Dataset($temporaryDataset->directory, $temporaryDataset->directory))->types('regions');
    }

    #[Test]
    public function it_refuses_a_table_whose_file_is_missing(): void
    {
        $temporaryDataset = (new TemporaryDataset)->manifest(['regions' => ['rows' => 1, 'columns' => ['id']]]);

        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('regions.csv" cannot be read');

        iterator_to_array((new Dataset($temporaryDataset->directory))->rows('regions'));
    }

    #[Test]
    public function it_refuses_a_table_without_schema(): void
    {
        $temporaryDataset = TemporaryDataset::minimal();

        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('regions.schema.json" cannot be read');

        (new Dataset($temporaryDataset->directory, $temporaryDataset->directory))->types('regions');
    }

    #[Test]
    public function it_refuses_a_truncated_file(): void
    {
        $temporaryDataset = (new TemporaryDataset)
            ->manifest(['regions' => ['rows' => 3, 'columns' => ['id', 'code']]])
            ->write('regions.csv', "id,code\n1,01\n2,02\n");

        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('"regions" holds 2 rows, the manifest announces 3');

        iterator_to_array((new Dataset($temporaryDataset->directory))->rows('regions'));
    }

    #[Test]
    public function it_refuses_an_empty_file(): void
    {
        $temporaryDataset = (new TemporaryDataset)
            ->manifest(['regions' => ['rows' => 0, 'columns' => ['id']]])
            ->write('regions.csv', '');

        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('The columns of "regions" are []');

        iterator_to_array((new Dataset($temporaryDataset->directory))->rows('regions'));
    }

    #[Test]
    public function it_refuses_an_unknown_table(): void
    {
        $this->expectException(DatasetException::class);
        $this->expectExceptionMessage('Unknown table "countries"');

        (new Dataset)->path('countries');
    }

    #[Test]
    public function it_skips_blank_lines_and_reads_quoted_values(): void
    {
        $temporaryDataset = (new TemporaryDataset)
            ->manifest(['regions' => ['rows' => 2, 'columns' => ['id', 'name']]])
            ->write('regions.csv', "id,name\n1,\"Bourgogne, Franche-Comté\"\n\n2,\n");

        $rows = iterator_to_array((new Dataset($temporaryDataset->directory))->rows('regions'), false);

        $this->assertSame('Bourgogne, Franche-Comté', $rows[0]['name']);
        $this->assertNull($rows[1]['name']);
    }
}
