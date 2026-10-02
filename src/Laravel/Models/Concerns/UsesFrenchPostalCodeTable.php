<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Laravel\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * What the models of the package share: the table and the connection come from the configuration, the identifier
 * comes from the data, and a row is valid until its `valid_to` date.
 *
 * @method static Builder<static> current()
 */
trait UsesFrenchPostalCodeTable
{
    /**
     * The table without its prefix.
     */
    abstract public static function baseTable(): string;

    public function getConnectionName(): ?string
    {
        $connection = config('french-postal-code.connection');

        return is_string($connection) && '' !== $connection ? $connection : null;
    }

    public function getTable(): string
    {
        $prefix = config('french-postal-code.table_prefix');

        return (is_string($prefix) ? $prefix : 'french_') . static::baseTable();
    }

    /**
     * Only the rows that are currently valid.
     *
     * @param Builder<static> $builder
     */
    protected function scopeCurrent(Builder $builder): void
    {
        $builder->whereNull($this->qualifyColumn('valid_to'));
    }
}
