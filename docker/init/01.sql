USE intertext;
SET CHARACTER SET utf8;

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

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` tinytext,
  `password` tinytext,
  `status` int(10) unsigned DEFAULT NULL,
  `name` tinytext,
  `surname` tinytext,
  PRIMARY KEY (`id`)
);

INSERT INTO `users` VALUES 
(1,'admin','098f6bcd4621d373cade4e832627b4f6',0,'Admin','Allmighty'),
(2,'resp','098f6bcd4621d373cade4e832627b4f6',1,'Responsible','Supervisor'),
(3,'editor','098f6bcd4621d373cade4e832627b4f6',2,'Any','Editor');
