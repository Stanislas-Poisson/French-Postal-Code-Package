<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Laravel\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use StanislasPoisson\FrenchPostalCode\Laravel\Models\Concerns\UsesFrenchPostalCodeTable;

/**
 * A commune, or a municipal arrondissement. A commune that was merged or renamed stays, with a `valid_to` date.
 *
 * @property int                  $id
 * @property int|null             $department_id
 * @property string               $insee_code
 * @property string               $kind
 * @property string               $name
 * @property string               $slug
 * @property float|null           $centre_latitude
 * @property float|null           $centre_longitude
 * @property CarbonInterface      $valid_from
 * @property CarbonInterface|null $valid_to
 */
final class Commune extends Model
{
    use UsesFrenchPostalCodeTable;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public static function baseTable(): string
    {
        return 'communes';
    }

    /**
     * @return HasMany<City, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'commune_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'centre_latitude'  => 'float',
            'centre_longitude' => 'float',
            'valid_from'       => 'date',
            'valid_to'         => 'date',
        ];
    }
}
