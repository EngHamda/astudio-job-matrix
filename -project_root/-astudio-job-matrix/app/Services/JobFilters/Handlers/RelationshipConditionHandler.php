<?php

namespace App\Services\JobFilters\Handlers;

use App\Exceptions\FilterException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RelationshipConditionHandler extends AbstractConditionHandler
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
     * Apply a relationship condition.
     *
     * @param Builder $query
     * @param string $conditionStr
     * @return Builder
     * @throws FilterException
     */
    public function apply(Builder $query, string $conditionStr): Builder
    {
        // Check for EXISTS operator
        if (preg_match('/^([\w:]+)\s+EXISTS$/', $conditionStr, $matches)) {
            [, $relationship] = $matches;
            return $this->applyExistsCondition($query, $relationship);
        }

        // Check for = operator
        //Use \s* (zero-or-more spaces) instead of \s+, so it will match whether or not spaces
        if (preg_match('/^([\w:]+)\s*=\s*(.+)$/', $conditionStr, $matches)) {
            [, $relationship, $value] = $matches;
            return $this->applyEqualityCondition($query, $relationship, trim($value));
        }

        // Check for HAS_ANY, HAS_ALL, IS_ANY
        if (preg_match('/^([\w:]+)\s+(HAS_ANY|HAS_ALL|IS_ANY)\s+\(([\w\s,]+)\)$/', $conditionStr, $matches)) {
            [, $relationship, $operator, $valuesStr] = $matches;
            $values = array_map('trim', explode(',', $valuesStr));

            if (empty($values)) {
                Log::warning("No values provided for relationship filter", ['relationship' => $relationship]);
                throw new FilterException("No values provided for relationship filter: $relationship");
            }

            // Check if the relationship is valid
            if (!in_array(Str::before($relationship, ':'), $this->config['relationship_filters'])) {
                Log::warning("Invalid relationship filter", ['relationship' => $relationship]);
                throw new FilterException("Invalid relationship filter: $relationship");
            }

            switch ($operator) {
                case 'HAS_ANY':
                    return $this->applyHasAnyCondition($query, $relationship, $values);
                case 'HAS_ALL':
                    return $this->applyHasAllCondition($query, $relationship, $values);
                case 'IS_ANY':
                    return $this->applyIsAnyCondition($query, $relationship, $values);
            }
        }

        Log::warning("Invalid relationship condition format", ['condition' => $conditionStr]);
        throw new FilterException("Invalid relationship condition format: $conditionStr");
    }

    /**
     * Apply HAS_ANY condition - job has any of the specified values.
     *
     * @param Builder $query
     * @param string $relationship
     * @param array $values
     * @return Builder
     */
    protected function applyHasAnyCondition(Builder $query, string $relationship, array $values): Builder
    {
        // Suppose $conditionStr is 'languages:name'
        //$relationshipArr = explode(':', $relationship, 2);//Str::before($relationship, ':');
        [$relationName, $field] = explode(':', $relationship, 2);
        return $query->whereHas($relationName, function ($subQuery) use ($relationName, $field, $values) {
            $subQuery->whereIn($field, $values);

        });
    }

    /**
     * Apply HAS_ALL condition - job has all of the specified values.
     *
     * @param Builder $query
     * @param string $relationship
     * @param array $values
     * @return Builder
     */
    protected function applyHasAllCondition(Builder $query, string $relationship, array $values): Builder
    {
        [$relationName, $field] = explode(':', $relationship, 2);
        foreach ($values as $value) {
            $query->whereHas($relationName, function ($subQuery) use ($field, $value) {
                $subQuery->where($field, $value);
            });
        }
        return $query;
    }

    /**
     * Apply IS_ANY condition - relationship matches any of the values.
     * This differs from HAS_ANY by checking if the relationship itself matches any value,
     * not if it contains any of the values.
     *
     * @param Builder $query
     * @param string $relationship
     * @param array $values
     * @return Builder
     */
    protected function applyIsAnyCondition(Builder $query, string $relationship, array $values): Builder
    {
        [$relationName, $field] = explode(':', $relationship, 2);
        return $query->whereHas($relationName, function ($subQuery) use ($field, $values) {
            $subQuery->where(function (Builder $sub) use ($field, $values) {
                foreach ($values as $value) {
                    $sub->orWhere($field, $value);
                }
            });
        });
    }

    /**
     * Apply EXISTS condition - relationship exists.
     *
     * @param Builder $query
     * @param string $relationship
     * @return Builder
     * @throws FilterException
     */
    protected function applyExistsCondition(Builder $query, string $relationship): Builder
    {
        [$relationName] = explode(':', $relationship, 2);
        // Check if the relationship is valid
        if (!in_array($relationName, $this->config['relationship_filters'])) {
            Log::warning("Invalid relationship filter", ['relationship' => $relationName]);
            throw new FilterException("Invalid relationship filter: $relationName");

        }

        return $query->has($relationName);
    }

    /**
     * Apply equality condition - exact match.
     *
     * @param Builder $query
     * @param string $relationship
     * @param string $value
     * @return Builder
     * @throws FilterException
     */
    protected function applyEqualityCondition(Builder $query, string $relationship, string $value): Builder
    {
        [$relationName, $field] = explode(':', $relationship, 2);
        // Check if the relationship is valid
        if (!in_array($relationName, $this->config['relationship_filters'])) {
            Log::warning("Invalid relationship filter", ['relationship' => $relationName]);
            throw new FilterException("Invalid relationship filter: $relationName");
        }

        return $query->whereHas($relationName, function ($subQuery) use ($field, $value) {
            $subQuery->where($field, '=', $value);
        });
    }
}
