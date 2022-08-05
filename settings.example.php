<?php

$CONFIG = Minds\Core\Di\Di::_()->get('Config');

$CONFIG->minds_debug = true;

/*
 * Cassandra configuration
 */
$CONFIG->cassandra = [
    'keyspace' => '{{cassandra-keyspace}}',
    'servers' => ['{{cassandra-server}}'],
    'cql_servers' => ['{{cassandra-server}}'],
    'username' => 'cassandra',
    'password' => 'cassandra',
];

$CONFIG->redis = [
    'master' => 'redis',
    'slave' => 'redis',
];

$CONFIG->rabbitmq = [
    'host' => 'rabbitmq',
    'port' => 5672,
    'username' => 'guest',
    'password' => 'guest',
];

$CONFIG->set('pulsar', [
    'host' => 'pulsar',
    'port' => 6650,
    'ssl' => false,
    'ssl_skip_verify' => false,
    //'ssl_cert_path' => '',
]);

$CONFIG->set('cloudflare', [
    'api_key' => '',
    'email' => '',
    'account_id' => '',
]);

$CONFIG->disable_secure_cookies = true;

$CONFIG->set('sessions', [
    'private_key' => 'file:///.dev/minds.pem',
    'public_key' => 'file:///.dev/minds.pub',
]);

$CONFIG->set('oauth', [
    'clients' => [
        'mobile' => [
            'secret' => '',
        ],
    ],
    'encryption_key' => '{{ jwt-secret }}',
]);

$CONFIG->set(
    'report_reasons',
    [
        [
            'value' => 1,
            'label' => 'Illegal',
            'hasMore' => true,
            'reasons' => [
                ['value' => 1, 'label' => 'Terrorism'],
                ['value' => 2, 'label' => 'Paedophilia'],
                ['value' => 3, 'label' => 'Extortion'],
                ['value' => 4, 'label' => 'Fraud'],
                ['value' => 5, 'label' => 'Revenge Porn'],
                ['value' => 6, 'label' => 'Sex trafficking'],
            ],
        ],
        [
            'value' => 2,
            'label' => 'NSFW (not safe for work)',
            'hasMore' => true,
            'reasons' => [ // Explicit reasons
                ['value' => 1, 'label' => 'Nudity'],
                ['value' => 2, 'label' => 'Pornography'],
                ['value' => 3, 'label' => 'Profanity'],
                ['value' => 4, 'label' => 'Violance and Gore'],
                ['value' => 5, 'label' => 'Race, Religion, Gender'],
            ],
        ],
        [
            'value' => 3,
            'label' => 'Encourages or incites violence',
            'hasMore' => false,
        ],
        [
            'value' => 4,
            'label' => 'Harassment',
            'hasMore' => false,
        ],
        [
            'value' => 5,
            'label' => 'Personal and confidential information',
            'hasMore' => false,
        ],
        [
            'value' => 7,
            'label' => 'Impersonates',
            'hasMore' => false,
        ],
        [
            'value' => 8,
            'label' => 'Spam',
            'hasMore' => false,
        ],
        [
            'value' => 10,
            'label' => 'Infringes my copyright',
            'hasMore' => true,
        ],
        [
            'value' => 12,
            'label' => 'Incorrect use of hashtags',
            'hasMore' => false,
        ],
        [
            'value' => 13,
            'label' => 'Malware',
            'hasMore' => false,
        ],
        [
            'value' => 15,
            'label' => 'Trademark infringement',
            'hasMore' => false,
        ],
        [
            'value' => 16,
            'label' => 'Token manipulation',
            'hasMore' => false,
        ],
        [
            'value' => 17,
            'label' => 'Security',
            'hasMore' => true,
            'reasons' => [
                ['value' => 1, 'label' => 'Hacked account']
            ],
        ],
        ['value' => 11,
            'label' => 'Another reason',
            'hasMore' => true,
        ],

    ]
);

/*
 * Other Elgg Settings
 */
$CONFIG->installed = true;
$CONFIG->path = '{{path}}';
$CONFIG->plugins_path = '{{path}}plugins/';
$CONFIG->pluginspath = '{{path}}plugins/';
$CONFIG->dataroot = '{{dataroot}}';
$CONFIG->default_site = '{{default-site}}';
$CONFIG->site_id = '{{default-site}}';
$CONFIG->site_name = '{{site-name}}';
$CONFIG->__site_secret__ = '{{site-secret}}';
// $CONFIG->cdn_url = 'http://{{domain}}/';
$CONFIG->site_url = 'http://{{domain}}/';
$CONFIG->cdn_url = 'http://{{domain}}/';
$CONFIG->cdn_assets_url = 'http://{{domain}}/';
$CONFIG->zmq_server = 'localhost';
$CONFIG->checkout_url = 'http://{{checkout_domain}}/';

/*
 * Overrides default system cache path from inside data root to custom location.
 *
 * @global string $CONFIG->system_cache_path
 * @name $CONFIG->system_cache_path
 */
$CONFIG->system_cache_path = '{{cache-path}}';

/*
 * Elasticsearch Settings
 */


$CONFIG->elasticsearch = [
    'hosts' => ['elasticsearch'],
    'index' => 'minds_badger',
    'metrics_index' => 'minds-metrics',
    'tags_index' => 'minds-trending-hashtags',
    // ES 7.x
    'indexes' => [
        'search_prefix' => 'minds-search',
        'boost' => 'minds-boost',
        'graph' => 'minds-graph',
        'metrics' => 'minds-metrics',
        'tags' => 'minds-hashtags',
        'clustered_entities' => 'minds-clustered-entities-feed'
    ],
    'username' => null,
    'password' => null,
    'cert' => '/var/secure/elasticsearch.crt',
];

// Vitess (MySQL) configuration
$CONFIG->mysql = [
    'host' => 'vitess:15306',
    'db' => 'minds',
    'user' => 'user',
    'ssl_skip_verify' => true,
];

/*
 * Memcache setup (optional)
 * This is where you may optionally set up memcache.
 *
 * Requirements:
 * 	1) One or more memcache servers (http://www.danga.com/memcached/)
 *  2) PHP memcache wrapper (http://uk.php.net/manual/en/memcache.setup.php)
 *
 * Note: Multiple server support is only available on server 1.2.1
 * or higher with PECL library > 2.0.0
 */

/*$CONFIG->memcache = true;

$CONFIG->memcache_servers = array (
   array('server1', 11211),
   array('server2', 11211)
);*/

/*
 * Queue Settings
 */
$CONFIG->queue = [
    'exchange' => '{{ queue-exchange }}',
];

/*
 * Use non-standard headers for broken MTAs.
 *
 * The default header EOL for headers is \r\n.  This causes problems
 * on some broken MTAs.  Setting this to TRUE will cause Elgg to use
 * \n, which will fix some problems sending email on broken MTAs.
 *
 * @global bool $CONFIG->broken_mta
 */
$CONFIG->broken_mta = false;

/*
 * Minimum password length
 *
 * This value is used when validating a user's password during registration.
 *
 * @global int $CONFIG->min_password_length
 */
$CONFIG->min_password_length = 6;

$CONFIG->set('plugins', [
    'Messenger',
    'oauth2',
    'guard',
]);

$CONFIG->set('sockets', [
    'jwt_secret' => '{{jwt-secret}}',
    'jwt_domain' => '{{jwt-domain}}',
    'server_uri' => '{{socket-server-uri}}'
]);

$CONFIG->set('facebook', [
    'app_id' => '{{facebook-app-id}}',
    'app_secret' => '{{facebook-app-secret}}',
]);

$CONFIG->set('twitter', [
    'api_key' => '{{twitter-app-id}}',
    'api_secret' => '{{twitter-app-id}}',
    'bearer_token' => '',
    'min_followers_for_sync' => 25000,
]);

$CONFIG->set('twilio', [
    'account_sid' => '{{twilio-account-sid}}',
    'auth_token' => '{{twilio-auth-token}}',
    'from' => '{{twilio-from}}',
    'verify' => [
        'service_sid' => '{{verify-service-sid}}'
    ]
]);

$CONFIG->set('google', [
    'geolocation' => '{{google-api-key}}',
    'translation' => '{{google-api-key}}',
    'push' => '{{google-api-key}}',
    'analytics' => [
        'service_account' => [
            'key_path' => __DIR__ . '/.auth/analytics.json',
        ],
        'ads' => '', // get it from https://ga-dev-tools.appspot.com/account-explorer/
    ],

    'youtube' => [
        'api_key' => '{{yt-api-key}}',
        'max_daily_imports' => 10,
    ],
    'bigquery' => [
        'project_id' => '',
        'key_file_path' =>  __DIR__ . '/.auth/bigquery.json'
    ],
]);

$CONFIG->set('apple', [
    'sandbox' => '{{apple-sandbox-enabled}}',
    'cert' => '{{apple-certificate}}',
]);

$CONFIG->set('boost', [
    'network' => [
        'min' => 100,
        'max' => 5000,
    ],
    'peer' => [
        'min' => 100,
        'max' => 5000000,
    ],
]);

/* Maximum view per day */
$CONFIG->set('max_daily_boost_views', 10000);

$CONFIG->set('encryptionKeys', [
    'email' => [
        'private' => '{{email-private-key}}',
        'public' => '{{email-public-key}}',
    ],
    'phone_number' => [
        'private' => '{{phone-number-private-key}}',
        'public' => '{{phone-number-public-key}}',
    ],
]);

$CONFIG->set('payouts', [
    'initialDate' => '2016-11-01',
    'retentionDays' => 40,
    'minimumAmount' => 100,
    'userPercentage' => 0.8,
]);

$CONFIG->set('payments', [
    'stripe' => [
        'api_key' => '',
        'transfers' => [
            'source_type' => 'bank_account',
        ],
    ],
]);

$CONFIG->set('sandbox', [
    'enabled' => false,
    'default' => [
        'guid' => '',
    ],
    'merchant' => [
        'guid' => '',
    ],
]);

$CONFIG->set('sns_secret', '{{sns-secret}}');

$CONFIG->set('blockchain', [
    'sale' => 'sale',
    'testnet' => false,

    'rpc_endpoints' => [
        'https://mainnet.infura.io/v3/708b51690a43476092936f9818f8c4fa',
    ],

    //'network_address' => 'https://rinkeby.infura.io/',
    'proxy_rpc_endpoint' => 'https://mainnet.infura.io/v3/708b51690a43476092936f9818f8c4fa',

    'client_network' => 1, // 1 = main ethereum network; 4 = test rinkeby; 1337 coin repo's testserver.sh

    'default_gas_price' => 40,
    'server_gas_price' => 40,
    'token_symbol' => 'status',

    'token_address' => '0xb26631c6dda06ad89b93c71400d25692de89c068',
    'contracts' => [
        'token_sale_event' => [
            'contract_address' => '0xf3c9dbb9598c21fe64a67d0586adb5d6eb66bc63',
            'wallet_address' => '0x1820fFAD63fD64d7077Da4355e9641dfFf4DAD0d',
            'wallet_pkey' => '',
            'eth_rate' => 2000, //1 ETH = 2,000 TOKENS
            'auto_issue_cap' => '120000000000000000000000', //60ETH (120,000 tokens) $30,000 USD
        ],
        'withdraw' => [
            'contract_address' => '0xdd10ccb3100980ecfdcbb1175033f0c8fa40548c',
            'wallet_address' => '0x14E421986C5ff2951979987Cdd82Fa3C0637D569',
            'wallet_pkey' => '',
            'limit_exemptions' => [
            ],
            'limit' => 25000,
        ],
        'bonus' => [
            'wallet_address' => '0x461f1C5768cDB7E567A84E22b19db0eABa069BaD',
            'wallet_pkey' => '',
        ],
        'boost' => [
            'contract_address' => '0x112ca67c8e9a6ac65e1a2753613d37b89ab7436b',
            'wallet_address' => '0xdd04D9636F1944FE24f1b4E51Ba77a6CD23b6fE3',
            'wallet_pkey' => '',
        ],
        'wire' => [
            'plus_address' => '',
            'plus_guid' => '', // Your plus user's guid.
            'contract_address' => '0x4b637bba81d24657d4c6acc173275f3e11a8d5d7',
            'wallet_address' => '0x4CDc1C1fd1A3F4DD63231afF8c16501BcC11Df95',
            'wallet_pkey' => '',
        ],
    ],

    'eth_rate' => 2000, //1 ETH = 2,000 TOKENS

    'disable_creditcards' => true,

    'offchain' => [
        'cap' => 1000,
    ],

    'liquidity_positions' => [
        'approved_pairs' => [
            "0x8ff2fd6f94cdce7c56bb11328eb9928c9483aa66",
            "0x9f9c34109bd5fa460fad4c14fb51df99339cb46c"
        ]
    ],

    'transak' => [
        'api_key' => '',
        'environment' => 'staging',
    ],

    'mw3' => '/usr/bin/env node ' . __MINDS_ROOT__ . '/../mw3/index.js',

    'web3_service' => [
        'wallet_encryption_key' => '',
        'base_url' => 'http://192.168.0.16:3333/'
    ],

    'skale' => [
        'development_mode' => true,
        'rpc_endpoints' => [
            'https://testnet-proxy.skalenodes.com/v1/whispering-turais'
        ],
        'minds_token_address' => '0x4E8792878Bd0CC7a19d2a5Bb345Ab18a6624c866',
        'gas_price_wei' => 100000,
        'default_sfuel_distributor_guid' => '1285556080390180877',
        'default_sfuel_distribution_amount_wei' => 220000000000,
        'sfuel_low_threshold' => 8801000000,
        'transfer_gas_limit' => 51280,
        'confirmation_timeout_seconds' => 60,
        'confirmation_polling_gap_seconds' => 5,
        'balance_cache_ttl_seconds' => 60
    ],
]);

$CONFIG->set('blockchain_override', [
    'pledge' => [
        // ...
    ],
]);

$CONFIG->set('token_exchange_rate', 1.25);

$CONFIG->set('plus', [
    'handler' => '',
    'tokens' => [
        'month' => 5,
        'year' => 50,
    ],
]);

$CONFIG->set('iframely', [
    'key' => 'f4da1791510e9dd6ad63bc',
    'origin' => 'minds',
]);

$CONFIG->set('default_email_subscriptions', [
    [
        'campaign' => 'when',
        'topic' => 'unread_notifications',
        'value' => true,
    ],
    [
        'campaign' => 'when',
        'topic' => 'wire_received',
        'value' => true,
    ],
    [
        'campaign' => 'when',
        'topic' => 'boost_completed',
        'value' => true,
    ],

    [
        'campaign' => 'with',
        'topic' => 'top_posts',
        'value' => 'periodically',
    ],
    [
        'campaign' => 'with',
        'topic' => 'channel_improvement_tips',
        'value' => true,
    ],
    [
        'campaign' => 'with',
        'topic' => 'posts_missed_since_login',
        'value' => true,
    ],
    [
        'campaign' => 'with',
        'topic' => 'new_channels',
        'value' => true,
    ],

    [
        'campaign' => 'global',
        'topic' => 'minds_news',
        'value' => false,
    ],
    [
        'campaign' => 'global',
        'topic' => 'minds_tips',
        'value' => true,
    ],
    [
        'campaign' => 'global',
        'topic' => 'exclusive_promotions',
        'value' => false,
    ],
]);

$CONFIG->set('i18n', [
    'languages' => [
        'en' => 'English',
        'es' => 'Español',
    ],
]);

// blacklist of internal IPs / URLs to block from curl requests
$CONFIG->set('internal_blacklist', []);

$CONFIG->set('tags', [
    'art', 'music', 'journalism', 'blockchain', 'freespeech', 'news', 'gaming', 'myphoto', 'nature', 'photography', 'politics', 'top', 'bitcoin', 'technology', 'food', 'animals', 'health', 'science', 'philosophy', 'comedy', 'film', 'minds',
]);

// served initially only when under minds-3216-default-tags-v2 - should eventually replace v1 tags.
$CONFIG->set('tags_v2', [
    'Animals', 'Anime', 'Art', 'Comedy', 'Crypto', 'Food', 'Freedom', 'Gaming', 'Literature', 'Memes', 'Movies', 'Music', 'Nature', 'NSFW', 'News', 'Outdoors', 'Photography', 'Politics', 'Religion', 'Sports', 'Technology', 'Travel'
]);

$CONFIG->set('steward_guid', '');
$CONFIG->set('steward_autoconfirm', false);
$CONFIG->set('development_mode', '{{development_mode}}');

// load discovery content irrespective of activity levels.
$CONFIG->set('discovery_development_mode', true);

// Show tags that are not trending in last 24h.
$CONFIG->set('trending_tags_development_mode', true);

$CONFIG->set('max_video_length', 900);

$CONFIG->set('max_video_length_plus', 1860);

// You can find a list of all in use feature flags at Core/Features/Provider.php
$CONFIG->set('features', [
    'psr7-router' => true,
    'es-feeds' => false,
    'helpdesk' => true,
    'top-feeds' => true,
    'cassandra-notifications' => true,
    'dark-mode' => true,
    'allow-comments-toggle' => false,
    'permissions' => false,
    'pro' => false,
    'webtorrent' => false,
    'top-feeds-by-age' => true,
    'ux-2020' => true,
    'modal-pager' => true,
    'wallet-upgrade' => true,
    'subscriber-conversations' => true,
    'activity-modal' => false,
    'withdrawal-console' => true,
    'twilio-verify' => true,
    'helpdesk-2021' => true,
    'discovery-default-tags' => true,
    'skale' => true,
    'polygon' => true,
    'web3-service-withdrawals' => false,
]);

$CONFIG->set('email', [
    'sender' => [
        'email' => 'no-reply@minds.com',
        'name' => 'Minds'
    ],
    'smtp' => [
        'host' => '',
        'username' => '',
        'password' => '',
        'port' => 465,
    ],
]);

/* Maximum video length for non-plus users */
$CONFIG->set('max_video_length', 900);

/* Maximum video length for plus */
$CONFIG->set('max_video_length_plus', 1860);

/* Maximum video file size, in bytes */
$CONFIG->set('max_video_file_size', 3900000000);

$CONFIG->set('aws', [
    'key' => '',
    'secret' => '',
    'useRoles' => false,
    'bucket' => 'cinemr',
    'staticStorage' => 'cinemr_dev',
    'region' => 'us-east-1',
    'account_id' => '324044571751',
    'elastic_transcoder' => [
        'pipeline_id' => '1401290942976-efm3xj',
        'presets' => [
            '360.mp4' => '1351620000001-000040',
            '720.mp4' => '1351620000001-000010',
            '360.webm' => '1404848610623-0blc5v',
            '720.webm' => '1404852762051-zzvwfq',
        ],
        'dir' => 'cinemr_dev',
    ],
    'queue' => [
        'namespace' => 'EmiDev',
        'wait_seconds' => 3,
    ],
]);

$CONFIG->set('transcode', [
    //'free_threshold' => 900, // 15 minutes
    'free_threshold' => 2,
    'hd_price' => 1, // tokens
    'fhd_price' => 1.5,  // tokens
]);

$CONFIG->set('transcoder', [
    'threads' => 4,
    'dir' => 'cinemr_dev',
    'presets' => [
        [
            'width' => 640,
            'height' => 360,
            'bitrate' => 500,
            'audio_bitrate' => 80,
            'formats' => ['mp4', 'webm'],
            'pro' => false,
        ],
        [
            'width' => 1280,
            'height' => 720,
            'bitrate' => 2000,
            'audio_bitrate' => 128,
            'formats' => ['mp4', 'webm'],
            'pro' => false,
        ],
        [
            'width' => 1920,
            'height' => 1080,
            'bitrate' => 2000,
            'audio_bitrate' => 128,
            'formats' => ['mp4', 'webm'],
            'pro' => true,
        ],
    ],
]);

$CONFIG->cinemr_url = 'https://cinemr.s3.amazonaws.com/cinemr_dev/';

$CONFIG->mongodb_servers = ['minds_mongo_1'];

$CONFIG->set('last_tos_update', 1);

$CONFIG->set('gitlab', [
    'project_id' => [
        'mobile' => '10171280', // project id mobile
        'front' => '10152778', // project id front
    ],
    'private_key' => '',
]);

$CONFIG->set('pro', [
    'handler' => '',
    'root_domains' => ['minds.com', 'www.minds.com', 'localhost', 'localhost:8080', 'localhost:4200', 'nginx', 'host.docker.internal'],
    'subdomain_suffix' => 'minds.com',
    'dynamodb_table_name' => 'traefik',
]);

$CONFIG->set('contact_details', [
    'name' => 'Minds',
    'email' => 'info@minds.com',
]);

$CONFIG->set('upgrades', [
    'pro' => [
        'monthly' => [
            'usd' => 60,
        ],
        'yearly' => [
            'usd' => 600,
        ],
        'lifetime' => [
            'tokens' => 20000
        ],
    ],
    'plus' => [
        'monthly' => [
            'usd' => 7,
        ],
        'yearly' => [
            'usd' => 60,
        ],
        'lifetime' => [
            'tokens' => 2500
        ],
    ],
]);

$CONFIG->set('email_confirmation', [
    'signing_key' => '',
    'expiration' => 172800, // 48 hours
]);

$CONFIG->set('unleash', [
    'apiUrl' => '',
    'instanceId' => '',
    'applicationName' => '',
    'pollingIntervalSeconds' => 300,
    'metricsIntervalSeconds' => 15,
]);

$CONFIG->set('captcha', [
    'jwt_secret' => '{{site-secret}}',
    'bypass_key' => '{{site-secret}}',
    'friendly_captcha' => [
        'signing_secret' => '{{site-secret}}',
    ]
]);

$CONFIG->set('cypress', [
    'shared_key' => '{{site-secret}}',
]);

$CONFIG->set('sendwyre', [
    'baseUrl' => 'https://api.sendwyre.com/',
    'accountId' => '',
    'secretKey' => '',
    'redirectUrl' => 'https://www.minds.com/token',
    'failureRedirectUrl' => 'https://www.minds.com/token?failed=1',
]);

$CONFIG->set('onboarding_modal_timestamp', 0);
$CONFIG->set('onboarding_v2_timestamp', 0);


$CONFIG->set('snowplow', [
    'collector_uri' => 'host.docker.internal:8090',
    'proto' => 'http',
]);

$CONFIG->set('arweave', [
    'host' => 'host.docker.internal',
    'port' => 5000,
]);

/* In characters */
$CONFIG->set('max_name_length', 50);


$CONFIG->SET('zendesk', [
    'private_key' => '',
    'url' => [
        'base' => '',
        'jwt_route' => 'access/jwt'
    ]
]);

$CONFIG->set('statuspage_io', [
    'url' => 'https://status.minds.com/'
]);

$CONFIG->set('default_recommendations_user', '100000000000000519');

$CONFIG->set('growthbook', [
    'features_endpoint' => 'https://growthbook-api.minds.com/api/features/key_c98323bd51a6b3ab',
]);

$CONFIG->set('user_quality_score', [
    'belowSpamRiskThreshold' => 0.4
]);

$CONFIG->set('did', [
    'domain' => 'localhost:8080',
    'uniresolver' => [
        'base_url' => 'https://dev.uniresolver.io/',
    ]
]);

$CONFIG->set('webpush_vapid_details', [
    'public_key' => "{{webpush_vapid_public_key}}",
    'private_key' => "{{webpush_vapid_private_key}}",
    'subject' => "{{webpush_vapid_subject}}"
]);

$CONFIG->set('seen-entities-weight', 0.01);

$CONFIG->set('survey_links', [
    'post_signup' => '',
]);

$CONFIG->set('metascraper', [
    'base_url' => 'metascraper:3334/',
    'ttl_seconds' => 86400,
    'request_timeout' => 30,
    'bypass_cache' => true
]);

$CONFIG->set('jury', [
    'development_mode' => false
]);
