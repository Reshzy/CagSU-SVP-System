<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dev Tools Enabled
    |--------------------------------------------------------------------------
    |
    | When true, System Administrators can access the Dev Tools hub for
    | creating test purchase requests, managing budgets, importing PPMPs,
    | and jumping workflow stages. Keep this false in production unless
    | you intentionally need these overrides.
    |
    */

    'enabled' => (bool) env('DEV_TOOLS_ENABLED', false),

];
