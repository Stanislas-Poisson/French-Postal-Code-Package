<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Symfony;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use StanislasPoisson\FrenchPostalCode\Symfony\Doctrine\DbalRowWriter;

/**
 * The SQL of each platform, without a server: MySQL and PostgreSQL are run by the `full-data` job of the CI.
 */
final class DbalRowWriterTest extends TestCase
{
    /**
     * @return iterable<string, array{AbstractPlatform, string}>
     */
    public static function platforms(): iterable
    {
        yield 'MySQL' => [new MySQLPlatform, 'INSERT INTO `french_regions` (`id`, `code`, `valid_to`) VALUES (?, ?, ?), (?, ?, ?) ON DUPLICATE KEY UPDATE `code` = VALUES(`code`), `valid_to` = VALUES(`valid_to`)'];

        yield 'MariaDB' => [new MariaDBPlatform, 'INSERT INTO `french_regions` (`id`, `code`, `valid_to`) VALUES (?, ?, ?), (?, ?, ?) ON DUPLICATE KEY UPDATE `code` = VALUES(`code`), `valid_to` = VALUES(`valid_to`)'];

        yield 'PostgreSQL' => [new PostgreSQLPlatform, 'INSERT INTO "french_regions" ("id", "code", "valid_to") VALUES (?, ?, ?), (?, ?, ?) ON CONFLICT ("id") DO UPDATE SET "code" = EXCLUDED."code", "valid_to" = EXCLUDED."valid_to"'];

        yield 'SQLite' => [new SQLitePlatform, 'INSERT INTO "french_regions" ("id", "code", "valid_to") VALUES (?, ?, ?), (?, ?, ?) ON CONFLICT ("id") DO UPDATE SET "code" = EXCLUDED."code", "valid_to" = EXCLUDED."valid_to"'];
    }

    #[Test]
    public function it_counts_the_rows_of_a_table(): void
    {
        $connection = $this->connection(new SQLitePlatform);
        $connection->method('fetchOne')->with('SELECT COUNT(*) FROM "french_cities"')->willReturn('42');

        self::assertSame(42, (new DbalRowWriter($connection, 'french_'))->count('cities'));
    }

    #[Test]
    public function it_counts_zero_when_the_database_answers_nothing_usable(): void
    {
        $connection = $this->connection(new SQLitePlatform);
        $connection->method('fetchOne')->willReturn(false);

        self::assertSame(0, (new DbalRowWriter($connection, 'french_'))->count('cities'));
    }

    #[Test]
    public function it_refuses_a_platform_it_does_not_know(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('is not supported');

        (new DbalRowWriter($this->connection(new SQLServerPlatform), 'french_'))->upsert('regions', [['id' => 1]], []);
    }

    #[Test]
    public function it_runs_the_callback_in_a_transaction(): void
    {
        $connection = $this->connection(new SQLitePlatform);
        $connection->expects($this->once())->method('transactional')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $ran = false;

        (new DbalRowWriter($connection, 'french_'))->transaction(static function () use (&$ran): void {
            $ran = true;
        });

        self::assertTrue($ran);
    }

    #[Test]
    public function it_sets_a_replacement_only_when_it_changes(): void
    {
        $connection = $this->connection(new SQLitePlatform);
        $connection->expects($this->exactly(2))->method('executeStatement')->with(
            'UPDATE "french_cities" SET "replaced_by_city_id" = ? WHERE "id" = ? AND ("replaced_by_city_id" IS NULL OR "replaced_by_city_id" <> ?)',
            self::anything(),
            [ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::INTEGER],
        );

        (new DbalRowWriter($connection, 'french_'))->linkReplacedCities([1 => 2, 3 => 4]);
    }

    #[Test]
    public function it_writes_nothing_when_there_is_no_row(): void
    {
        $connection = $this->connection(new SQLitePlatform);
        $connection->expects($this->never())->method('executeStatement');

        (new DbalRowWriter($connection, 'french_'))->upsert('regions', [], ['code']);
    }

    #[Test]
    #[DataProvider('platforms')]
    public function it_writes_the_upsert_of_the_platform(AbstractPlatform $abstractPlatform, string $expected): void
    {
        $connection = $this->connection($abstractPlatform);
        $connection->expects($this->once())->method('executeStatement')->with(
            $expected,
            [1, '01', null, 2, '02', '2026-01-01'],
            [ParameterType::INTEGER, ParameterType::STRING, ParameterType::NULL, ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING],
        );

        (new DbalRowWriter($connection, 'french_'))->upsert(
            'regions',
            [['id' => 1, 'code' => '01', 'valid_to' => null], ['id' => 2, 'code' => '02', 'valid_to' => '2026-01-01']],
            ['code', 'valid_to'],
        );
    }

    private function connection(AbstractPlatform $abstractPlatform): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($abstractPlatform);

        return $connection;
    }
}
