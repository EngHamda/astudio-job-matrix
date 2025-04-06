<?php

namespace App\Services\Utilities;

use App\Models\Attribute;
use DateTime;
use Exception;
use Closure;

class JobFilterUtility
{
    /**
     * Convert the filter structure back to a string for debugging
     *
     * @param array $filterData
     * @return string
     */
    public static function filterToString(array $filterData): string
    {
        if (isset($filterData['logical'])) {
            $operator = $filterData['logical']['operator'];
            $conditions = $filterData['logical']['conditions'];

            $conditionStrings = [];
            foreach ($conditions as $condition) {
                $conditionStrings[] = self::filterToString($condition);
            }

            return '(' . implode(" $operator ", $conditionStrings) . ')';
        } else {
            return $filterData['condition'];
        }
    }

    /**
     * Get a JSON representation of the parsed filter structure
     *
     * @param array $filterData
     * @return string
     */
    public static function filterToJson(array $filterData): string
    {
        return json_encode($filterData, JSON_PRETTY_PRINT);
    }

    /**
     * Parse JSON filter into array structure
     *
     * @param string $jsonFilter
     * @return array
     */
    public static function parseJsonFilter(string $jsonFilter): array
    {
        try {
            return json_decode($jsonFilter, true) ?? [];
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Validate filter structure
     *
     * @param array $structure
     * @return bool
     */
    public static function validateFilterStructure(array $structure): bool
    {
        // Simple validation - either it has a logical operator or it's a simple condition
        return isset($structure['logical']) || (isset($structure['type']) && isset($structure['condition']));
    }

    /**
     * Get attribute options for a select-type attribute
     *
     * @param int $attributeId
     * @return array
     */
    public static function getAttributeOptions(int $attributeId): array
    {
        $attribute = Attribute::find($attributeId);

        if (!$attribute || $attribute->type !== 'select') {
            return [];
        }

        return $attribute->options ?? [];
    }

    /**
     * Check if a field exists in the given columns array
     *
     * @param string $field
     * @param array $allowedColumns
     * @return bool
     */
    public static function isValidField(string $field, array $allowedColumns): bool
    {
        return in_array($field, $allowedColumns);
    }

    /**
     * Get the field type based on configuration arrays
     *
     * @param string $field
     * @param array $numericColumns
     * @param array $booleanColumns
     * @param array $dateColumns
     * @param array $enumColumns
     * @param array $allColumns
     * @return string|null
     */
    public static function getFieldType(
        string $field,
        array  $numericColumns,
        array  $booleanColumns,
        array  $dateColumns,
        array  $enumColumns,
        array  $allColumns
    ): ?string
    {
        if (in_array($field, $numericColumns)) {
            return 'numeric';
        } elseif (in_array($field, $booleanColumns)) {
            return 'boolean';
        } elseif (in_array($field, $dateColumns)) {
            return 'date';
        } elseif (isset($enumColumns[$field])) {
            return 'enum';
        } elseif (in_array($field, $allColumns)) {
            return 'string';
        }

        return null;
    }

    /**
     * Check if an operator is valid for a given field type
     *
     * @param string $operator
     * @param string $fieldType
     * @param array $validOperators
     * @return bool
     */
    public static function isValidOperator(string $operator, string $fieldType, array $validOperators): bool
    {
        return isset($validOperators[$fieldType]) &&
            in_array($operator, $validOperators[$fieldType]);
    }

    /**
     * Format a value based on its field type
     *
     * @param mixed $value
     * @param string $fieldType
     * @return mixed
     */
    public static function formatValue(mixed $value, string $fieldType): mixed
    {
        switch ($fieldType) {
            case 'numeric':
                return (float)$value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'date':
                try {
                    $date = new DateTime($value);
                    return $date->format('Y-m-d H:i:s');
                } catch (Exception) {
                    return $value;
                }
            default:
                return $value;
        }
    }

    /**
     * Creates a dynamic attribute filter for a given attribute
     *
     * @param string $attributeName
     * @param string $operator
     * @param mixed $value
     * @return Closure
     */
    public static function createAttributeFilter(string $attributeName, string $operator, mixed $value): Closure
    {
        return function ($query) use ($attributeName, $operator, $value) {
            $attribute = Attribute::where('name', $attributeName)->first();

            if (!$attribute) {
                return $query;
            }

            return $query->whereHas('jobAttributeValues', function ($subQuery) use ($attribute, $operator, $value) {
                $subQuery->where('attribute_id', $attribute->id);

                if ($operator === 'LIKE') {
                    $subQuery->where('value', 'LIKE', '%' . $value . '%');
                } else {
                    $subQuery->where('value', $operator, $value);
                }
            });
        };
    }

    /**
     * Validate a date string
     *
     * @param string $dateString
     * @return bool
     */
    public static function isValidDate(string $dateString): bool
    {
        try {
            new DateTime($dateString);
            return true;
        } catch (Exception) {
            return false;
        }
    }
}
