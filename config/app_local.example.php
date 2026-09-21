<?php

use function Cake\Core\env;

return [

    'debug' => filter_var(env('DEBUG', true), FILTER_VALIDATE_BOOLEAN),

    'Security' => [
        'salt' => env('SECURITY_SALT', '__SALT__'),
    ],

    'Datasources' => [
        'default' => [
            'host' => 'localhost',

            'username' => 'my_app',
            'password' => 'secret',

            'database' => 'my_app',

            'url' => env('DATABASE_URL', null),
        ],

        'test' => [
            'host' => 'localhost',

            'username' => 'my_app',
            'password' => 'secret',
            'database' => 'test_myapp',

            'url' => env('DATABASE_TEST_URL', 'sqlite://127.0.0.1/tmp/tests.sqlite'),
        ],
    ],

    'EmailTransport' => [
        'default' => [
            'host' => 'localhost',
            'port' => 25,
            'username' => null,
            'password' => null,
            'client' => null,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],

    'Captcha' => [
        'turnstile' => [

            'siteKey' => '3x00000000000000000000FF',

            'secretKey' => '1x0000000000000000000000000000000AA',
        ],
    ],

    /*
     * AI chatbot (DeepSeek, OpenAI-compatible). Leave apiKey empty to disable.
     */
    'Chatbot' => [
        'apiKey' => env('CHATBOT_API_KEY', ''),
        'endpoint' => env('CHATBOT_ENDPOINT', 'https://api.deepseek.com/chat/completions'),
        'model' => env('CHATBOT_MODEL', 'deepseek-chat'),
    ],
];
