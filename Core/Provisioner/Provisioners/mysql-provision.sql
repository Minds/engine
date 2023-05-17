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
