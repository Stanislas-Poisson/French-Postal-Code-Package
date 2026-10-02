<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Laravel\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use StanislasPoisson\FrenchPostalCode\Laravel\Models\Concerns\UsesFrenchPostalCodeTable;

/**
 * A French region.
 *
 * @property int                  $id
 * @property string               $code
 * @property string               $name
 * @property string               $slug
 * @property CarbonInterface      $valid_from
 * @property CarbonInterface|null $valid_to
 */
final class Region extends Model
{
    use UsesFrenchPostalCodeTable;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public static function baseTable(): string
    {
        return 'regions';
    }

    /**
     * @return HasMany<Department, $this>
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'region_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['valid_from' => 'date', 'valid_to' => 'date'];
    }
}
