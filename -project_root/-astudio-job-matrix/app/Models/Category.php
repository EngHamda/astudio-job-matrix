<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

// for comments

/**
 * Category Model Implementation
 *
 * Requirements Applied:
 *  - Category Model: Fields - id, name.
 *  - Many-to-Many Relationship with CoreJob.
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, CoreJob> $jobs
 * @property-read int|null $jobs_count
 * @method static Builder<static>|Category newModelQuery()
 * @method static Builder<static>|Category newQuery()
 * @method static Builder<static>|Category query()
 * @method static Builder<static>|Category whereCreatedAt($value)
 * @method static Builder<static>|Category whereId($value)
 * @method static Builder<static>|Category whereName($value)
 * @method static Builder<static>|Category whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * The jobs that belong to this category.
     */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(CoreJob::class, 'job_category', 'category_id', 'job_id');

    }
}
