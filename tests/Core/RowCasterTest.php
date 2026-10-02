<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Core;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use StanislasPoisson\FrenchPostalCode\Core\LoadReport;
use StanislasPoisson\FrenchPostalCode\Core\RowCaster;

final class RowCasterTest extends TestCase
{
    #[Test]
    public function a_load_report_adds_up_its_tables(): void
    {
        $loadReport = new LoadReport(['regions' => ['rows' => 18, 'added' => 18], 'cities' => ['rows' => 10, 'added' => 2]]);

        $this->assertSame(28, $loadReport->rows());
        $this->assertSame(20, $loadReport->added());
    }

    #[Test]
    public function it_gives_each_value_the_type_of_its_column(): void
    {
        $cast = (new RowCaster)->cast(
            ['id' => '12', 'latitude' => '46.5', 'postal_code' => '01400', 'valid_from' => '2026-01-01', 'valid_to' => null, 'other' => '7'],
            ['id' => 'integer', 'latitude' => 'number', 'postal_code' => 'string', 'valid_from' => 'date', 'valid_to' => 'date'],
        );

        $this->assertSame(['id' => 12, 'latitude' => 46.5, 'postal_code' => '01400', 'valid_from' => '2026-01-01', 'valid_to' => null, 'other' => '7'], $cast);
    }
}
