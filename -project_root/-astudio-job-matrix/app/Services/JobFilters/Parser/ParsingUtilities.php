<?php

namespace App\Services\JobFilters\Parser;

/**
 * Utility trait for parsing operations
 */
trait ParsingUtilities
{
    /**
     * Check if a string has balanced parentheses.
     *
     * @param string $input
     * @return bool
     */
    protected function hasBalancedParentheses(string $input): bool
    {
        $depth = 0;
        for ($i = 0; $i < strlen($input); $i++) {
            if ($input[$i] === '(') {
                $depth++;
            } elseif ($input[$i] === ')') {
                $depth--;
                if ($depth < 0) {
                    return false;
                }
            }
        }

        return $depth === 0;
    }
}
