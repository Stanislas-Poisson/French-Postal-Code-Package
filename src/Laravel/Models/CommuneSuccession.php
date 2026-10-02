<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Laravel\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use StanislasPoisson\FrenchPostalCode\Laravel\Models\Concerns\UsesFrenchPostalCodeTable;

/**
 * The link between an INSEE commune code and the code that follows it at a given date.
 * It has no foreign key: codes can be reused, so a succession is found by code and date.
 *
 * @property int             $id
 * @property string          $from_code
 * @property string|null     $to_code
 * @property string          $kind
 * @property CarbonInterface $effective_date
 */
final class CommuneSuccession extends Model
{
    use UsesFrenchPostalCodeTable;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public static function baseTable(): string
    {
        return 'commune_successions';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['effective_date' => 'date'];
    }

    /**
     * A succession has no validity period: every row is current, so the scope keeps them all.
     *
     * @param Builder<static> $builder
     */
    protected function scopeCurrent(Builder $builder): void {}
}
