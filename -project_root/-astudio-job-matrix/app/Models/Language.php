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
 * Language Model Implementation
 *
 * Requirements Applied:
 *  - Language Model: Fields - id, name.
 *  - Many-to-Many Relationship with CoreJob.
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, CoreJob> $jobs
 * @property-read int|null $jobs_count
 * @method static Builder<static>|Language newModelQuery()
 * @method static Builder<static>|Language newQuery()
 * @method static Builder<static>|Language query()
 * @method static Builder<static>|Language whereCreatedAt($value)
 * @method static Builder<static>|Language whereId($value)
 * @method static Builder<static>|Language whereName($value)
 * @method static Builder<static>|Language whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Language extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * The jobs that use this language.
     */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(CoreJob::class, 'job_language', 'language_id', 'job_id');

    }
}
