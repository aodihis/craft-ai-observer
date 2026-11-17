<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Observe AI agent activity
    |--------------------------------------------------------------------------
    |
    | When enabled, the package listens to the laravel/ai agent events and
    | normalizes them into telemetry records. When disabled, no listener is
    | registered and an agent run costs nothing.
    |
    */

    'enabled' => env('AI_OBSERVER_ENABLED', true),

];
