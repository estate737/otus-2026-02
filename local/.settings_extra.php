<?php

return [
    'exception_handling' => [
        'value' => [
            'debug' => true,
            'handled_errors_types' => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_WARNING,
            'exception_errors_types' => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE & ~E_COMPILE_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_WARNING,
            'ignore_silence' => false,
            'assertion_throws_exception' => true,
            'assertion_error_type' => 256,
            'log' => [
                'class_name' => '\\App\\Debug\\Log',
                'settings' => [
                    'file' => 'local/logs/exceptions.log',
                    'log_size' => 1000000,
                ],
            ],
        ],
        'readonly' => false,
    ],
];
