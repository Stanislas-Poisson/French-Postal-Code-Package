<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Laravel\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use StanislasPoisson\FrenchPostalCode\Laravel\Models\Concerns\UsesFrenchPostalCodeTable;

/**
 * A department, or an overseas collectivity (which has no region).
 *
 * @property int                  $id
 * @property int|null             $region_id
 * @property string               $code
 * @property string               $type
 * @property string               $name
 * @property string               $slug
 * @property CarbonInterface      $valid_from
 * @property CarbonInterface|null $valid_to
 */
final class Department extends Model
{
    use UsesFrenchPostalCodeTable;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public static function baseTable(): string
    {
        return 'departments';
    }

    /**
     * @return HasMany<Commune, $this>
     */
    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class, 'department_id');
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['valid_from' => 'date', 'valid_to' => 'date'];
    }
}
