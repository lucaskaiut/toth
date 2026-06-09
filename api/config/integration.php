<?php

return [
    'providers' => [
        'nox' => [
            'label' => 'Nox Scheduler',
            'base_url' => env('NOX_BASE_URL', 'http://nox-scheduler-api'),
            'timeout' => (int) env('NOX_TIMEOUT', 30),
        ],
    ],

    'max_tool_iterations' => (int) env('INTEGRATION_MAX_TOOL_ITERATIONS', 5),
];
