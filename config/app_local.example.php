<?php

use function Cake\Core\env;

/*
 * Local configuration file to provide any overrides to your app.php configuration.
 * Copy and save this file as app_local.php and make changes as required.
 * Note: It is not recommended to commit files with credentials such as app_local.php
 * into source code version control.
 */
return [
    /*
     * Debug Level:
     *
     * Production Mode:
     * false: No error messages, errors, or warnings shown.
     *
     * Development Mode:
     * true: Errors and warnings shown.
     */
    'debug' => filter_var(env('DEBUG', true), FILTER_VALIDATE_BOOLEAN),

    /*
     * Security and encryption configuration
     *
     * - salt - A random string used in security hashing methods.
     *   The salt value is also used as the encryption key.
     *   You should treat it as extremely sensitive data.
     */
    'Security' => [
        'salt' => env('SECURITY_SALT', '__SALT__'),
    ],

    /*
     * Connection information used by the ORM to connect
     * to your application's datastores.
     *
     * See app.php for more configuration options.
     */
    'Datasources' => [
        'default' => [
            'host' => 'localhost',
            /*
             * CakePHP will use the default DB port based on the driver selected
             * MySQL on MAMP uses port 8889, MAMP users will want to uncomment
             * the following line and set the port accordingly
             */
            //'port' => 'non_standard_port_number',

            'username' => 'my_app',
            'password' => 'secret',

            'database' => 'my_app',
            /*
             * If not using the default 'public' schema with the PostgreSQL driver
             * set it here.
             */
            //'schema' => 'myapp',

            /*
             * You can use a DSN string to set the entire configuration
             */
            'url' => env('DATABASE_URL', null),
        ],

        /*
         * The test connection is used during the test suite.
         */
        'test' => [
            'host' => 'localhost',
            //'port' => 'non_standard_port_number',
            'username' => 'my_app',
            'password' => 'secret',
            'database' => 'test_myapp',
            //'schema' => 'myapp',
            'url' => env('DATABASE_TEST_URL', null),
        ],
    ],

    /*
     * Google reCAPTCHA v2 keys for the public contact form.
     *
     * The keys default to EMPTY so a misconfigured deployment fails closed
     * (an empty secret makes verification reject every submission) instead of
     * silently letting spam through. Set real keys from
     * https://www.google.com/recaptcha/admin via the RECAPTCHA_SITEKEY /
     * RECAPTCHA_SECRET environment variables before going live.
     *
     * For local development you can either:
     *   - set RECAPTCHA_ENABLED=false to skip verification entirely, or
     *   - paste Google's universal test keys (6LeIxAcTAAAAA...) which always
     *     pass. Those test keys are refused automatically when debug is off.
     */
    'Recaptcha' => [
        'enabled' => filter_var(env('RECAPTCHA_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'sitekey' => env('RECAPTCHA_SITEKEY', ''),
        'secret' => env('RECAPTCHA_SECRET', ''),
    ],

    /*
     * Email configuration, used by the "forgot password" flow.
     *
     * Leave this block commented out for local development: app.php defaults
     * to the Debug transport, which sends nothing and instead writes the
     * rendered message (reset link included) to logs/debug.log.
     *
     * To go live on cPanel, uncomment it and fill in the values from
     * "Email Accounts > Connect Devices" in the cPanel dashboard. `from` has
     * to be a mailbox the server hosts, otherwise delivery is rejected.
     *
     * See app.php for more configuration options.
     */
    //'EmailTransport' => [
    //    'default' => [
    //        'className' => \Cake\Mailer\Transport\SmtpTransport::class,
    //        'host' => 'mail.your-domain.com',
    //        'port' => 587,
    //        'username' => 'no-reply@your-domain.com',
    //        'password' => 'the-mailbox-password',
    //        'tls' => true,
    //        'client' => null,
    //        'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
    //    ],
    //],
    //'Email' => [
    //    'default' => [
    //        'transport' => 'default',
    //        'from' => ['no-reply@your-domain.com' => 'Eco Glow Lighting'],
    //    ],
    //],
];
