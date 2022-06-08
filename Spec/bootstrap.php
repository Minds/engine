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
$CONFIG->cassandra = [
    'keyspace' => 'phpspec',
    'servers' => ['127.0.0.1'],
    'cql_servers' => ['127.0.0.1'],
    'username' => 'cassandra',
    'password' => 'cassandra',
];

$CONFIG->elasticsearch = [
    'hosts' => [ 'phpspec:9200' ],
    'indexes' => [
        'search_prefix' => 'minds-search',
        'tags' => 'minds-hashtags',
    ]
];

$CONFIG->payments = [
    'stripe' => [
        'api_key' => 'phpspec',
    ],
];

$CONFIG->cypress = [
    'shared_key' => 'random-key',
];

$CONFIG->pro = [
    'stripe_account' => null
];

$CONFIG->plus = [
    'support_tier_urn' => 'plus_support_tier_urn',
];

$CONFIG->snowplow = [
    'collector_uri' => ''
];

$CONFIG->sessions = [
    'public_key' => '/.dev/minds.pub',
    'private_key' => '/.dev/minds.pem',
];


$CONFIG->redis = [
    'master' => 'phpspec',
    'slave' => 'phpspec'
];

$CONFIG->set('sockets', [
    'jwt_secret' => 'secret',
    'jwt_domain' => 'localhost:8080',
    'server_uri' => 'localhost:8010'
]);

$minds->loadLegacy();
