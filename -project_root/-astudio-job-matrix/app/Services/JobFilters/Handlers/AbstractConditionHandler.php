<?php

namespace App\Services\JobFilters\Handlers;

use App\Services\JobFilters\Parser\ConditionParser;

abstract class AbstractConditionHandler implements ConditionHandlerInterface
{
    /**
     * @var array
     */
    protected array $config;

    /**
     * @var ConditionParser
     */
    protected ConditionParser $conditionParser;

    /**
     * Constructor
     *
     * @param ConditionParser $conditionParser
     */
    public function __construct(ConditionParser $conditionParser)
    {
        $this->conditionParser = $conditionParser;
        $this->config = config('job_filters');

    }

    /**
     * Parse a condition string into field, operator, and value components.
     *
     * @param string $conditionStr
     * @return array|null
     */
    protected function parseConditionString(string $conditionStr): ?array
    {
        return $this->conditionParser->parseConditionString($conditionStr);
    }
}