<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quarter Grace Period Days
    |--------------------------------------------------------------------------
    |
    | This value determines the number of days after a quarter ends during
    | which replacement PRs can still select items from the previous quarter.
    |
    | For example, if set to 14 days:
    | - Q1 ends on March 31
    | - Grace period: April 1 - April 14
    | - During this period, replacement PRs can select Q1 items
    |
    | This grace period ONLY applies to replacement PRs (PRs created to
    | replace returned PRs), not to regular PR creation.
    |
    | Default: 14 days (2 weeks)
    |
    */
    'quarter_grace_period_days' => env('PPMP_GRACE_PERIOD_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Enable Quarter Grace Period
    |--------------------------------------------------------------------------
    |
    | This flag allows you to completely enable or disable the grace period
    | feature. When disabled, replacement PRs will follow the same strict
    | quarter rules as regular PRs.
    |
    | Default: true (enabled)
    |
    */
    'enable_grace_period' => env('PPMP_ENABLE_GRACE_PERIOD', true),
];
