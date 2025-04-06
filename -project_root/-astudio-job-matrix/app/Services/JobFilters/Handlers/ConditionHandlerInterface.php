<?php

namespace App\Services\JobFilters\Handlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface ConditionHandlerInterface
{
    /**
     * Handle a specific condition type.
     *
     * @template TModel of Model
     * @param Builder<TModel> $query
     * @param string $conditionStr
     * @return Builder<TModel>
     */
    public function handle(Builder $query, string $conditionStr): Builder;
}
