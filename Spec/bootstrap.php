<?php

ini_set('memory_limit', '512M');

# Redirect error_log output to blackhole
ini_set('error_log', '/dev/null');

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

global $CONFIG;

date_default_timezone_set('UTC');

define('__MINDS_ROOT__', dirname(__FILE__) . '/../');

require_once(dirname(__FILE__) . '/mocks.php');
require_once(dirname(__FILE__) . '/pulsar-mocks.php');

$minds = new Minds\Core\Minds();

$CONFIG = Minds\Core\Di\Di::_()->get('Config');
$CONFIG->default_access = 2;
$CONFIG->site_guid = 0;
$CONFIG->site_url = "https://phpspec.minds.io/";
$CONFIG->cassandra = [
    'keyspace' => 'phpspec',
    'servers' => ['127.0.0.1'],
    'cql_servers' => ['127.0.0.1'],
    'username' => 'cassandra',
    'password' => 'cassandra',
];
$CONFIG->set('dataroot', '/data/');

$CONFIG->set('development_mode', false);
$CONFIG->set('system_user_guid', '100000000000000519');

$CONFIG->set('oci', [
    'oss_s3_client' => [
        'endpoint' => '',
        'key' => '',
        'secret' => ''
    ],
    'api_auth' => [
        'private_key' => '',
        'tenant_id' => '',
        'user_id' => '',
        'key_fingerprint' => ''
    ]
]);

$CONFIG->set('transcoder', [
    'use_oracle_oss' => false,
]);

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
        'test_api_key' => 'phpspec_test_creds',
        'test_email' => 'teststripe@minds.io',
    ],
];

$CONFIG->cypress = [
    'shared_key' => base64_encode(openssl_random_pseudo_bytes(256)),
];

$CONFIG->pro = [
    'stripe_account' => null
];

$CONFIG->plus = [
    'support_tier_urn' => 'plus_support_tier_urn',
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
$CONFIG->set('development_mode', false);
$CONFIG->set('trending_tags_development_mode', false);

$CONFIG->set('site_name', 'PHPSpec Minds');


$minds->loadLegacy();
