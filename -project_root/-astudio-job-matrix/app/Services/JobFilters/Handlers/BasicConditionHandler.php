<?php

namespace App\Services\JobFilters\Handlers;

use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class BasicConditionHandler extends AbstractConditionHandler
{
    /**
     * Handle a specific condition type.
     *
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @param string $conditionStr
     * @return Builder<TModel>
     */
    public function handle(Builder $query, string $conditionStr): Builder
    {
        return $this->apply($query, $conditionStr);
    }

    /**
     * Apply a basic condition on a job column.
     *
     * @param Builder $query The query builder instance
     * @param string $conditionStr The condition string
     * @return Builder The query builder with filters applied
     */
    public function apply(Builder $query, string $conditionStr): Builder
    {
        // Parse the condition string (e.g., "job_type=full-time")
        $parts = $this->parseConditionString($conditionStr);

        if (!$parts) {
            Log::warning("Invalid basic condition format", ['condition' => $conditionStr]);
            return $query;
        }

        [$field, $operator, $value] = $parts;

        // Validate the field is a valid job column
        if (!in_array($field, $this->config['job_columns'])) {
            Log::warning("Invalid field in basic condition", ['field' => $field]);
            return $query;
        }

        // Apply appropriate condition based on field type and operator
        if (in_array($field, $this->config['numeric_columns'])) {
            return $this->applyNumericCondition($query, $field, $operator, $value);
        } elseif (in_array($field, $this->config['boolean_columns'])) {
            return $this->applyBooleanCondition($query, $field, $operator, $value);
        } elseif (in_array($field, $this->config['date_columns'])) {
            return $this->applyDateCondition($query, $field, $operator, $value);
        } elseif (isset($this->config['enum_columns'][$field])) {
            return $this->applyEnumCondition($query, $field, $operator, $value, $this->config['enum_columns'][$field]);
        } else {
            // Default to string column
            return $this->applyStringCondition($query, $field, $operator, $value);
        }
    }

    /**
     * Apply a condition on a string column.
     *
     * @param Builder $query
     * @param string $field
     * @param string $operator
     * @param string $value
     * @return Builder
     */
    protected function applyStringCondition(Builder $query, string $field, string $operator, string $value): Builder
    {
        if (!in_array($operator, $this->config['valid_operators']['string'])) {
            Log::warning("Invalid operator for string field", ['field' => $field, 'operator' => $operator]);
            return $query;
        }

        if ($operator === 'LIKE') {
            return $query->where($field, 'LIKE', '%' . $value . '%');
        } else {
            return $query->where($field, $operator, $value);
        }
    }

    /**
     * Apply a condition on a numeric column.
     *
     * @param Builder $query
     * @param string $field
     * @param string $operator
     * @param string $value
     * @return Builder
     */
    protected function applyNumericCondition(Builder $query, string $field, string $operator, string $value): Builder
    {
        if (!in_array($operator, $this->config['valid_operators']['numeric'])) {
            Log::warning("Invalid operator for numeric field", ['field' => $field, 'operator' => $operator]);
            return $query;
        }

        if (!is_numeric($value)) {
            Log::warning("Non-numeric value for numeric field", ['field' => $field, 'value' => $value]);
            return $query;
        }

        return $query->where($field, $operator, (float)$value);
    }

    /**
     * Apply a condition on a boolean column.
     *
     * @param Builder $query
     * @param string $field
     * @param string $operator
     * @param string $value
     * @return Builder
     */
    protected function applyBooleanCondition(Builder $query, string $field, string $operator, string $value): Builder
    {
        if (!in_array($operator, $this->config['valid_operators']['boolean'])) {
            Log::warning("Invalid operator for boolean field", ['field' => $field, 'operator' => $operator]);
            return $query;
        }

        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($boolValue === null) {
            Log::warning("Invalid boolean value", ['field' => $field, 'value' => $value]);
            return $query;
        }

        return $query->where($field, $operator, $boolValue);
    }

    /**
     * Apply a condition on a date column.
     *
     * @param Builder $query
     * @param string $field
     * @param string $operator
     * @param string $value
     * @return Builder
     */
    protected function applyDateCondition(Builder $query, string $field, string $operator, string $value): Builder
    {
        if (!in_array($operator, $this->config['valid_operators']['date'])) {
            Log::warning("Invalid operator for date field", ['field' => $field, 'operator' => $operator]);
            return $query;
        }

        try {
            $date = new DateTime($value);
            return $query->where($field, $operator, $date->format('Y-m-d H:i:s'));
        } catch (Exception $e) {
            Log::warning("Invalid date format", ['field' => $field, 'value' => $value, 'error' => $e->getMessage()]);
            return $query;
        }
    }

    /**
     * Apply a condition on an enum column.
     *
     * @param Builder $query
     * @param string $field
     * @param string $operator
     * @param string $value
     * @param array $allowedValues
     * @return Builder
     */
    protected function applyEnumCondition(Builder $query, string $field, string $operator, string $value, array $allowedValues): Builder
    {
        if (!in_array($operator, $this->config['valid_operators']['enum'])) {
            Log::warning("Invalid operator for enum field", ['field' => $field, 'operator' => $operator]);
            return $query;
        }

        if ($operator === 'IN') {
            // Parse the comma-separated values
            $values = array_map('trim', explode(',', $value));

            // Validate values
            $validValues = array_filter($values, function ($val) use ($allowedValues) {
                return in_array($val, $allowedValues);
            });

            if (empty($validValues)) {
                Log::warning("No valid values for enum field", ['field' => $field, 'values' => $values]);
                return $query;
            }

            return $query->whereIn($field, $validValues);
        } else {
            // For = and != operators
            if (!in_array($value, $allowedValues)) {
                Log::warning("Invalid value for enum field", ['field' => $field, 'value' => $value]);
                return $query;
            }

            return $query->where($field, $operator, $value);
        }
    }
}
