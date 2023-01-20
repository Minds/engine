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
    updated_at timestamp NULL DEFAULT NULL
) ENGINE=InnoDB;

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
    guid bigint PRIMARY KEY,
    day int NOT NULL,
    views int NOT NULL,
    reach int NOT NULL
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

ALTER TABLE boosts
    ADD reason int NULL DEFAULT NULL
    AFTER status;
