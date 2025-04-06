<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;


/**
 *
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property array<array-key, mixed>|null $options
 * @property bool $is_required
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, JobAttributeValue> $jobAttributeValues
 * @property-read int|null $job_attribute_values_count
 * @method static Builder<static>|Attribute newModelQuery()
 * @method static Builder<static>|Attribute newQuery()
 * @method static Builder<static>|Attribute query()
 * @method static Builder<static>|Attribute whereCreatedAt($value)
 * @method static Builder<static>|Attribute whereId($value)
 * @method static Builder<static>|Attribute whereIsRequired($value)
 * @method static Builder<static>|Attribute whereName($value)
 * @method static Builder<static>|Attribute whereOptions($value)
 * @method static Builder<static>|Attribute whereType($value)
 * @method static Builder<static>|Attribute whereUpdatedAt($value)
 * @mixin Builder
 * @method static Builder|Attribute where(string $column, $operator = null, $value = null, string $boolean = 'and')
 */
class Attribute extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'options',
        'is_required'
    ];

    /**
     * Relationship to job attribute values.
     *
     * @return HasMany
     */
    public function jobAttributeValues(): HasMany
    {
        return $this->hasMany(JobAttributeValue::class);
    }

    /**
     * Validate a value against the attribute's validation rules.
     *
     * @param mixed $value The value to validate
     * @return bool Whether the value is valid
     */
    public function validateValue(mixed $value): bool
    {
        // Combine base and contextual validation rules
        $baseRules = $this->getBaseValidationRules();
        $contextRules = $this->getContextualValidationRules();
        $combinedRules = array_merge($baseRules, $contextRules);

        $validator = Validator::make(['value' => $value], $combinedRules);
        return !$validator->fails();
    }

    /**
     * Get base validation rules based on attribute type.
     *
     * @return array Validation rules
     */
    protected function getBaseValidationRules(): array
    {
        $baseRules = match ($this->type) {
            'text' => ['value' => ['string']],
            'number' => ['value' => ['numeric']],
            'boolean' => ['value' => ['boolean']],
            'date' => ['value' => ['date']],
            'select' => ['value' => ['in:' . implode(',', $this->options ?? [])]],
            default => ['value' => []]
        };

        // Add required rule if attribute is set as required
        if ($this->is_required) {
            $baseRules['value'][] = 'required';
        }

        return $baseRules;
    }

    /**
     * Get contextual validation rules based on attribute options.
     *
     * @return array Additional validation rules
     */
    protected function getContextualValidationRules(): array
    {
        return match ($this->type) {
            'number' => $this->getNumberValidationRules(),
            'text' => $this->getTextValidationRules(),
            'date' => $this->getDateValidationRules(),
            default => []
        };
    }

    /**
     * Get validation rules for number attributes.
     *
     * @return array Number-specific validation rules
     */
    protected function getNumberValidationRules(): array
    {
        $numberRules = [];

        if (isset($this->options['min'])) {
            $numberRules['value'][] = 'min:' . $this->options['min'];
        }

        if (isset($this->options['max'])) {
            $numberRules['value'][] = 'max:' . $this->options['max'];
        }

        return $numberRules;
    }

    /**
     * Get validation rules for text attributes.
     *
     * @return array Text-specific validation rules
     */
    protected function getTextValidationRules(): array
    {
        $textRules = [];

        if (isset($this->options['max_length'])) {
            $textRules['value'][] = 'max:' . $this->options['max_length'];
        }

        if (isset($this->options['min_length'])) {
            $textRules['value'][] = 'min:' . $this->options['min_length'];
        }

        return $textRules;
    }

    /**
     * Get validation rules for date attributes.
     *
     * @return array Date-specific validation rules
     */
    protected function getDateValidationRules(): array
    {
        $dateRules = [];

        if (isset($this->options['min_date'])) {
            $dateRules['value'][] = 'after_or_equal:' . $this->options['min_date'];
        }

        if (isset($this->options['max_date'])) {
            $dateRules['value'][] = 'before_or_equal:' . $this->options['max_date'];
        }

        return $dateRules;
    }

}
