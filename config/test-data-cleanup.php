<?php

$environment = strtolower(
    (string) env('APP_ENV', 'production')
);

return [
    /*
     * Local/testing/development/staging are enabled by default.
     *
     * IMPORTANT:
     * Do not call app()->environment() inside this config file.
     * Laravel is still loading configuration at this point and the
     * container `env` binding may not yet exist.
     *
     * A server using APP_ENV=production requires explicit opt-in:
     * TEST_DATA_CLEANUP_ENABLED=true
     */
    'enabled' =>
        in_array(
            $environment,
            ['local', 'testing', 'development', 'staging'],
            true
        )
        || filter_var(
            env('TEST_DATA_CLEANUP_ENABLED', false),
            FILTER_VALIDATE_BOOL
        ),
];
