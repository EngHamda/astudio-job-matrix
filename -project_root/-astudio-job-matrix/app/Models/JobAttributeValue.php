<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * JobAttributeValue Model Implementation
 *
 * Requirements Applied:
 *  - Supports Dynamic Attribute Handling for CoreJob via the EAV Implementation.
 *  - Provides flexible dynamic attribute value casting (number, boolean, date, select).
 *
 * @property int $id
 * @property int $job_id
 * @property int $attribute_id
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Attribute $attribute
 * @property-read mixed $casted_value
 * @property-read CoreJob $job
 * @method static Builder<static>|JobAttributeValue newModelQuery()
 * @method static Builder<static>|JobAttributeValue newQuery()
 * @method static Builder<static>|JobAttributeValue query()
 * @method static Builder<static>|JobAttributeValue whereAttributeId($value)
 * @method static Builder<static>|JobAttributeValue whereCreatedAt($value)
 * @method static Builder<static>|JobAttributeValue whereId($value)
 * @method static Builder<static>|JobAttributeValue whereJobId($value)
 * @method static Builder<static>|JobAttributeValue whereUpdatedAt($value)
 * @method static Builder<static>|JobAttributeValue whereValue($value)
 * @mixin Eloquent
 */
class JobAttributeValue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'job_id',
        'attribute_id',
        'value'
    ];

    /**
     * Relationship to the CoreJob model.
     *
     * @return BelongsTo
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(CoreJob::class, 'job_id');
    }

    /**
     * Relationship to the Attribute model.
     *
     * @return BelongsTo
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Cast the value based on the attribute type.
     *
     * @return mixed
     */
    public function getCastedValueAttribute(): mixed
    {
        // If no attribute is associated, return the raw value
        if (!$this->attribute) {
            return $this->value;
        }

        return match ($this->attribute->type) {
            'number' => $this->castNumber($this->value),
            'boolean' => $this->castBoolean($this->value),
            'date' => $this->castDate($this->value),
            'select' => $this->castSelect($this->value),
            default => $this->value
        };
    }

    /**
     * Cast a value to a number.
     *
     * @param mixed $value
     * @return float|null
     */
    protected function castNumber(mixed $value): ?float
    {
        return is_numeric($value) ? floatval($value) : null;
    }

    /**
     * Cast a value to a boolean.
     *
     * @param mixed $value
     * @return bool
     */
    protected function castBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Cast a value to a date.
     *
     * @param mixed $value
     * @return Carbon|null
     */
    protected function castDate(mixed $value): ?Carbon
    {
        try {
            return $value ? Carbon::parse($value) : null;
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Cast a value for a select type attribute.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function castSelect(mixed $value): mixed
    {
        // Ensure the value is in the predefined options
        return in_array($value, $this->attribute->options ?? []) ? $value : null;
    }

}
