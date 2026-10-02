<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Laravel\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use StanislasPoisson\FrenchPostalCode\Laravel\Models\Concerns\UsesFrenchPostalCodeTable;

/**
 * A postal entry: a commune and one of its postal codes, with its own GPS point, for example "37200 Tours".
 * It is the row to reference by a foreign key: its identifier is stable and never reused.
 *
 * @property int                  $id
 * @property int                  $commune_id
 * @property string               $postal_code
 * @property string|null          $label
 * @property float|null           $latitude
 * @property float|null           $longitude
 * @property int                  $address_count
 * @property string|null          $coordinate_source
 * @property CarbonInterface      $valid_from
 * @property CarbonInterface|null $valid_to
 * @property int|null             $replaced_by_city_id
 */
final class City extends Model
{
    use UsesFrenchPostalCodeTable;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public static function baseTable(): string
    {
        return 'cities';
    }

    /**
     * @return BelongsTo<Commune, $this>
     */
    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class, 'commune_id');
    }

    /**
     * The city that replaces this one once its validity is closed.
     *
     * @return BelongsTo<City, $this>
     */
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_city_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude'      => 'float',
            'longitude'     => 'float',
            'address_count' => 'integer',
            'valid_from'    => 'date',
            'valid_to'      => 'date',
        ];
    }
}
