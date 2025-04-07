<?php

namespace App\Services\JobFilters\Parser;

use Illuminate\Support\Str;

/**
 * ConditionParser - Parses individual filter conditions into their components.
 *
 * Example Usage:
 * ----------------------------------------------------------------------------
 * For a complex filter like:
 * (job_type=full-time AND (languages:name HAS_ANY (PHP,JavaScript))) AND (locations:city IS_ANY (New York,Remote)) AND attribute:years_experience>=3
 *
 * The ConditionParser receives individual conditions from FilterParser and determines their types:
 *
 * 1. "job_type=full-time" -> ['type' => 'basic', 'condition' => 'job_type=full-time']
 *    (Identified as a basic job column condition)
 *
 * 2. "languages:name HAS_ANY (PHP,JavaScript)" -> ['type' => 'relationship', 'condition' => 'languages:name HAS_ANY (PHP,JavaScript)']
 *    (Identified as a relationship filter)
 *
 * 3. "locations:city IS_ANY (New York,Remote)" -> ['type' => 'relationship', 'condition' => 'locations:city IS_ANY (New York,Remote)']
 *    (Identified as a relationship filter)
 *
 * 4. "attribute:years_experience>=3" -> ['type' => 'eav', 'condition' => 'attribute:years_experience>=3']
 *    (Identified as an EAV attribute condition)
 *
 * Each condition type can later be processed differently based on how it should interact with the database.
 */
class ConditionParser
{
    use ParsingUtilities;

    /**
     * @var array
     */
    protected array $config;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->config = config('job_filters');
    }

    /**
     * Parse a simple condition with no logical operators.
     *
     * @param string $inputStr
     * @return array
     */
    public function parseSimpleCondition(string $inputStr): array
    {
        // Determine the type of condition and parse accordingly
        $conditionType = $this->determineConditionType($inputStr);
        return [
            'type' => $conditionType,
            'condition' => $inputStr
        ];
    }

    /**
     * Parse a condition string into field, operator, and value components.
     *
     * @param string $conditionStr
     * @return array|null
     */
    public function parseConditionString(string $conditionStr): ?array
    {
        // Match patterns like "field=value", "field>=value", etc.
        $patterns = [
//            (\w+) in the regex only matches letters, digits and underscores
//            // Standard operators
//            '/^(\w+)\s*=\s*(.+)$/' => '=',
//            '/^(\w+)\s*!=\s*(.+)$/' => '!=',
//            '/^(\w+)\s*>\s*(.+)$/' => '>',
//            '/^(\w+)\s*<\s*(.+)$/' => '<',
//            '/^(\w+)\s*>=\s*(.+)$/' => '>=',
//            '/^(\w+)\s*<=\s*(.+)$/' => '<=',
//            // LIKE operator
//            '/^(\w+)\s+LIKE\s+(.+)$/' => 'LIKE',
//            // IN operator (for comma-separated lists)
//            '/^(\w+)\s+IN\s*\(([\w\s,]+)\)$/' => 'IN'

            //update: ([\w\- ]+) matches any sequence of non‑space characters, (.+) to allow spaces in the field name
            '/^([\w\- ]+)\s*>=\s*(.+)$/' => '>=',
            '/^([\w\- ]+)\s*<=\s*(.+)$/' => '<=',
            '/^([\w\- ]+)\s*>\s*(.+)$/' => '>',
            '/^([\w\- ]+)\s*<\s*(.+)$/' => '<',
            '/^([\w\- ]+)\s*!=\s*(.+)$/' => '!=',
            '/^([\w\- ]+)\s*=\s*(.+)$/' => '=',
            // LIKE operator
            '/^([\w\- ]+)\s+LIKE\s+(.+)$/' => 'LIKE',
            // IN operator (for comma-separated lists)
            '/^([\w\- ]+)\s+IN\s*\(([\w\-\s,]+)\)$/i' => 'IN'
//            '/^([\w\- ]+)\s+IN\s*\(([\w\s,]+)\)$/' => 'IN'
        ];

        foreach ($patterns as $pattern => $op) {
            if (preg_match($pattern, $conditionStr, $matches)) {
                return [
                    trim($matches[1]), // field
                    $op,               // operator
                    trim($matches[2])  // value
                ];
            }
        }

        return null;
    }

    /**
     * Determine the type of condition: basic, relationship, EAV, or nested.
     *
     * @param string $input
     * @return string
     */
    protected function determineConditionType(string $input): string
    {
        // Extract the field part (before any operator)
        $fieldPart = $input;
        $operators = ['=', '>', '<', '>=', '<=', '!=', 'LIKE', 'HAS_ANY', 'HAS_ALL'];

        foreach ($operators as $operator) {
            $position = strpos($input, $operator);
            if ($position !== false) {
                $fieldPart = trim(substr($input, 0, $position));
                break;
            }
        }

        // Get the first word of the field part
        $spacePos = strpos($fieldPart, ' ');
        $firstWord = $spacePos !== false ? substr($fieldPart, 0, $spacePos) : $fieldPart;

        // 1. Check if it's a basic condition (column in core_jobs table)
        if (in_array($firstWord, $this->config['job_columns'])) {
            return 'basic';
        }

        // 2. Check if it's a relationship condition
        if (in_array(Str::before($firstWord, ':'), $this->config['relationship_filters'])) {
            return 'relationship';
        }

        // 3. Check if it's an EAV attribute condition
        if (str_starts_with($firstWord, 'attribute:')) {
            return 'eav';
        }

        // 4. Check if it's a nested condition
        if (str_contains($input, '(') && $this->hasBalancedParentheses($input)) {
            return 'nested';
        }

        // 5. Default to basic if none of the above
        return 'basic';
    }
}
