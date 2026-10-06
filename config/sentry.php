<?php

/**
 * Sentry configuration overrides for the Family Tree Platform.
 *
 * Only the security- and environment-relevant keys are declared here; the
 * remaining options fall back to the sentry-laravel package defaults via
 * mergeConfigFrom().
 */
return [

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),

    'release' => env('SENTRY_RELEASE'),

    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE') === null
        ? 0.0
        : (float) env('SENTRY_TRACES_SAMPLE_RATE'),

    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE') === null
        ? 0.0
        : (float) env('SENTRY_PROFILES_SAMPLE_RATE'),

    // Never send IPs, headers, cookies, request bodies, or user identifiers by default.
    'send_default_pii' => false,

];
