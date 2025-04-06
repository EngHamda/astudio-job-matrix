<?php

namespace App\Services\JobFilters\Parser;

use Illuminate\Support\Str;

/**
 * FilterParser - Parses complex filter strings with logical operators into structured arrays.
 *
 * Example Usage:
 * ----------------------------------------------------------------------------
 * For a complex filter like:
 * (job_type=full-time AND (languages:name HAS_ANY (PHP,JavaScript))) AND (locations:city IS_ANY (New York,Remote)) AND attribute:years_experience>=3
 *
 * The FilterParser breaks down the expression into its logical structure:
 *
 * 1. Identifies the top-level AND operator and splits into 3 parts:
 *    - (job_type=full-time AND (languages:name HAS_ANY (PHP,JavaScript)))
 *    - (locations:city IS_ANY (New York,Remote))
 *    - attribute:years_experience>=3
 *
 * 2. For the first part, it recognizes another AND and recursively processes:
 *    - job_type=full-time
 *    - languages:name HAS_ANY (PHP,JavaScript)
 *
 * 3. Simple conditions are sent to ConditionParser.parseSimpleCondition()
 *
 * 4. Final result is a nested array structure:
 *    [
 *        'logical' => [
 *            'operator' => 'AND',
 *            'conditions' => [
 *                [
 *                    'logical' => [
 *                        'operator' => 'AND',
 *                        'conditions' => [
 *                            ['type' => 'basic', 'condition' => 'job_type=full-time'],
 *                            ['type' => 'relationship', 'condition' => 'languages:name HAS_ANY (PHP,JavaScript)']
 *                        ]
 *                    ]
 *                ],
 *                ['type' => 'relationship', 'condition' => 'locations:city IS_ANY (New York,Remote)'],
 *                ['type' => 'eav', 'condition' => 'attribute:years_experience>=3']
 *            ]
 *        ]
 *    ]
 *
 * This structured representation can then be used for building database queries.
 */
class FilterParser
{
    use ParsingUtilities;

    /**
     * @var ConditionParser
     */
    protected ConditionParser $conditionParser;

    /**
     * @var array
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param ConditionParser $conditionParser
     */
    public function __construct(ConditionParser $conditionParser)
    {
        $this->config = config('job_filters');
        $this->conditionParser = $conditionParser;
    }

    /**
     * Parse a filter string into a nested array structure.
     *
     * @param string $filterString
     * @return array<string, mixed>
     */
    public function parse(string $filterString): array
    {
        $filterString = $this->cleanFilterString($filterString);

        // If we have no logical operators, this is a simple condition
        if (!$this->containsLogicalOperator($filterString)) {
            return $this->conditionParser->parseSimpleCondition($filterString);
        }

        return $this->parseComplexCondition($filterString);
    }

    /**
     * Clean the input string by removing outer whitespace and unnecessary parentheses.
     *
     * @param string $filterString
     * @return string
     */
    protected function cleanFilterString(string $filterString): string
    {
        $filterString = trim($filterString);

        // Remove outer parentheses if they enclose the entire input
        while (Str::startsWith($filterString, '(') && Str::endsWith($filterString, ')')) {
            $temp = substr($filterString, 1, -1);
            if ($this->hasBalancedParentheses($temp)) {
                $filterString = $temp;
            } else {
                break;
            }
        }

        return $filterString;
    }

    /**
     * Check if the input contains logical operators (AND, OR).
     *
     * @param string $filterString
     * @return bool
     */
    protected function containsLogicalOperator(string $filterString): bool
    {
        return $this->containsOperator($filterString, 'AND') || $this->containsOperator($filterString, 'OR');
    }

    /**
     * Check if the input contains a specific operator at the top level (not within parentheses).
     *
     * @param string $filterString
     * @param string $operator
     * @return bool
     */
    protected function containsOperator(string $filterString, string $operator): bool
    {
        $depth = 0;
        $length = strlen($filterString);

        for ($i = 0; $i < $length; $i++) {
            if ($filterString[$i] === '(') {
                $depth++;
            } elseif ($filterString[$i] === ')') {
                $depth--;
            } elseif ($depth === 0) {
                // Check for operator with spaces around it
                $searchStr = " $operator ";
                if (substr($filterString, $i, strlen($searchStr)) === $searchStr) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Parse a complex condition containing logical operators.
     *
     * @param string $input
     * @return array
     */
    protected function parseComplexCondition(string $input): array
    {
        // Determine if this is an AND or OR condition
        $operator = $this->containsOperator($input, 'AND') ? 'AND' : 'OR';
        $parts = $this->splitByOperator($input, $operator);

        $conditions = [];
        foreach ($parts as $part) {
            $cleanPart = $this->cleanFilterString($part);

            if ($this->containsLogicalOperator($cleanPart)) {
                $conditions[] = $this->parseComplexCondition($cleanPart);
            } else {
                $condition = $this->conditionParser->parseSimpleCondition($cleanPart);
                $conditions[] = $condition;
            }
        }

        return [
            'logical' => [
                'operator' => $operator,
                'conditions' => $conditions,
            ]
        ];
    }

    /**
     * Split input by a logical operator while respecting parentheses.
     *
     * @param string $input
     * @param string $operator
     * @return array
     */
    protected function splitByOperator(string $input, string $operator): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $length = strlen($input);
        $operatorWithSpaces = " $operator ";
        $operatorLength = strlen($operatorWithSpaces);

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            if ($depth === 0 && substr($input, $i, $operatorLength) === $operatorWithSpaces) {
                $parts[] = trim($current);
                $current = '';
                $i += ($operatorLength - 1); // Skip over the operator
            } else {
                $current .= $char;
            }
        }

        if (trim($current) !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }
}
