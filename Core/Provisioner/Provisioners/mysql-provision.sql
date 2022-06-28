CREATE DATABASE IF NOT EXISTS minds;

CREATE TABLE IF NOT EXISTS minds.friends(
  user_guid bigint,
  friend_guid bigint,
  timestamp timestamp,
  PRIMARY KEY(user_guid, friend_guid)
) ENGINE=InnoDB;
