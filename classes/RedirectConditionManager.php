<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes;

use CreativeSizzle\Redirect\Classes\Contracts\RedirectConditionInterface;
use CreativeSizzle\Redirect\Classes\Contracts\RedirectManagerInterface;

final class RedirectConditionManager
{
    private RedirectManagerInterface $redirectManager;

    public function __construct(RedirectManagerInterface $redirectManager)
    {
        $this->redirectManager = $redirectManager;
    }

    public function getEnabledConditions(RedirectRule $rule): array
    {
        $enabledConditions = [];

        if (! class_exists(\CreativeSizzle\RedirectConditions\Models\ConditionParameter::class)) {
            return $enabledConditions;
        }

        $conditions = $this->redirectManager->getConditions();

        if (count($conditions) === 0) {
            return $enabledConditions;
        }

        $conditionCodes = \CreativeSizzle\RedirectConditions\Models\ConditionParameter::query()
            ->where('redirect_id', '=', $rule->getId())
            ->whereNotNull('is_enabled')
            ->get(['condition_code'])
            ->pluck('condition_code')
            ->toArray();

        if (count($conditionCodes) === 0) {
            return $enabledConditions;
        }

        foreach ($conditions as $condition) {
            /** @var RedirectConditionInterface $condition */
            $condition = resolve($condition);

            if (! in_array($condition->getCode(), $conditionCodes, true)) {
                continue;
            }

            $enabledConditions[] = $condition;
        }

        return $enabledConditions;
    }
}
