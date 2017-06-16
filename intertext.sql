#DROP DATABASE intertext;
SET default_storage_engine=InnoDB;
CREATE DATABASE intertext DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

GRANT ALL PRIVILEGES ON intertext.* TO 'intertext'@'localhost' IDENTIFIED BY 'intertext';

USE intertext;

CREATE TABLE texts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name TINYTEXT,

  PRIMARY KEY (id),
	INDEX index_name (name(20))
);

CREATE TABLE `versions` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	text_id BIGINT UNSIGNED,
  version_name TINYTEXT,
  root_id BIGINT UNSIGNED,
	text_changed BOOL DEFAULT FALSE,
	uniq_ids BOOL DEFAULT FALSE,
	text_elements TEXT NOT NULL,
	filename TEXT,

  PRIMARY KEY (id),
	INDEX index_text_id (text_id),
	INDEX index_name (version_name(20))
);

CREATE TABLE `alignments` (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	ver1_id BIGINT UNSIGNED,
	ver2_id BIGINT UNSIGNED,
	method TINYTEXT NOT NULL,
	profile TINYTEXT NOT NULL,
	resp INT UNSIGNED,
	editor INT UNSIGNED,
	c_chstruct BOOLEAN DEFAULT 0,
	chtext BOOLEAN DEFAULT 0,
	status INT UNSIGNED DEFAULT 0,
	remote_user BIGINT UNSIGNED,

	PRIMARY KEY (id),
	INDEX index_ver1 (ver1_id),
	INDEX index_ver2 (ver2_id),
	INDEX index_resp (resp),
	INDEX index_editor (editor),
	INDEX index_status (status),
	INDEX index_remote_user (remote_user)
);
