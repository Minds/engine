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