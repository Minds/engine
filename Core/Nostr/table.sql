CREATE TABLE IF NOT EXISTS nostr_events (
    id varchar(64),
    pubkey varchar(64),
    created_at timestamp,
    kind int,
    tags text,
    content text,
    sig varchar(128),
    deleted boolean DEFAULT 0,
    PRIMARY KEY (id, pubkey)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS nostr_replies (
    id varchar(64),
    event_id varchar(64),
    relay_url text,
    marker text,
    FOREIGN KEY (id) REFERENCES nostr_events(id),
    PRIMARY KEY (id, event_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS nostr_mentions (
    id varchar(64),
    pubkey varchar(64),
    FOREIGN KEY (id) REFERENCES nostr_events(id),
    PRIMARY KEY (id, pubkey)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS nostr_users (
    pubkey varchar(64),
    user_guid bigint,
    is_external boolean,
    PRIMARY KEY (pubkey)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS nostr_kind_1_to_activity_guid (
    id varchar(64),
    activity_guid bigint,
    owner_guid bigint,
    is_external boolean,
    PRIMARY KEY (id, activity_guid)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS nostr_pubkey_whitelist (
    pubkey varchar(64),
    PRIMARY KEY (pubkey)
) ENGINE=InnoDB;