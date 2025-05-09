<?php

namespace App\Services\JobFilters\Handlers;

use DateTime;
use Exception;
use App\Exceptions\FilterException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Attribute;

class EavConditionHandler extends AbstractConditionHandler
{
    /**
     * Handle a specific condition type.
     *
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @param string $conditionStr
     * @return Builder<TModel>
     * @throws FilterException
     */
    public function handle(Builder $query, string $conditionStr): Builder
    {
        return $this->apply($query, $conditionStr);
    }

    /**
     * Apply an EAV condition.
     *
     * @param Builder $query
     * @param string $conditionStr
     * @return Builder
     * @throws FilterException
     */
    public function apply(Builder $query, string $conditionStr): Builder
    {
        // Parse the condition string (e.g., "attribute:years_experience>=3")
        if (!Str::contains($conditionStr, 'attribute:')) {
            Log::warning("Invalid EAV condition format", ['condition' => $conditionStr]);
            throw new FilterException("Invalid EAV condition format: $conditionStr");
        }

        // Extract attribute name and condition
        $attributeStr = Str::after($conditionStr, 'attribute:');
        $parts = $this->parseConditionString($attributeStr);
        if (!$parts) {
            Log::warning("Invalid EAV condition format", ['condition' => $conditionStr]);
            throw new FilterException("Invalid EAV condition format: $conditionStr");
        }

        [$attributeName, $operator, $value] = $parts;

        // Get the attribute from the database
        $attribute = Attribute::where('name', $attributeName)->first();

        if (!$attribute) {
            Log::warning("Attribute not found", ['attribute' => $attributeName]);
            throw new FilterException("Attribute not found: $attributeName");
        }

        Log::info("Processing EAV attribute", ['name' => $attributeName, 'type' => $attribute->type]);

        // Apply the appropriate condition based on attribute type
        switch ($attribute->type) {
            case 'text':
                return $this->applyEavStringCondition($query, $attribute->id, $operator, $value);
            case 'number':
                return $this->applyEavNumericCondition($query, $attribute->id, $operator, $value);
            case 'boolean':
                return $this->applyEavBooleanCondition($query, $attribute->id, $operator, $value);
            case 'select':
                return $this->applyEavSelectCondition($query, $attribute->id, $operator, $value, $attribute->options ?? []);
            case 'date':
                return $this->applyEavDateCondition($query, $attribute->id, $operator, $value);
            default:
                Log::warning("Unsupported attribute type", ['type' => $attribute->type]);
                throw new FilterException("Unsupported attribute type: " . $attribute->type);
        }
    }

    /**
     * Apply a condition on a text-type EAV attribute.
     *
     * @param Builder $query
     * @param int $attributeId
     * @param string $operator
     * @param string $value
     * @return Builder
     * @throws FilterException
     */
    protected function applyEavStringCondition(Builder $query, int $attributeId, string $operator, string $value): Builder
    {
        if (!in_array($operator, $this->config['valid_operators']['string'])) {
            Log::warning("Invalid operator for text attribute", ['operator' => $operator]);
            throw new FilterException("Invalid operator for text attribute: " . $operator);
        }

        return $query->whereHas('jobAttributeValues', function ($subQuery) use ($attributeId, $operator, $value) {
            $subQuery->where('attribute_id', $attributeId);

            if ($operator === 'LIKE') {
                $subQuery->where('value', 'LIKE', '%' . $value . '%');
            } else {
                $subQuery->where('value', $operator, $value);
            }
        });
    }

    /**
     * Apply a condition on a number-type EAV attribute.
     *
     * @param Builder $query
     * @param int $attributeId
     * @param string $operator
     * @param string $value
     * @return Builder
     * @throws FilterException
     */
    protected function applyEavNumericCondition(Builder $query, int $attributeId, string $operator, string $value): Builder
    {
        if (!in_array($operator, $this->config['valid_operators']['numeric'])) {
            Log::warning("Invalid operator for numeric attribute", ['operator' => $operator]);
            throw new FilterException("Invalid operator for numeric attribute: " . $operator);
        }

        if (!is_numeric($value)) {
            Log::warning("Non-numeric value for numeric attribute", ['value' => $value]);
            throw new FilterException("Non-numeric value for numeric attribute: " . $value);
        }

        return $query->whereHas('jobAttributeValues', function ($subQuery) use ($attributeId, $operator, $value) {
            $subQuery->where('attribute_id', $attributeId)
                ->where(DB::raw('CAST(value AS DECIMAL(10,2))'), $operator, (float)$value);
        });
    }

    /**
     * Apply a condition on a boolean-type EAV attribute.
     *
     * @param Builder $query
     * @param int $attributeId
     * @param string $operator
     * @param string $value
     * @return Builder
     * @throws FilterException
     */
    protected function applyEavBooleanCondition(Builder $query, int $attributeId, string $operator, string $value): Builder
    {
        if (!in_array($operator, $this->config['valid_operators']['boolean'])) {
            Log::warning("Invalid operator for boolean attribute", ['operator' => $operator]);
            throw new FilterException("Invalid operator for boolean attribute: " . $operator);
        }

        //to convert various truthy/falsy inputs into a proper boolean—or null
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($boolValue === null) {
            Log::warning("Invalid boolean value for attribute", ['value' => $value]);
            throw new FilterException("Invalid boolean value for attribute: " . $value);
        }
        
        return $query->whereHas('jobAttributeValues', function ($subQuery) use ($attributeId, $operator, $boolValue) {
            $subQuery->where('attribute_id', $attributeId)
                ->where('value', $operator, $boolValue ? 'ture' : 'false');
        });
    }

    /**
     * Apply a condition on a select-type EAV attribute.
     *
     * @param Builder $query
     * @param int $attributeId
     * @param string $operator
     * @param string $value
     * @param array $allowedValues
     * @return Builder
     * @throws FilterException
     */
    protected function applyEavSelectCondition(Builder $query, int $attributeId, string $operator, string $value, array $allowedValues): Builder
    {
        if (!in_array($operator, $this->config['valid_operators']['enum'])) {
            Log::warning("Invalid operator for select attribute", ['operator' => $operator]);
            throw new FilterException("Invalid operator for select attribute: " . $operator);
        }

        if ($operator === 'IN') {
            // Parse the comma-separated values
            $values = array_map('trim', explode(',', $value));

            // Validate values
            $validValues = array_filter($values, function ($val) use ($allowedValues) {
                return in_array($val, $allowedValues);
            });

            if (empty($validValues)) {
                Log::warning("No valid values for select attribute", ['values' => $values]);
                throw new FilterException("No valid values for select attribute: ", $values);
            }

            return $query->whereHas('jobAttributeValues', function ($subQuery) use ($attributeId, $validValues) {
                $subQuery->where('attribute_id', $attributeId)
                    ->whereIn('value', $validValues);
            });
        } else if ($operator === '!=') {
            // For != operator, we need to handle two cases:
            // 1. Records that have this attribute but with a different value
            // 2. Records that don't have this attribute at all

            return $query->where(function ($query) use ($attributeId, $value) {
                $query->whereHas('jobAttributeValues', function ($subQuery) use ($attributeId, $value) {
                    $subQuery->where('attribute_id', $attributeId)
                        ->where('value', '!=', $value);
                })->orWhereDoesntHave('jobAttributeValues', function ($subQuery) use ($attributeId) {
                    $subQuery->where('attribute_id', $attributeId);
                });
            });
        } else {
            // For = operator
            if (!in_array($value, $allowedValues)) {
                Log::warning("Invalid value for select attribute", ['value' => $value]);
                throw new FilterException("Invalid value for select attribute: " . $value);
            }

            return $query->whereHas('jobAttributeValues', function ($subQuery) use ($attributeId, $value) {
                $subQuery->where('attribute_id', $attributeId)
                    ->where('value', $value);
            });
        }
    }

    /**
     * Apply a condition on a date-type EAV attribute.
     *
     * @param Builder $query
     * @param int $attributeId
     * @param string $operator
     * @param string $value
     * @return Builder
     * @throws FilterException
     */
    protected function applyEavDateCondition(Builder $query, int $attributeId, string $operator, string $value): Builder
    {
        if (!in_array($operator, $this->config['valid_operators']['date'])) {
            Log::warning("Invalid operator for date attribute", ['operator' => $operator]);
            throw new FilterException("Invalid operator for date attribute: " . $operator);
        }

        try {
            $date = new DateTime($value);

            return $query->whereHas('jobAttributeValues', function ($subQuery) use ($attributeId, $operator, $date) {
                $subQuery->where('attribute_id', $attributeId)
                    ->where(DB::raw('DATE(value)'), $operator, $date->format('Y-m-d'));
            });
        } catch (Exception $e) {
            Log::warning("Invalid date format for attribute", ['value' => $value, 'error' => $e->getMessage()]);
            throw new FilterException("Invalid date format for attribute, value => " . $value);
        }
    }
}
