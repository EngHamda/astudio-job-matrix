<?php

namespace App\Services;

use App\Services\JobFilters\Handlers\ConditionHandlerInterface;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Services\JobFilters\Parser\FilterParser;
use App\Services\JobFilters\Handlers\BasicConditionHandler;
use App\Services\JobFilters\Handlers\EavConditionHandler;
use App\Services\JobFilters\Handlers\RelationshipConditionHandler;

class JobFilterService
{
    /**
     * The parser that converts a filter string into structured conditions.
     *
     * @var FilterParser
     */
    protected FilterParser $filterParser;
    /**
     * A map of condition handler instances, keyed by filter type.
     *
     * @var array<string, ConditionHandlerInterface>
     */
    protected array $conditionHandlers;

    public function __construct(
        FilterParser                 $filterParser,
        BasicConditionHandler        $basicHandler,
        EavConditionHandler          $eavHandler,
        RelationshipConditionHandler $relationshipHandler
    )
    {
        $this->filterParser = $filterParser;
        $this->conditionHandlers = [
            'basic' => $basicHandler,
            'eav' => $eavHandler,
            'relationship' => $relationshipHandler
        ];
    }

    /**
     * Apply filters to a query builder instance.
     *
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @param string $filterString
     * @return Builder<TModel>
     */
    public function applyFilters(Builder $query, string $filterString): Builder
    {
        Log::info("Applying filters", ['filter_input' => $filterString]);
        // Parse input string
        $parsedFilter = $this->filterParser->parse($filterString);
        Log::info("Parsed filter structure", $parsedFilter);
        // Apply the filter to the query
        return $this->buildQuery($query, $parsedFilter);
    }

    /**
     * Build the query based on the parsed filter structure.
     *
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @param array<string, mixed> $filterData
     * @return Builder<TModel>
     */
    protected function buildQuery(Builder $query, array $filterData): Builder
    {
        // If this is a logical condition (AND/OR)
        if (isset($filterData['logical'])) {
            return $this->applyLogicalCondition($query, $filterData['logical']);
        } else {
            // This is a leaf condition
            return $this->applyCondition(
                $query,
                $filterData['type'],
                $filterData['condition']
            );
        }
    }

    /**
     * Apply a logical condition group (AND/OR).
     *
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @param array{operator:string,conditions:array<int, array<string, mixed>>} $logicalData
     * @return Builder<TModel>
     */
    protected function applyLogicalCondition(Builder $query, array $logicalData): Builder
    {
        $operator = $logicalData['operator'];
        $conditions = $logicalData['conditions'];

        return $query->where(function ($subQuery) use ($operator, $conditions) {
            foreach ($conditions as $index => $condition) {
                if ($index === 0) {
                    $method = 'where';
                } else {
                    $method = ($operator === 'OR') ? 'orWhere' : 'where';
                }
                $subQuery->$method(function ($nestedQuery) use ($condition) {
                    $this->buildQuery($nestedQuery, $condition);
                });
            }
        });

    }

    /**
     * Apply a single condition to the query.
     *
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @param string $type
     * @param string $conditionStr
     * @return Builder<TModel>
     */
    protected function applyCondition(Builder $query, string $type, string $conditionStr): Builder
    {
        Log::info("Applying condition", ['type' => $type, 'condition' => $conditionStr]);

        if (!isset($this->conditionHandlers[$type])) {
            Log::warning("Unknown condition type", ['type' => $type]);
            return $query;
        }

        return $this->conditionHandlers[$type]->handle($query, $conditionStr);
    }
}
