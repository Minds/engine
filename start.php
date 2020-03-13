<?php
/**
 * Bootstraps Minds engine
 */

/**
 * The time with microseconds when the Minds engine was started.
 *
 * @global float
 */
global $START_MICROTIME;
$START_MICROTIME = microtime(true);
date_default_timezone_set('UTC');

define('__MINDS_ROOT__', dirname(__FILE__));

/**
 * Autoloader
 */
require_once(__MINDS_ROOT__ . '/vendor/autoload.php');

// Sentry
Sentry\init([
    'dsn' => getenv('SENTRY_DSN'),
    'release' => getenv('MINDS_VERSION') ?: 'Unknown',
    'environment' => getenv('MINDS_ENV') ?: 'development',
    'send_default_pii' => false,
]);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$minds = new Minds\Core\Minds();
$minds->start();
