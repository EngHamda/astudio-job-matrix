<?php

namespace App\Services;

use App\Exceptions\FilterException;
use App\Services\JobFilters\Handlers\ConditionHandlerInterface;

use Exception;
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
     * @throws FilterException
     */
    public function applyFilters(Builder $query, string $filterString): Builder
    {
        if (empty($filterString)) {
            return $query;
        }

        try {
            Log::info("Applying filters", ['filter_input' => $filterString]);
            // Parse input string
            $parsedFilter = $this->filterParser->parse($filterString);
            Log::info("Parsed filter structure", $parsedFilter);
            // Apply the filter to the query
            return $this->buildQuery($query, $parsedFilter);
        } catch (Exception $e) {
            // Log and convert other exceptions to FilterExceptions
            Log::error("Filter application error", [
                'filter_input' => $filterString,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new FilterException("Error applying filter: {$e->getMessage()}", 400, $e);
        }
    }

    /**
     * Build the query based on the parsed filter structure.
     *
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @param array<string, mixed> $filterData
     * @return Builder<TModel>
     * @throws FilterException
     */
    protected function buildQuery(Builder $query, array $filterData): Builder
    {
        // If this is a logical condition (AND/OR)
        if (isset($filterData['logical'])) {
            return $this->applyLogicalCondition($query, $filterData['logical']);
        } else if (isset($filterData['type']) && isset($filterData['condition'])) {
            // This is a leaf condition
            return $this->applyCondition(
                $query,
                $filterData['type'],
                $filterData['condition']
            );
        } else {
            Log::error("Invalid filter data structure", ['filter_data' => $filterData]);
            throw new FilterException("Invalid filter structure");
        }
    }

    /**
     * Apply a logical condition group (AND/OR).
     *
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @param array{operator:string,conditions:array<int, array<string, mixed>>} $logicalData
     * @return Builder<TModel>
     * @throws FilterException
     */
    protected function applyLogicalCondition(Builder $query, array $logicalData): Builder
    {
        if (!isset($logicalData['operator']) || !isset($logicalData['conditions'])) {
            Log::error("Invalid logical condition structure", ['logical_data' => $logicalData]);
            throw new FilterException("Invalid logical condition structure");
        }

        $operator = $logicalData['operator'];
        $conditions = $logicalData['conditions'];

        if (!in_array($operator, ['AND', 'OR'])) {
            Log::error("Invalid logical operator", ['operator' => $operator]);
            throw new FilterException("Invalid logical operator: $operator");
        }

        if (empty($conditions)) {
            Log::warning("Empty conditions in logical group", ['operator' => $operator]);
            return $query;
        }

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
     * @throws FilterException
     */
    protected function applyCondition(Builder $query, string $type, string $conditionStr): Builder
    {
        Log::info("Applying condition", ['type' => $type, 'condition' => $conditionStr]);

        if (!isset($this->conditionHandlers[$type])) {
            Log::warning("Unknown condition type", ['type' => $type]);
            throw new FilterException("Unknown filter type: $type");
        }

        try {
            return $this->conditionHandlers[$type]->handle($query, $conditionStr);
        } catch (Exception $e) {
            // Convert other exceptions
            Log::error("Error in condition handler", [
                'type' => $type,
                'condition' => $conditionStr,
                'error' => $e->getMessage()
            ]);
            throw new FilterException("Error in $type filter: {$e->getMessage()}", 400, $e);
        }
    }
}
