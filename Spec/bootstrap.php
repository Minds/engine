<?php

ini_set('memory_limit', '256M');

# Redirect error_log output to blackhole
ini_set('error_log', '/dev/null');

global $CONFIG;

date_default_timezone_set('UTC');

define('__MINDS_ROOT__', dirname(__FILE__) . '/../');

require_once(dirname(__FILE__) . '/mocks.php');
require_once(dirname(__FILE__) . '/pulsar-mocks.php');

$minds = new Minds\Core\Minds();

$CONFIG = Minds\Core\Di\Di::_()->get('Config');
$CONFIG->default_access = 2;
$CONFIG->site_guid = 0;
$CONFIG->cassandra = new stdClass;
$CONFIG->cassandra->keyspace = 'phpspec';
$CONFIG->cassandra->servers = ['127.0.0.1'];
$CONFIG->cassandra->cql_servers = ['127.0.0.1'];
$CONFIG->cassandra->username = 'cassandra';
$CONFIG->cassandra->password = 'cassandra';

$CONFIG->payments = [
    'stripe' => [
        'api_key' => 'phpspec',
    ],
];

$CONFIG->cypress = [
    'shared_key' => 'random-key',
];

$CONFIG->plus = [
    'support_tier_urn' => 'plus_support_tier_urn',
];

$minds->loadLegacy();
