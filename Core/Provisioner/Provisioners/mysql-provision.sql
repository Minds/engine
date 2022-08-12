CREATE TABLE IF NOT EXISTS friends(
  user_guid bigint,
  friend_guid bigint,
  timestamp timestamp,
  PRIMARY KEY(user_guid, friend_guid)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pseudo_seen_entities(
  pseudo_id varchar(128),
  entity_guid bigint,
  last_seen_timestamp timestamp,
  PRIMARY KEY(pseudo_id, entity_guid)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recommendations_clustered_recs(
  cluster_id int,
  entity_guid bigint,
  entity_owner_guid bigint,
  score float(5,2),
  first_engaged timestamp NULL DEFAULT NULL,
  last_engaged timestamp NULL DEFAULT NULL,
  last_updated timestamp NULL DEFAULT NULL,
  time_created timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  total_views bigint,
  total_engagement bigint,
  PRIMARY KEY(cluster_id, entity_guid),
  INDEX (score)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS nostr_events (
    id varchar(64),
    pubkey varchar(64),
    created_at timestamp,
    updated_at timestamp,
    kind int,
    tags text,
    e_ref varchar(64),
    p_ref varchar(64),
    content text,
    sig varchar(128),
    PRIMARY KEY (id, pubkey)
) ENGINE=InnoDB;
