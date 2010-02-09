# mysql migration script
# base version: 1.0
# update version: 1.1

PLEASE NOTE: Since there exists no migration-tool, please use this script MANUEL to convert your old database to
a newer version.
Dont't paste this script directly into your SQL-client, because you have to excute the convert scripts and/or delete-queries 
in the specified order! 

# For detailed informations, please take a look at the update protocol from our installation in goettingen!
# (Should be located in the same folder)
#
#
# #1
# create new table for semesters
#

CREATE TABLE `semester_data` (
`semester_id` varchar(32) NOT NULL default '',
`name` varchar(255) NOT NULL default '',
`description` text NOT NULL,
`semester_token` varchar(10) NOT NULL default '',
`beginn` int(20) unsigned default NULL,
`ende` int(20) unsigned default NULL,
`vorles_beginn` int(20) unsigned default NULL,
`vorles_ende` int(20) unsigned default NULL,
PRIMARY KEY (`semester_id`)
) TYPE=MyISAM;

# #2
# create new table for holidays
#

CREATE TABLE semester_holiday (
holiday_id varchar(32) NOT NULL default '',
semester_id varchar(32) NOT NULL default '',
name varchar(255) NOT NULL default '',
description text NOT NULL,
beginn int(20) unsigned default NULL,
ende int(20) unsigned NOT NULL default '0',
PRIMARY KEY (holiday_id)
) TYPE=MyISAM;

# #3
# create new configuration table
#

CREATE TABLE `config` (
`config_id` varchar( 32 ) NOT NULL default '',
`key` varchar( 255 ) NOT NULL default '',
`value` varchar( 255 ) NOT NULL default '',
`default_value` varchar( 255 ) NOT NULL default '',
`chdate` int( 20 ) NOT NULL default '0',
`comment` text NOT NULL ,
PRIMARY KEY ( `config_id` )
) TYPE = MYISAM

# #4
# >>>please use the script convert_semester.php at this point!
#

# #5
# changes to the resources-management
#

ALTER TABLE `resources_assign` DROP `repeat_month` , DROP `repeat_week` ;
ALTER TABLE `resources_categories` ADD `is_room` TINYINT( 4 ) NOT NULL AFTER `system` ;
ALTER TABLE `resources_categories_properties` ADD `requestable` TINYINT( 4 ) NOT NULL AFTER `property_id` ;

CREATE TABLE `resources_locks` (
`lock_id` varchar(32) NOT NULL default '',
`lock_begin` int(20) unsigned default NULL,
`lock_end` int(20) unsigned default NULL,
PRIMARY KEY (`lock_id`)
) TYPE=MyISAM;

ALTER TABLE `resources_objects` ADD `institut_id` VARCHAR( 32 ) NOT NULL AFTER `owner_id` ;
ALTER TABLE `resources_objects` ADD INDEX ( `institut_id` ) ;
ALTER TABLE `resources_objects` ADD `lockable` TINYINT( 4 ) DEFAULT NULL AFTER `description` ,
ADD `multiple_assign` TINYINT( 4 ) DEFAULT NULL AFTER `lockable` ;
ALTER TABLE `resources_objects` DROP `inventar_num` ,DROP `parent_bind` ;

CREATE TABLE `resources_requests` (
`request_id` varchar(32) NOT NULL default '',
`seminar_id` varchar(32) NOT NULL default '',
`termin_id` varchar(32) NOT NULL default '',
`user_id` varchar(32) NOT NULL default '',
`resource_id` varchar(32) NOT NULL default '',
`category_id` varchar(32) NOT NULL default '',
`comment` text,
`closed` tinyint(3) unsigned default NULL,
`mkdate` int(20) unsigned default NULL,
`chdate` int(20) unsigned default NULL,
PRIMARY KEY (`request_id`),
KEY `seminar_id` (`seminar_id`,`user_id`,`resource_id`),
KEY `termin_id` (`termin_id`)
) TYPE=MyISAM;

CREATE TABLE `resources_requests_properties` (
`request_id` varchar(32) NOT NULL default '',
`property_id` varchar(32) NOT NULL default '',
`state` text,
`mkdate` int(20) unsigned default NULL,
`chdate` int(20) unsigned default NULL,
PRIMARY KEY (`request_id`,`property_id`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS `resources_temporary_events` (
`event_id` varchar(32) NOT NULL default '',
`resource_id` varchar(32) NOT NULL default '',
`assign_id` varchar(32) NOT NULL default '',
`seminar_id` varchar(32) NOT NULL default '',
`termin_id` varchar(32) NOT NULL default '',
`begin` int(20) NOT NULL default '0',
`end` int(20) NOT NULL default '0',
`mkdate` int(20) NOT NULL default '0',
PRIMARY KEY (`event_id`)
) TYPE=HEAP;

ALTER TABLE `resources_assign` CHANGE `assign_user_id` `assign_user_id` VARCHAR( 32 ) DEFAULT NULL;

ALTER TABLE `resources_assign` ADD INDEX ( `resource_id` );
ALTER TABLE `resources_assign` ADD INDEX ( `assign_user_id` ) ;
OPTIMIZE TABLE `resources_assign` ;

ALTER TABLE `resources_categories` ADD INDEX ( `is_room` ) ;
OPTIMIZE TABLE `resources_categories` ;

ALTER TABLE `resources_objects` DROP INDEX `categorie_id` ;
ALTER TABLE `resources_objects` DROP INDEX `root_id` ;
ALTER TABLE `resources_objects` ADD INDEX ( `root_id` ) ;
ALTER TABLE `resources_objects` ADD INDEX ( `parent_id` ) ;
ALTER TABLE `resources_objects` ADD INDEX ( `category_id` ) ;
ALTER TABLE `resources_objects` ADD INDEX ( `owner_id` ) ;
OPTIMIZE TABLE `resources_objects` ;

ALTER TABLE `resources_requests` DROP INDEX `seminar_id` ;
ALTER TABLE `resources_requests` ADD INDEX ( `seminar_id` ) ;
ALTER TABLE `resources_requests` ADD INDEX ( `user_id` ) ;
ALTER TABLE `resources_requests` ADD INDEX ( `resource_id` ) ;
ALTER TABLE `resources_requests` ADD INDEX ( `category_id` ) ;
ALTER TABLE `resources_requests` ADD INDEX ( `closed` ) ;
OPTIMIZE TABLE `resources_requests` ;

UPDATE `resources_categories` SET `is_room`='1' WHERE `name` = 'Raum';

# #6
# create default entries for resources-management
#

INSERT INTO `config` VALUES ('3d415eca600096df09e59407e4a7994d', 'RESOURCES_LOCKING_ACTIVE', '', '', 1074780851, '');
INSERT INTO `config` VALUES ('b7a2817d142ddd185df2f5ac587fe218', 'RESOURCES_ALLOW_ROOM_REQUESTS', '', '', 1074780851, '');
INSERT INTO `config` VALUES ('d821ffbff29ce636cef63ffe3fd8b427', 'RESOURCES_ALLOW_CREATE_ROOMS', '1', '', 1074780851, '');
INSERT INTO `config` VALUES ('e48dacf9158cd0b936144f0f4cf8dfa3', 'RESOURCES_INHERITANCE_PERMS_ROOMS', '1', '1', 1074780851, '');
INSERT INTO `config` VALUES ('45856b1e3407ceb37d87ec9b8fd32d7d', 'RESOURCES_INHERITANCE_PERMS', '1', '1', 1074780851, '');
INSERT INTO `config` VALUES ('c353c73d8f37e3c301ae34e99c837af4', 'RESOURCES_ENABLE_ORGA_CLASSIFY', '', '', 1074780851, '');
INSERT INTO `config` VALUES ('dde143b2b8ed77a384f8c48c956982b8', 'RESOURCES_ENABLE_ORGA_ADMIN_NOTICE', '', '', 1074780851, '');
INSERT INTO `config` VALUES ('4ff6d5e7ef7ee66acefa5fcf8e7f2305', 'RESOURCES_ALLOW_SINGLE_ASSIGN_PERCENTAGE', '50', '', 1074780851, '');
INSERT INTO `config` VALUES ('5b4b20d9d2c1556ff1b42503d38a8bc6', 'RESOURCES_ALLOW_SINGLE_DATE_GROUPING', '5', '', 1074780851, '');

# #7
# create new tables for evaluation-module
#

CREATE TABLE `eval` (
  `eval_id` varchar(32) NOT NULL default '',
  `author_id` varchar(32) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `text` text NOT NULL,
  `startdate` int(20) default NULL,
  `stopdate` int(20) default NULL,
  `timespan` int(20) default NULL,
  `mkdate` int(20) NOT NULL default '0',
  `chdate` int(20) NOT NULL default '0',
  `anonymous` tinyint(1) NOT NULL default '1',
  `visible` tinyint(1) NOT NULL default '1',
  `shared` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`eval_id`)
) TYPE=MyISAM PACK_KEYS=1;

CREATE TABLE `eval_range` (
  `eval_id` varchar(32) NOT NULL default '',
  `range_id` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`eval_id`,`range_id`)
) TYPE=MyISAM PACK_KEYS=1;

CREATE TABLE `eval_user` (
  `eval_id` varchar(32) NOT NULL default '',
  `user_id` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`eval_id`,`user_id`)
) TYPE=MyISAM;

CREATE TABLE `evalanswer` (
  `evalanswer_id` varchar(32) NOT NULL default '',
  `parent_id` varchar(32) NOT NULL default '',
  `position` int(11) NOT NULL default '0',
  `text` text NOT NULL,
  `value` int(11) NOT NULL default '0',
  `rows` tinyint(4) NOT NULL default '0',
  `counter` int(11) NOT NULL default '0',
  `residual` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`evalanswer_id`),
  KEY `parent_id` (`parent_id`)
) TYPE=MyISAM;

CREATE TABLE `evalanswer_user` (
  `evalanswer_id` varchar(32) NOT NULL default '',
  `user_id` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`evalanswer_id`,`user_id`)
) TYPE=MyISAM PACK_KEYS=1;

CREATE TABLE `evalgroup` (
  `evalgroup_id` varchar(32) NOT NULL default '',
  `parent_id` varchar(32) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `text` text NOT NULL,
  `position` int(11) NOT NULL default '0',
  `child_type` enum('EvaluationGroup','EvaluationQuestion') NOT NULL default 'EvaluationGroup',
  `mandatory` tinyint(1) NOT NULL default '0',
  `template_id` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`evalgroup_id`),
  KEY `parent_id` (`parent_id`)
) TYPE=MyISAM PACK_KEYS=1;

CREATE TABLE `evalquestion` (
  `evalquestion_id` varchar(32) NOT NULL default '',
  `parent_id` varchar(32) NOT NULL default '',
  `type` enum('likertskala','multiplechoice','polskala') NOT NULL default 'multiplechoice',
  `position` int(11) NOT NULL default '0',
  `text` text NOT NULL,
  `multiplechoice` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`evalquestion_id`),
  KEY `parent_id` (`parent_id`)
) TYPE=MyISAM;


# #8
# create default entries for evaluation-module 
#

INSERT INTO `evalquestion` VALUES ('ef227e91618878835d52cfad3e6d816b', '0', 'polskala', 0, 'Wertung 1-5', 0);
INSERT INTO `evalanswer` VALUES ('d67301d4f59aa35d1e3f12a9791b6885', 'ef227e91618878835d52cfad3e6d816b', 0, 'Sehr gut', 1, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('7052b76e616656e4b70f1c504c04ec81', 'ef227e91618878835d52cfad3e6d816b', 1, '', 2, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('64152ace8f2a74d0efb67c54eff64a2b', 'ef227e91618878835d52cfad3e6d816b', 2, '', 3, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('3a3ab5307f39ea039d41fb6f2683475e', 'ef227e91618878835d52cfad3e6d816b', 3, '', 4, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('6115b19f694ccd3d010a0047ff8f970a', 'ef227e91618878835d52cfad3e6d816b', 4, 'Sehr Schlecht', 5, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('be4c3e5fe0b2b735bb3b2712afa8c490', 'ef227e91618878835d52cfad3e6d816b', 5, 'Keine Meinung', 6, 0, 0, 1);
INSERT INTO `evalquestion` VALUES ('724244416b5d04a4d8f4eab8a86fdbf8', '0', 'likertskala', 0, 'Schulnoten', 0);
INSERT INTO `evalanswer` VALUES ('84be4c31449a9c1807bf2dea0dc869f1', '724244416b5d04a4d8f4eab8a86fdbf8', 0, 'Sehr gut', 1, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('c446970d2addd68e43c2a6cae6117bf7', '724244416b5d04a4d8f4eab8a86fdbf8', 1, 'Gut', 2, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('3d4dcedb714dfdcfbe65cd794b4d404b', '724244416b5d04a4d8f4eab8a86fdbf8', 2, 'Befriedigend', 3, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('fa2bf667ba73ae74794df35171c2ad2e', '724244416b5d04a4d8f4eab8a86fdbf8', 3, 'Ausreichend', 4, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('0be387b9379a05c5578afce64b0c688f', '724244416b5d04a4d8f4eab8a86fdbf8', 4, 'Mangelhaft', 5, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('aec07dd525f2610bdd10bf778aa1893b', '724244416b5d04a4d8f4eab8a86fdbf8', 5, 'Nicht erteilt', 6, 0, 0, 1);
INSERT INTO `evalquestion` VALUES ('95bbae27965d3404f7fa3af058850bd3', '0', 'likertskala', 0, 'Wertung (trifft zu, ...)', 0);
INSERT INTO `evalanswer` VALUES ('7080335582e2787a54f315ec8cef631e', '95bbae27965d3404f7fa3af058850bd3', 0, 'trifft völlig zu', 1, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('d68a74dc2c1f0ce226366da918dd161d', '95bbae27965d3404f7fa3af058850bd3', 1, 'trifft ziemlich zu', 2, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('641686e7c61899b303cda106f20064e7', '95bbae27965d3404f7fa3af058850bd3', 2, 'teilsteils', 3, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('7c36d074f2cc38765c982c9dfb769afc', '95bbae27965d3404f7fa3af058850bd3', 3, 'trifft wenig zu', 4, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('5c4827f903168ed4483db5386a9ad5b8', '95bbae27965d3404f7fa3af058850bd3', 4, 'trifft gar nicht zu', 5, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('c10a3f4e97f8badc5230a9900afde0c7', '95bbae27965d3404f7fa3af058850bd3', 5, 'kann ich nicht beurteilen', 6, 0, 0, 1);
INSERT INTO `evalquestion` VALUES ('6fddac14c1f2ac490b93681b3da5fc66', '0', 'multiplechoice', 0, 'Werktage', 0);
INSERT INTO `evalanswer` VALUES ('ced33706ca95aff2163c7d0381ef5717', '6fddac14c1f2ac490b93681b3da5fc66', 0, 'Montag', 1, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('087c734855c8a5b34d99c16ad09cd312', '6fddac14c1f2ac490b93681b3da5fc66', 1, 'Dienstag', 2, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('63f5011614f45329cc396b90d94a7096', '6fddac14c1f2ac490b93681b3da5fc66', 2, 'Mittwoch', 3, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('ccd1eaddccca993f6789659b36f40506', '6fddac14c1f2ac490b93681b3da5fc66', 3, 'Donnerstag', 4, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('48842cedeac739468741940982b5fe6d', '6fddac14c1f2ac490b93681b3da5fc66', 4, 'Freitag', 5, 0, 0, 0);
INSERT INTO `evalquestion` VALUES ('12e508079c4770fb13c9fce028f40cac', '0', 'multiplechoice', 0, 'Werktage-mehrfach', 1);
INSERT INTO `evalanswer` VALUES ('21b3f7cf2de5cbb098d800f344d399ee', '12e508079c4770fb13c9fce028f40cac', 0, 'Montag', 1, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('f0016e918b5bc5c4cf3cc62bf06fa2e9', '12e508079c4770fb13c9fce028f40cac', 1, 'Dienstag', 2, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('c88242b50ff0bb43df32c1e15bdaca22', '12e508079c4770fb13c9fce028f40cac', 2, 'Mittwoch', 3, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('b39860f6601899dcf87ba71944c57bc7', '12e508079c4770fb13c9fce028f40cac', 3, 'Donnerstag', 4, 0, 0, 0);
INSERT INTO `evalanswer` VALUES ('568d6fd620642cb7395c27d145a76734', '12e508079c4770fb13c9fce028f40cac', 4, 'Freitag', 5, 0, 0, 0);
INSERT INTO `evalquestion` VALUES ('a68bd711902f23bd5c55a29f1ecaa095', '0', 'multiplechoice', 0, 'Freitext-Mehrzeilig', 0);
INSERT INTO `evalanswer` VALUES ('39b98a5560d5dabaf67227e2895db8da', 'a68bd711902f23bd5c55a29f1ecaa095', 0, '', 1, 5, 0, 0);
INSERT INTO `evalquestion` VALUES ('442e1e464e12498bd238a7767215a5a2', '0', 'multiplechoice', 0, 'Freitext-Einzeilig', 0);
INSERT INTO `evalanswer` VALUES ('61ae27ab33c402316a3f1eb74e1c46ab', '442e1e464e12498bd238a7767215a5a2', 0, '', 1, 1, 0, 0);

# #9
# changes for folder-system
#

ALTER TABLE `dokumente` ADD `url` VARCHAR( 255 ) NOT NULL ,
ADD `protected` TINYINT( 4 ) NOT NULL ;

# #10
# changes for literatur-module
#

ALTER TABLE `Institute` ADD `lit_plugin_name` VARCHAR( 255 ) ;
DROP TABLE IF EXISTS `literatur` ;

# #11
# changes for email-forwarding
#

ALTER TABLE `user_info` ADD `email_forward` TINYINT DEFAULT '0' NOT NULL ; 
