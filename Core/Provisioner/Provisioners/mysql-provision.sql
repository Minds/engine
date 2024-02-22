CREATE TABLE IF NOT EXISTS friends
(
    user_guid   bigint,
    friend_guid bigint,
    timestamp   timestamp,
    PRIMARY KEY (user_guid, friend_guid)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS pseudo_seen_entities
(
    pseudo_id           varchar(128),
    entity_guid         bigint,
    last_seen_timestamp timestamp,
    PRIMARY KEY (pseudo_id, entity_guid)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS recommendations_clustered_recs
(
    cluster_id        int,
    entity_guid       bigint,
    entity_owner_guid bigint,
    score             float(5, 2),
    first_engaged     timestamp NULL DEFAULT NULL,
    last_engaged      timestamp NULL DEFAULT NULL,
    last_updated      timestamp NULL DEFAULT NULL,
    time_created      timestamp      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    total_views       bigint,
    total_engagement  bigint,
    PRIMARY KEY (cluster_id, entity_guid),
    INDEX (score)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS nostr_events
(
    id         varchar(64),
    pubkey     varchar(64),
    created_at timestamp,
    updated_at timestamp,
    kind       int,
    tags       text,
    e_ref      varchar(64),
    p_ref      varchar(64),
    content    text,
    sig        varchar(128),
    PRIMARY KEY (id, pubkey)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS superminds
(
    guid              bigint    NOT NULL PRIMARY KEY,
    activity_guid     bigint,
    sender_guid       bigint,
    receiver_guid     bigint,
    status            int,
    payment_amount    float(7, 2),
    payment_method    int,
    payment_reference text,
    created_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_timestamp timestamp NULL     DEFAULT NULL,
    twitter_required  boolean,
    reply_type        int,
    INDEX (sender_guid, status),
    INDEX (receiver_guid, status)
) ENGINE = InnoDB;

ALTER TABLE superminds
    ADD reply_activity_guid bigint
        AFTER activity_guid;

CREATE TABLE IF NOT EXISTS supermind_refunds
(
    supermind_request_guid bigint      NOT NULL PRIMARY KEY,
    tx_id                  varchar(32) NOT NULL,
    timestamp              timestamp   NOT NULL default CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS user_verification
(
    user_guid bigint NOT NULL,
    device_id varchar(64) NOT NULL,
    device_token varchar(256) NOT NULL,
    verification_code varchar(6) NOT NULL,
    status int NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT NULL,
    sensor_data json NULL DEFAULT NULL,
    ip text NULL DEFAULT NULL,
    geo_lat DECIMAL(10, 8) NULL DEFAULT NULL,
    geo_lon DECIMAL(11, 8) NULL DEFAULT NULL,
    PRIMARY KEY (user_guid, device_id, created_at)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS user_configurations
(
    user_guid bigint PRIMARY KEY,
    terms_accepted_at timestamp NULL DEFAULT NULL,
    supermind_cash_min float(7, 2) NULL DEFAULT NULL,
    supermind_offchain_tokens_min float(7, 2) NULL DEFAULT NULL,
    created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT NULL,
    plus_demonetized timestamp NULL DEFAULT NULL
) ENGINE=InnoDB;

ALTER TABLE user_configurations
    ADD boost_partner_suitability int NULL DEFAULT NULL
    AFTER user_guid;

CREATE TABLE IF NOT EXISTS boosts
(
    guid bigint PRIMARY KEY,
    owner_guid bigint NOT NULL,
    entity_guid bigint NOT NULL,
    target_suitability int NOT NULL,
    target_location int NOT NULL,
    payment_method int NOT NULL,
    payment_amount float NOT NULL,
    payment_tx_id text NULL DEFAULT NULL,
    daily_bid float NOT NULL,
    duration_days int NOT NULL,
    status int NOT NULL,
    admin_guid bigint NULL DEFAULT NULL,
    created_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_timestamp timestamp NULL DEFAULT NULL,
    approved_timestamp timestamp NULL DEFAULT NULL,
    INDEX (owner_guid),
    INDEX (entity_guid),
    INDEX (target_location),
    INDEX (payment_method),
    INDEX (created_timestamp)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS boost_summaries
(
    guid bigint,
    date date NOT NULL,
    views int NOT NULL,
    clicks int,
    PRIMARY KEY (guid, date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS boost_rankings
(
    guid bigint PRIMARY KEY,
    ranking_open float,
    ranking_safe float,
    last_updated timestamp
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS boost_estimates
(
    target_audience int NOT NULL,
    target_location int NOT NULL,
    payment_method int NOT NULL,
    24h_bids int NOT NULL,
    24h_views int NOT NULL,
    PRIMARY KEY (target_audience, target_location, payment_method)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entities_hidden
(
    user_guid bigint NOT NULL,
    entity_guid bigint NOT NULL,
    created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
    PRIMARY KEY (user_guid, entity_guid)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users_marketing_attributes
(
    user_guid bigint NOT NULL,
    attribute_key varchar(128) NOT NULL,
    attribute_value text,
    updated_timestamp timestamp NOT NULL default CURRENT_TIMESTAMP,
    PRIMARY KEY (user_guid, attribute_key)
) ENGINE=InnoDB;

ALTER TABLE boosts
    ADD reason int NULL DEFAULT NULL
    AFTER status;

CREATE TABLE IF NOT EXISTS boost_partner_views
(
    served_by_user_guid bigint NOT NULL,
    boost_guid bigint NOT NULL,
    views int NOT NULL,
    view_date timestamp NOT NULL,
    PRIMARY KEY (served_by_user_guid, boost_guid, view_date)
) ENGINE=InnoDB;

ALTER TABLE boosts
    ADD completed_timestamp timestamp DEFAULT NULL
    AFTER approved_timestamp;

ALTER TABLE boosts
ADD INDEX completed_timestamp (completed_timestamp) USING BTREE;

ALTER TABLE boosts
    ADD target_platform_web boolean DEFAULT true
    AFTER target_suitability;

ALTER TABLE boosts
    ADD target_platform_android boolean DEFAULT true
    AFTER target_platform_web;

ALTER TABLE boosts
    ADD target_platform_ios boolean DEFAULT true
    AFTER target_platform_android;

ALTER TABLE boosts
    ADD goal int NULL DEFAULT NULL
    AFTER target_location;

ALTER TABLE boosts
    ADD goal_button_text int NULL DEFAULT NULL
    AFTER goal;

ALTER TABLE boosts
    ADD goal_button_url text NULL DEFAULT NULL
    AFTER goal_button_text;

ALTER TABLE boost_summaries
    ADD clicks int
    AFTER views;

CREATE TABLE IF NOT EXISTS minds_payments
(
    payment_guid bigint NOT NULL PRIMARY KEY,
    user_guid bigint NOT NULL,
    affiliate_user_guid bigint DEFAULT NULL,
    payment_type int NOT NULL,
    payment_status int NOT NULL,
    payment_method int NOT NULL,
    payment_amount_millis int NOT NULL,
    refunded_amount_millis int NULL,
    is_captured bool DEFAULT FALSE, # Check stripe docs with different states
    payment_tx_id text DEFAULT NULL,
    created_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_timestamp timestamp NULL DEFAULT NULL,
    INDEX user_guid_idx (user_guid),
    INDEX affiliate_user_guid_idx (affiliate_user_guid)
) ENGINE=InnoDB;

ALTER TABLE boosts
    ADD payment_guid bigint DEFAULT NULL
    AFTER payment_method;

CREATE TABLE IF NOT EXISTS minds_default_tag_mapping (
	entity_guid bigint NOT NULL,
	tag_name varchar(100) NOT NULL,
	entity_type varchar(100) NOT NULL,
	PRIMARY KEY (entity_guid, tag_name)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS minds_votes
(
    user_guid bigint NOT NULL,
    entity_guid bigint NOT NULL,
    entity_type text NOT NULL,
    direction int NOT NULL,
    deleted boolean NOT NULL DEFAULT FALSE,
    created_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_guid, entity_guid, direction)
) ENGINE=InnoDB;

ALTER TABLE minds_payments
    ADD affiliate_type int DEFAULT NULL
    AFTER affiliate_user_guid;

CREATE TABLE IF NOT EXISTS minds_comments (
    guid bigint,
    entity_guid bigint,
    owner_guid bigint,
    parent_guid bigint REFERENCES minds_comments(guid),
    parent_depth int,
    body text,
    attachments json,
    mature boolean,
    edited boolean,
    spam boolean,
    deleted boolean,
    `enabled` boolean,
    group_conversation boolean,
    access_id bigint,
    time_created timestamp DEFAULT CURRENT_TIMESTAMP,
    time_updated timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (guid)
) ENGINE = InnoDB;

-- For local environment --
CREATE TABLE IF NOT EXISTS recommendations_latest_cluster_activities (
  cluster_id int,
  activity_guid bigint,
  channel_guid bigint,
  score float,
  PRIMARY KEY (cluster_id, activity_guid)
);

-- For local environment --
CREATE TABLE IF NOT EXISTS recommendations_latest_cluster_tags (
  cluster_id int,
  interest_tag varchar(255),
  relative_ratio float,
  PRIMARY KEY (cluster_id, interest_tag)
);

-- For local environment --
CREATE TABLE IF NOT EXISTS recommendations_latest_user_clusters (
  user_id bigint PRIMARY KEY,
  cluster_id int
);

ALTER TABLE pseudo_seen_entities
    ADD first_seen_timestamp timestamp DEFAULT CURRENT_TIMESTAMP
        AFTER entity_guid;

CREATE TABLE IF NOT EXISTS minds_gift_cards (
    guid bigint PRIMARY KEY,
    product_id tinyint NOT NULL,
    amount decimal(5,2) NOT NULL,
    issued_by_guid bigint NOT NULL,
    issued_at timestamp NOT NULL,
    claim_code text NOT NULL,
    expires_at timestamp NOT NULL,
    claimed_by_guid bigint DEFAULT NULL,
    claimed_at timestamp DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS minds_gift_card_transactions (
    payment_guid bigint NOT NULL ,
    gift_card_guid bigint NOT NULL,
    amount decimal(5,2) NOT NULL,
    created_at timestamp(3) NOT NULL,
    refunded_at timestamp(3) DEFAULT NULL,
    PRIMARY KEY (payment_guid, gift_card_guid),
    FOREIGN KEY (payment_guid) REFERENCES minds_payments(payment_guid),
    FOREIGN KEY (gift_card_guid) REFERENCES minds_gift_cards(guid)
);

CREATE TABLE IF NOT EXISTS minds_group_membership (
    group_guid bigint NOT NULL,
    user_guid bigint NOT NULL,
    created_timestamp timestamp DEFAULT CURRENT_TIMESTAMP(),
    updated_timestamp timestamp DEFAULT CURRENT_TIMESTAMP(),
    membership_level int NOT NULL,
    PRIMARY KEY (group_guid, user_guid),
    INDEX (group_guid, membership_level),
    INDEX (user_guid, membership_level)
);

CREATE TABLE IF NOT EXISTS minds_onboarding_v5_completion (
    user_guid bigint PRIMARY KEY,
    started_at timestamp DEFAULT CURRENT_TIMESTAMP,
    completed_at timestamp DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS minds_onboarding_v5_step_progress (
    user_guid bigint NOT NULL,
    step_key varchar(100) NOT NULL,
    step_type varchar(100) NOT NULL,
    completed_at timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_guid, step_key)
);

ALTER TABLE user_configurations
    ADD plus_demonetized_ts timestamp NULL DEFAULT NULL;

ALTER TABLE user_configurations
    ADD dismissals json NULL DEFAULT NULL
    AFTER plus_demonetized_ts;

CREATE TABLE IF NOT EXISTS minds_partner_earnings (
    user_guid bigint NOT NULL,
    item varchar(256) NOT NULL,
    timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    amount_cents int NULL DEFAULT NULL,
    amount_tokens decimal(8,3) NULL DEFAULT NULL,
    PRIMARY KEY (user_guid, item, timestamp)
);

CREATE TABLE IF NOT EXISTS minds_activitypub_uris (
    uri varchar(256) NOT NULL PRIMARY KEY,
    domain varchar(256) NOT NULL,
    entity_urn varchar(256) NOT NULL,
    entity_guid bigint NOT NULL UNIQUE,
    created_timestamp timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS minds_activitypub_actors (
    uri varchar(256) NOT NULL PRIMARY KEY,
    `type` text NOT NULL,
    inbox text NOT NULL,
    outbox text NOT NULL,
    shared_inbox text DEFAULT NULL,
    url text DEFAULT NULL,
    FOREIGN KEY (uri) REFERENCES minds_activitypub_uris(uri)
);

CREATE TABLE IF NOT EXISTS minds_activitypub_keys (
    user_guid bigint NOT NULL PRIMARY KEY,
    private_key text NOT NULL
);

ALTER TABLE minds_comments
    ADD source text DEFAULT NULL
        AFTER access_id;

ALTER TABLE minds_comments
    ADD canonical_url text DEFAULT NULL
        AFTER source;

ALTER TABLE minds_activitypub_actors
    ADD icon_url text DEFAULT NULL;

ALTER TABLE minds_activitypub_uris
    ADD updated_timestamp timestamp DEFAULT CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS minds_asset_storage (
    `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `entity_guid` bigint NOT NULL,
    `entity_type` int NOT NULL,
    `filename` varchar(256) NOT NULL,
    `owner_guid` bigint NOT NULL,
    `tenant_id` bigint DEFAULT NULL,
    `size_bytes` bigint NOT NULL,
    `duration_seconds` int DEFAULT NULL,
    `created_timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_timestamp` timestamp DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted` boolean NOT NULL DEFAULT FALSE,
    UNIQUE KEY (`tenant_id`, `entity_guid`, `filename`),
    INDEX (`owner_guid`),
    INDEX (`deleted`)
);

CREATE TABLE `minds_tenants` (
  `tenant_id` int NOT NULL AUTO_INCREMENT,
  `owner_guid` bigint DEFAULT NULL,
  `domain` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`tenant_id`),
  UNIQUE KEY `domain` (`domain`)
);

CREATE TABLE IF NOT EXISTS minds_tenant_configs (
    tenant_id int,
    site_name varchar(64),
    site_email varchar(128),
    primary_color varchar(16),
    color_scheme varchar(32),
    updated_timestamp timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (tenant_id)
);

CREATE TABLE `minds_entities` (
  `tenant_id` int NOT NULL,
  `guid` bigint NOT NULL,
  `owner_guid` bigint DEFAULT NULL,
  `type` varchar(32) DEFAULT NULL,
  `subtype` varchar(32) DEFAULT NULL,
  `access_id` bigint DEFAULT NULL,
  `container_guid` bigint DEFAULT NULL,
  PRIMARY KEY (`tenant_id`,`guid`)
);

CREATE TABLE `minds_entities_user` (
  `guid` bigint NOT NULL,
  `tenant_id` int NOT NULL,
  `username` varchar(128) DEFAULT NULL,
  `name` text,
  `briefdescription` text,
  `email` text DEFAULT NULL,
  `password` varchar(256) DEFAULT NULL,
  `liquidity_spot_opt_out` tinyint(1) DEFAULT '0',
  `disabled_boost` tinyint(1) DEFAULT '0',
  `mature` tinyint(1) DEFAULT '0',
  `spam` tinyint(1) DEFAULT '0',
  `deleted` tinyint(1) DEFAULT '0',
  `enabled` tinyint(1) DEFAULT '1',
  `admin` tinyint(1) DEFAULT '0',
  `banned` tinyint(1) DEFAULT '0',
  `canary` tinyint(1) DEFAULT '0',
  `verified` tinyint(1) DEFAULT '0',
  `founder` tinyint(1) DEFAULT '0',
  `last_accepted_tos` timestamp NULL DEFAULT NULL,
  `time_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `time_updated` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `icontime` timestamp NULL DEFAULT NULL,
  `email_confirmation_token` text,
  `email_confirmed_at` timestamp NULL DEFAULT NULL,
  `merchant` json DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `city` varchar(64) DEFAULT NULL,
  `public_dob` varchar(64) DEFAULT NULL,
  `social_profiles` json DEFAULT NULL,
  `eth_wallet` varchar(128) DEFAULT NULL,
  `ip` int unsigned DEFAULT NULL,
  `canonical_url` text,
  `source` text,
  PRIMARY KEY (`tenant_id`,`guid`),
  UNIQUE KEY `username` (`tenant_id`, `username`)
);

CREATE TABLE `minds_entities_group` (
  `tenant_id` int NOT NULL,
  `guid` bigint NOT NULL,
  `name` varchar(128) DEFAULT NULL,
  `brief_description` text,
  `deleted` tinyint(1) DEFAULT '0',
  `banner` tinyint(1) DEFAULT '0',
  `membership` int DEFAULT NULL,
  `moderated` tinyint(1) DEFAULT '0',
  `icon_time` timestamp NULL DEFAULT NULL,
  `time_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `time_updated` timestamp NULL DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `show_boost` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`tenant_id`,`guid`),
  UNIQUE KEY `name` (`name`)
);

CREATE TABLE `minds_entities_activity` (
  `tenant_id` int NOT NULL,
  `guid` bigint NOT NULL,
  `message` text,
  `title` text,
  `remind_object` blob,
  `comments_enabled` tinyint(1) DEFAULT '1',
  `paywall` tinyint(1) DEFAULT '0',
  `edited` tinyint(1) DEFAULT '0',
  `spam` tinyint(1) DEFAULT '0',
  `deleted` tinyint(1) DEFAULT '0',
  `pending` tinyint(1) DEFAULT '0',
  `mature` tinyint(1) DEFAULT '0',
  `time_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `time_updated` timestamp NULL DEFAULT NULL,
  `time_sent` timestamp NULL DEFAULT NULL,
  `license` text,
  `inferred_tags` json DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `canonical_url` text,
  `source` text,
  PRIMARY KEY (`tenant_id`,`guid`)
);

CREATE TABLE `minds_entities_object_image` (
  `tenant_id` int NOT NULL,
  `guid` bigint NOT NULL,
  `message` text,
  `title` text,
  `deleted` tinyint(1) DEFAULT '0',
  `time_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `time_updated` timestamp NULL DEFAULT NULL,
  `auto_caption` text,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  PRIMARY KEY (`tenant_id`,`guid`)
);

CREATE TABLE `minds_entities_object_video` (
  `tenant_id` int NOT NULL,
  `guid` bigint NOT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `time_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `time_updated` timestamp NULL DEFAULT NULL,
  `cloudflare_id` varchar(128) DEFAULT NULL,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `auto_caption` text,
  PRIMARY KEY (`tenant_id`,`guid`)
);

ALTER TABLE `minds_tenants`
    ADD root_user_guid bigint DEFAULT NULL
    AFTER owner_guid;

CREATE TABLE IF NOT EXISTS `minds_tenants_domain_details` (
    `tenant_id` int NOT NULL PRIMARY KEY,
    `domain` varchar(128) NOT NULL,
    `cloudflare_id` varchar(64) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE `minds_votes` ADD COLUMN `tenant_id` int DEFAULT NULL AFTER `user_guid`;
CREATE INDEX `tenant_id` ON `minds_votes` (`tenant_id`);

CREATE TABLE IF NOT EXISTS minds_tenant_featured_entities (
    `tenant_id` int,
    `entity_guid` bigint,
    `auto_subscribe` boolean DEFAULT FALSE,
    `recommended` boolean DEFAULT FALSE,
    `created_timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_timestamp` timestamp DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`tenant_id`, `entity_guid`)
);

ALTER TABLE `minds_entities_user` MODIFY COLUMN ip varchar(40);

ALTER TABLE `friends` ADD COLUMN tenant_id int AFTER friend_guid;

ALTER TABLE `minds_tenant_configs`
    ADD community_guidelines text DEFAULT NULL
    AFTER color_scheme;

ALTER TABLE `minds_tenant_configs`
    ADD nsfw_enabled boolean DEFAULT TRUE
    AFTER community_guidelines;

CREATE TABLE `minds_reports` (
  `tenant_id` int NOT NULL,
  `report_guid` bigint NOT NULL,
  `entity_guid` bigint NOT NULL,
  `entity_urn` varchar(256) NOT NULL,
  `reported_by_guid` bigint NOT NULL,
  `moderated_by_guid` bigint DEFAULT NULL,
  `reason` tinyint NOT NULL,
  `sub_reason` tinyint DEFAULT NULL,
  `status` tinyint NOT NULL,
  `action` tinyint DEFAULT NULL,
  `created_timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_timestamp` timestamp DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_guid`),
  INDEX (tenant_id)
);

ALTER TABLE `minds_tenant_configs`
    ADD last_cache_timestamp timestamp DEFAULT NULL
    AFTER updated_timestamp;

CREATE TABLE IF NOT EXISTS `minds_in_app_purchases` (
    `transaction_id` varchar(128) NOT NULL PRIMARY KEY ,
    `user_guid` bigint NOT NULL,
    `product_id` varchar(128) NOT NULL,
    `purchase_type` tinyint NOT NULL,
    `purchase_timestamp` timestamp NOT NULL,
    INDEX (`user_guid`)
);

CREATE TABLE IF NOT EXISTS `minds_user_rss_feeds` (
    `feed_id` bigint NOT NULL PRIMARY KEY,
    `user_guid` bigint NOT NULL,
    `tenant_id` int DEFAULT NULL,
    `title` text NOT NULL,
    `url` varchar(512) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_fetch_at` timestamp DEFAULT NULL,
    `last_fetch_status` tinyint DEFAULT NULL,
    `last_fetch_entry_timestamp` timestamp DEFAULT NULL,
    KEY (`tenant_id`),
    KEY (`user_guid`),
    UNIQUE KEY (`user_guid`, `url`)
);

ALTER TABLE `minds_entities_user`
	ADD `password_reset_code` text
	AFTER `email_confirmed_at`;

ALTER TABLE `minds_entities_activity`
    ADD `blurb` TEXT DEFAULT NULL
    AFTER `title`;

ALTER TABLE `minds_entities_activity`
    ADD `perma_url` TEXT DEFAULT NULL
    AFTER `blurb`;

ALTER TABLE `minds_entities_activity`
    ADD `thumbnail_src` TEXT DEFAULT NULL
    AFTER `perma_url`;

CREATE TABLE  IF NOT EXISTS  minds_role_permissions(
    `tenant_id` int,
    `permission_id` varchar(64),
    `role_id` tinyint,
    `enabled`       boolean,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`tenant_id`, `permission_id`, `role_id`)
);

CREATE TABLE  IF NOT EXISTS  minds_role_user_assignments(
    `tenant_id` int,
    `role_id` tinyint,
    `user_guid` bigint,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`tenant_id`, `role_id`, `user_guid`)
);

ALTER TABLE `minds_entities_user`
    ADD `language` varchar(32) DEFAULT 'en'
    AFTER `ip`;


ALTER TABLE minds_activitypub_uris ADD COLUMN tenant_id int DEFAULT -1 FIRST;
ALTER TABLE minds_activitypub_actors ADD COLUMN tenant_id int DEFAULT -1 FIRST;
ALTER TABLE minds_activitypub_keys ADD COLUMN tenant_id int DEFAULT -1 FIRST;

ALTER TABLE minds_activitypub_actors DROP FOREIGN KEY minds_activitypub_actors_ibfk_1;

ALTER TABLE minds_activitypub_uris DROP PRIMARY KEY, ADD PRIMARY KEY(tenant_id, uri);
ALTER TABLE minds_activitypub_actors DROP PRIMARY KEY, ADD PRIMARY KEY(tenant_id, uri);
ALTER TABLE minds_activitypub_keys DROP PRIMARY KEY, ADD PRIMARY KEY(tenant_id, user_guid);
ALTER TABLE minds_activitypub_actors ADD CONSTRAINT minds_activitypub_actors_ibfk_1 FOREIGN KEY (tenant_id, uri) REFERENCES minds_activitypub_uris(tenant_id, uri);

ALTER TABLE `minds_entities_activity`
    ADD `nsfw` json DEFAULT NULL
    AFTER `attachments`;

ALTER TABLE `minds_entities_activity`
    ADD `nsfw_lock` json DEFAULT NULL
    AFTER `nsfw`;

CREATE TABLE IF NOT EXISTS  minds_embedded_comments_activity_map (
    tenant_id int DEFAULT -1,
    user_guid bigint NOT NULL,
    url varchar(256) NOT NULL,
    activity_guid bigint NOT NULL,
    PRIMARY KEY (tenant_id, user_guid, url)
);

CREATE TABLE IF NOT EXISTS  minds_embedded_comments_settings (
    tenant_id int NOT NULL,
    user_guid bigint NOT NULL,
    auto_imports_enabled boolean DEFAULT TRUE,
    domain varchar(128) DEFAULT NULL,
    path_regex varchar(256) DEFAULT NULL,
    PRIMARY KEY (tenant_id, user_guid)
);

CREATE TABLE IF NOT EXISTS  `minds_oidc_providers` (
  `tenant_id` int NOT NULL,
  `provider_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `issuer` varchar(128) DEFAULT NULL,
  `client_id` varchar(128) DEFAULT NULL,
  `client_secret` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`tenant_id`, `provider_id`),
  INDEX (provider_id)
) ENGINE=InnoDB AUTO_INCREMENT=1;

ALTER TABLE `minds_entities_group` MODIFY COLUMN banner timestamp;

CREATE TABLE IF NOT EXISTS `minds`.`minds_tenant_invites`
(
    id int PRIMARY KEY AUTO_INCREMENT,
    tenant_id int NOT NULL,
    owner_guid bigint NOT NULL,
    email varchar(512) NOT NULL,
    invite_token text NOT NULL,
    target_roles text DEFAULT NULL,
    target_group_guids text DEFAULT NULL,
    custom_message text,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    send_timestamp TIMESTAMP DEFAULT NULL,
    status tinyint(6) NOT NULL,
    INDEX (tenant_id, owner_guid),
    INDEX (status),
    UNIQUE INDEX (tenant_id, email)
) ENGINE = "InnoDB";

CREATE TABLE IF NOT EXISTS minds_post_notification_subscriptions (
    tenant_id INT NOT NULL,
    user_guid BIGINT NOT NULL,
    entity_guid BIGINT NOT NULL,
    frequency enum ('ALWAYS', 'HIGHLIGHTS', 'NEVER') DEFAULT 'ALWAYS',
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (tenant_id, user_guid, entity_guid),
    INDEX (tenant_id, entity_guid)
);

ALTER TABLE `minds_tenant_configs`
    ADD federation_disabled boolean DEFAULT false
    AFTER color_scheme;

ALTER TABLE minds_tenant_featured_entities ADD COLUMN auto_post_subscription boolean DEFAULT FALSE AFTER recommended;

CREATE TABLE IF NOT EXISTS  minds_custom_pages(
    `tenant_id` int NOT NULL,
    `page_type` varchar(64) NOT NULL,
    `content` text DEFAULT NULL,
    `external_link` text DEFAULT NULL,
    created_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`tenant_id`, `page_type`)
);

CREATE TABLE IF NOT EXISTS minds_push_notification_config (
    tenant_id int PRIMARY KEY,
    apns_team_id varchar(10),
    apns_key varchar(512),
    apns_key_id varchar(10),
    apns_topic varchar(128)
);

CREATE TABLE IF NOT EXISTS `minds`.`minds_tenant_mobile_configs` (
    `tenant_id` int NOT NULL PRIMARY KEY,
    `splash_screen_type` tinyint DEFAULT NULL,
    `welcome_screen_logo_type` tinyint DEFAULT NULL,
    `preview_status` tinyint NOT NULL DEFAULT 0,
    `preview_last_updated_timestamp` timestamp DEFAULT NULL,
    `update_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE minds_entities_object_image ADD COLUMN filename text AFTER deleted;

ALTER TABLE `minds_tenants` ADD plan enum ('TEAM', 'COMMUNITY', 'ENTERPRISE') DEFAULT 'TEAM' AFTER root_user_guid;

CREATE TABLE IF NOT EXISTS minds_site_membership_tiers (
    tenant_id int,
    membership_tier_guid bigint,
    stripe_product_id varchar(256),
    name varchar(256) NOT NULL,
    description text DEFAULT NULL,
    billing_period enum ('month', 'year') NOT NULL,
    pricing_model enum ('recurring', 'one_time') NOT NULL,
    currency varchar(3) NOT NULL,
    price_in_cents int NOT NULL,
    archived boolean DEFAULT FALSE,
    PRIMARY KEY (tenant_id, membership_tier_guid)
);

CREATE TABLE IF NOT EXISTS minds_site_membership_tiers_role_assignments (
    tenant_id int NOT NULL,
    membership_tier_guid bigint NOT NULL,
    role_id int NOT NULL,
    PRIMARY KEY (tenant_id, membership_tier_guid, role_id)
);

CREATE TABLE IF NOT EXISTS minds_site_membership_tiers_group_assignments (
    tenant_id int NOT NULL,
    membership_tier_guid bigint NOT NULL,
    group_guid bigint NOT NULL,
    PRIMARY KEY (tenant_id, membership_tier_guid, group_guid)
);

CREATE TABLE IF NOT EXISTS minds_site_membership_subscriptions (
    id int NOT NULL primary key AUTO_INCREMENT,
    tenant_id int NOT NULL,
    user_guid bigint NOT NULL,
    membership_tier_guid bigint NOT NULL,
    stripe_subscription_id varchar(256) NOT NULL,
    valid_from timestamp NOT NULL,
    valid_to timestamp DEFAULT NULL,
    auto_renew boolean NOT NULL,
    UNIQUE INDEX (tenant_id, user_guid, membership_tier_guid),
    UNIQUE INDEX (stripe_subscription_id),
    INDEX (tenant_id),
    INDEX (valid_from),
    INDEX (valid_to)
);

ALTER TABLE `minds_tenants` ADD plan enum ('TEAM', 'COMMUNITY', 'ENTERPRISE') DEFAULT 'TEAM' AFTER root_user_guid;

ALTER TABLE `minds_tenant_configs`
    ADD reply_email varchar(128) DEFAULT NULL
    AFTER federation_disabled;

CREATE TABLE IF NOT EXISTS minds_stripe_keys(
    tenant_id int PRIMARY KEY,
    pub_key varchar(128),
    sec_key_cipher_text varchar(256),
    created_timestamp timestamp DEFAULT CURRENT_TIMESTAMP(),
    updated_timestamp timestamp NULL
);


CREATE TABLE IF NOT EXISTS minds_site_membership_entities (
    tenant_id int,
    entity_guid bigint,
    membership_guid  bigint,
    created_timestamp timestamp DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (tenant_id, entity_guid, membership_guid)
);

ALTER TABLE minds_entities_activity
    ADD COLUMN site_membership boolean DEFAULT FALSE
    AFTER attachments;
ALTER TABLE minds_entities_activity
    ADD COLUMN paywall_thumbnail boolean DEFAULT FALSE
    AFTER site_membership;
ALTER TABLE minds_entities_activity
    ADD COLUMN link_title text DEFAULT null
    AFTER paywall_thumbnail;

CREATE TABLE IF NOT EXISTS minds_payments_config(
    tenant_id INT NOT NULL PRIMARY KEY,
    stripe_customer_portal_config_id varchar(256) DEFAULT NULL
);

ALTER TABLE minds_entities_object_image ADD COLUMN blurhash text AFTER filename;

ALTER TABLE minds_entities_activity
    MODIFY COLUMN paywall_thumbnail JSON DEFAULT NULL;