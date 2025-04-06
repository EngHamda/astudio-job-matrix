<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;
use Exception;


/**
 * CoreJob Model
 *
 * Manages job listings with standard fields and dynamic EAV attributes.
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $company_name
 * @property float|null $salary_min
 * @property float|null $salary_max
 * @property bool $is_remote
 * @property string $job_type
 * @property string $status
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection<int, JobAttributeValue> $jobAttributeValues
 * @property-read Collection<int, Language> $languages
 * @property-read Collection<int, Location> $locations
 * @property-read Collection<int, Category> $categories
 * @method static paginate(int $int)
 */
class CoreJob extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'core_jobs';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'title', 'description', 'company_name', 'salary_min', 'salary_max',
        'is_remote', 'job_type', 'status', 'published_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'is_remote' => 'boolean',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    /**
     * Validation rules for creating/updating jobs.
     *
     * @return array<string,string>
     */
    public static function validationRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'company_name' => 'required|string|max:255',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|gte:salary_min',
            'is_remote' => 'boolean',
            'job_type' => 'required|in:full-time,part-time,contract,freelance',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date'
        ];
    }

    // Relationship Methods,

    /**
     * Programming languages required for the job.
     * Note I add ids to `belongsToMany` as default id is `core_job_id`
     * Supports:
     * - Multiple languages per job
     * - Multiple jobs per language
     *
     * @return BelongsToMany<Language>
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'job_language', 'job_id', 'language_id');

    }

    /**
     * Possible locations for the job.
     * Supports:
     * - Multiple locations per job
     * - Multiple jobs per location
     *
     * @return BelongsToMany<Location>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'job_location', 'job_id', 'location_id');

    }

    /**
     * Categories/departments for the job.
     * Supports:
     * - Multiple categories per job
     * - Multiple jobs per category
     *
     * @return BelongsToMany<Category>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'job_category', 'job_id', 'category_id');

    }

    /**
     * Dynamic attribute values (EAV) for the job.
     * Supports:
     * - Dynamic attribute storage
     * - Flexible attribute management
     *
     * @return HasMany<JobAttributeValue>
     */
    public function jobAttributeValues(): HasMany
    {
        return $this->hasMany(JobAttributeValue::class, 'job_id');
    }

    // Dynamic Attribute Methods

    /**
     * Set a single dynamic attribute value.
     *
     * @param string $key
     * @param mixed $value
     * @return JobAttributeValue
     * @throws Throwable
     */
    public function setDynamicAttribute(string $key, mixed $value): JobAttributeValue
    {
        $attribute = Attribute::query()->where('name', $key)->first();

        if (!$attribute) {
            throw new Exception("Attribute $key does not exist.");
        }

        if (!$attribute->validateValue($value)) {
            throw new Exception("Invalid value for attribute $key.");
        }

//        throw_if(!$attribute,
//            new Exception("Attribute $key does not exist.")
//        );
//
//        throw_if(!$attribute->validateValue($value),
//            new Exception("Invalid value for attribute $key.")
//        );

        return $this->jobAttributeValues()->updateOrCreate(
            ['attribute_id' => $attribute->id],
            ['value' => $value]
        );
    }

    /**
     * Set multiple dynamic attributes at once.
     *
     * @param array<string,mixed> $attributes
     * @return Collection<string,JobAttributeValue>
     */
    public function setDynamicAttributes(array $attributes): Collection
    {
        return collect($attributes)->map(fn($value, $name) => $this->setDynamicAttribute($name, $value)
        );
    }

    /**
     * Get a single dynamic attribute value.
     *
     * @param string $key The attribute name.
     * @return mixed|null The attribute value, or null if not set.
     */
    public function getJobAttributeValue(string $key): mixed
    {
        $jobAttributeValue = $this->jobAttributeValues()
            ->whereHas('attribute', fn($query) => $query->where('name', $key))
            ->first();

        return $jobAttributeValue?->value;
    }

    /**
     * Get multiple dynamic attribute values.
     *
     * @param string[] $attributeNames
     * @return Collection<string,mixed>
     */
    public function getDynamicAttributes(array $attributeNames): Collection
    {
        return collect($attributeNames)->mapWithKeys(fn($name) => [$name => $this->getJobAttributeValue($name)]
        );
    }

}
