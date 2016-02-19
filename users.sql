USE intertext;
SET CHARACTER SET utf8;
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
