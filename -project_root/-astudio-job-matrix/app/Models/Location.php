<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Location Model Implementation
 *
 * Requirements Applied:
 *  - Location Model: Fields - id, city, state, country.
 *  - Many-to-Many Relationship with CoreJob.
 *
 * @property int $id
 * @property string $city
 * @property string|null $state
 * @property string $country
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $full_location
 * @property-read Collection<int, CoreJob> $jobs
 * @property-read int|null $jobs_count
 * @method static Builder<static>|Location newModelQuery()
 * @method static Builder<static>|Location newQuery()
 * @method static Builder<static>|Location query()
 * @method static Builder<static>|Location whereCity($value)
 * @method static Builder<static>|Location whereCountry($value)
 * @method static Builder<static>|Location whereCreatedAt($value)
 * @method static Builder<static>|Location whereId($value)
 * @method static Builder<static>|Location whereState($value)
 * @method static Builder<static>|Location whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Location extends Model
{
    use HasFactory;

    protected $fillable = ['city', 'state', 'country'];
    private string $city;
    private string $state;
    private string $country;

    /**
     * The jobs available in this location.
     */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(CoreJob::class, 'job_location', 'location_id', 'job_id');

    }

    /**
     * Get a formatted location string.
     */
    public function getFullLocationAttribute(): string
    {
        $locationParts = array_filter([
            $this->city,
            $this->state,
            $this->country
        ]);

        return implode(', ', $locationParts);
    }
}
