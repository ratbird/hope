# mysql migration script
# base version: studip-1.1.5
# update version: studip-1.2

PLEASE NOTE: Since there exists no migration-tool, please use this script MANUEL to convert your old database to
a newer version.
Don`t paste this script directly into your SQL-client, because you may have to excute some convert scripts and/or 
delete-queries at specified points! 

# For detailed informations, please take a look at the update protocol from our installation in Goettingen!
# (Should be located in the same folder)

# #1
# StEP00026: changes for new DB-based config/user-config system
#

ALTER TABLE `config` CHANGE `key` `field` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `config` ADD `position` INT NOT NULL AFTER `default_value` ;
ALTER TABLE `config` ADD `type` ENUM( 'boolean', 'integer', 'string' ) NOT NULL AFTER `value` ;
ALTER TABLE `config` ADD `parent_id` VARCHAR( 32 ) NOT NULL AFTER `config_id` ;
ALTER TABLE `config` ADD INDEX ( `parent_id` ) ;
ALTER TABLE `config` ADD `mkdate` INT( 20 ) NOT NULL AFTER `position` ;
ALTER TABLE `config` CHANGE `comment` `description` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `config` ADD `comment` TEXT NOT NULL ;
ALTER TABLE `config` ADD `message_template` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `config` ADD `range` ENUM( 'global', 'user' ) NOT NULL AFTER `type` ;
ALTER TABLE `config` ADD `section` VARCHAR( 255 ) NOT NULL AFTER `default_value` ;
ALTER TABLE `config` ADD `is_default` TINYINT NOT NULL AFTER `value` ;
ALTER TABLE `config` DROP `default_value`;
ALTER TABLE `config` ADD INDEX ( `field` , `range` ) ;


CREATE TABLE `user_config` (
`userconfig_id` varchar(32) NOT NULL default '',
`parent_id` varchar(32) default NULL,
`user_id` varchar(32) NOT NULL default '',
`field` varchar(255) NOT NULL default '',
`value` text NOT NULL,
`mkdate` int(11) NOT NULL default '0',
`chdate` int(11) NOT NULL default '0',
`comment` text NOT NULL,
PRIMARY KEY (`userconfig_id`),
KEY `user_id` (`user_id`,`field`)
);

# #2
# StEP00023: new tables for session-management using PHP4-Sessions (better performance, but new ;) )
#

CREATE TABLE `session_data` (
  `sid` varchar(32) NOT NULL default '',
  `val` mediumtext NOT NULL,
  `changed` timestamp(14) NOT NULL,
  PRIMARY KEY  (`sid`),
  KEY `changed` (`changed`)
) TYPE=MyISAM;

CREATE TABLE `user_data` (
  `sid` varchar(32) NOT NULL default '',
  `val` mediumtext NOT NULL,
  `changed` timestamp(14) NOT NULL,
  PRIMARY KEY  (`sid`),
  KEY `changed` (`changed`)
) TYPE=MyISAM;

# #3
# >>>StEP00023: you MAY use the script convert_active_sessions.php at this point
# PLEASE NOTE I: We introduce the session-management in this Stud.IP-Version. The old session-
# management ist still supported, so you can decide to use the old-system OR the new-system
# Please take a look to further documentation in this directory switching the mode of session-
# management.
# PLEASE NOTE II: Use this convert script NOT until you switched to prepend4.php as default
# prepend-file!
#

# #4
# StEP00017: changes for news rss-feeds
#

ALTER TABLE `news` ADD `chdate_uid` VARCHAR( 32 ) NOT NULL ,
ADD `chdate` INT UNSIGNED NOT NULL ,
ADD `mkdate` INT UNSIGNED NOT NULL ;
ALTER TABLE `news` ADD INDEX ( `chdate` ) ;
UPDATE news SET chdate=date WHERE 1;

ALTER TABLE `user_info` ADD `news_author_id` VARCHAR( 32 ) NOT NULL ;
ALTER TABLE `user_info` ADD INDEX ( `news_author_id` ) ; 

# #5
# StEP00030: changes for news-comments
#

ALTER TABLE `news` ADD `allow_comments` TINYINT( 1 ) NOT NULL AFTER `expire` ;

CREATE TABLE `comments` (
  `comment_id` varchar(32) NOT NULL default '',
  `object_id` varchar(32) NOT NULL default '',
  `user_id` varchar(32) NOT NULL default '',
  `content` text NOT NULL,
  `mkdate` int(20) NOT NULL default '0',
  `chdate` int(20) NOT NULL default '0',
  PRIMARY KEY  (`comment_id`),
  KEY `object_id` (`object_id`)
) TYPE=MyISAM; 

# #6
# StEP00029: changes for archiving wiki-pages feature
#

ALTER TABLE `archiv` ADD `wikidump` LONGTEXT AFTER `forumdump` ; 

# #7
# StEP00009: changes to ressources-management system
#

ALTER TABLE `resources_locks` ADD `type` VARCHAR( 15 ) NOT NULL ;
ALTER TABLE `resources_temporary_events` ADD `type` VARCHAR( 15 ) NOT NULL AFTER `end` ;

# #8
# optimize some tables
#

ALTER TABLE `object_user_visits` ADD INDEX ( `user_id` );

ALTER TABLE `px_topics` DROP INDEX `user_id` ;
ALTER TABLE `px_topics` ADD INDEX ( `user_id` , `Seminar_id` ) ;

ALTER TABLE `wap_sessions` ADD PRIMARY KEY ( `session_id` ) ;

ALTER TABLE `vote_user` ADD INDEX ( `user_id` ) ;
ALTER TABLE `guestbook` DROP INDEX `range_id` , ADD INDEX `range_id` ( `range_id` , `mkdate` )



############################################################

# new settings and data
# We introduce with this version the possibility to store settings and data in the DB
# Every new version will insert new *default* setting as DB-querys.
# PLEASE NOTE: The old configuration-system (local.inc and config.inc.php) ist still used
# and extended at this time, until we will switch to the new DB-configuration system at all. 
# 

INSERT INTO `config` VALUES ('3d415eca6003321f09e59407e4a7994d', '', 'RESOURCES_LOCKING_ACTIVE', '', 1, 'boolean', 'global', 'resources', 0, 0, 1100709567, 'Schaltet in der Ressourcenverwaltung das Blockieren der Bearbeitung für einen Zeitraum aus (nur Admins dürfen in dieser Zeit auf die Belegung zugreifen)', '', '');
INSERT INTO `config` VALUES ('b7a2817d142443245df2f5ac587fe218', '', 'RESOURCES_ALLOW_ROOM_REQUESTS', '', 1, 'boolean', 'global', '', 0, 0, 1100709567, 'Schaltet in der Ressourcenverwaltung das System zum Stellen und Bearbeiten von Raumanfragen ein oder aus', '', '');
INSERT INTO `config` VALUES ('d821ffbff29ce636c6763ffe3fd8b427', '', 'RESOURCES_ALLOW_CREATE_ROOMS', '2', 1, 'integer', 'global', '', 0, 0, 1100709567, 'Welche Rechstufe darf  Räume anlegen? 1 = Nutzer ab Status tutor, 2 = Nutzer ab Status admin, 3 = nur Ressourcenadministratoren', '', '');
INSERT INTO `config` VALUES ('5a6e2342b90530ed50ad8497054420c0', '', 'RESOURCES_ALLOW_ROOM_PROPERTY_REQUESTS', '1', 1, 'boolean', 'global', '', 0, 0, 1074780851, 'Schaltet in der Ressourcenverwaltung die Möglichkeit, im Rahmen einer Anfrage Raumeigenschaften zu wünschen, ein oder aus', '', '');
INSERT INTO `config` VALUES ('e4123cf9158cd0b936144f0f4cf8dfa3', '', 'RESOURCES_INHERITANCE_PERMS_ROOMS', '1', 1, 'integer', 'global', '', 0, 0, 1100709567, 'Art der Rechtevererbung in der Ressourcenverwaltung für Räume: 1 = lokale Rechte der Einrichtung und Veranstaltung werden übertragen, 2 = nur Autorenrechte werden vergeben, 3 = es werden keine Rechte vergeben', '', '');
INSERT INTO `config` VALUES ('45856b1e3407ce565d87ec9b8fd32d7d', '', 'RESOURCES_INHERITANCE_PERMS', '1', 1, 'integer', 'global', '', 0, 0, 1100709567, 'Art der Rechtevererbung in der Ressourcenverwaltung für Ressourcen (nicht Räume): 1 = lokale Rechte der Einrichtung und Veranstaltung werden übertragen, 2 = nur Autorenrechte werden vergeben, 3 = es werden keine Rechte vergeben', '', '');
INSERT INTO `config` VALUES ('c353c73d8f37e3c301ae34898c837af4', '', 'RESOURCES_ENABLE_ORGA_CLASSIFY', '1', 1, 'boolean', 'global', '', 0, 0, 1100709567, 'Schaltet in der Ressourcenverwaltung das Einordnen von Ressourcen in Orga-Struktur (ohne Rechtevergabe) ein oder aus', '', '');
INSERT INTO `config` VALUES ('0821671742242add144595b1112399fb', '', 'RESOURCES_ALLOW_SINGLE_ASSIGN_PERCENTAGE', '50', 1, 'integer', 'global', '', 0, 0, 1100709567, 'Wert (in Prozent), ab dem ein Raum mit Einzelbelegungen (statt Serienbelegungen) gefüllt wird, wenn dieser Anteil an möglichen Belegungen bereits durch andere Belegungen zu Überschneidungen führt', '', '');
INSERT INTO `config` VALUES ('94d1643209a8f404dfe71228aad5345d', '', 'RESOURCES_ALLOW_SINGLE_DATE_GROUPING', '5', 1, 'integer', 'global', '', 0, 0, 1100709567, 'Anzahl an Einzeltermine, ab der diese als Gruppe zusammengefasst bearbeitet werden', '', '');
INSERT INTO `config` VALUES ('074ccc86f0313dd695dc8e3ec3cebe73', '', 'HTML_HEAD_TITLE', 'Stud.IP', 1, 'string', 'global', '', 0, 0, 0, 'Angezeigter Titel in der Kopfzeile des Browsers', '', '');
INSERT INTO `config` VALUES ('f2f8a47ea69ed9ccba5573e85a15662c', '', 'ACCESSKEY_ENABLE', '', 1, 'boolean', 'user', '', 0, 0, 0, 'Schaltet die Nutzung von Shortcuts für einen User ein oder aus, Systemdefault', '', '');
INSERT INTO `config` VALUES ('0b00c75bc76abe0dd132570403b38e5c', '', 'NEWS_RSS_EXPORT_ENABLE', '1', 1, 'boolean', 'global', '', 0, 0, 0, 'Schaltet die Möglichkeit des rss-Export von privaten News global ein oder aus', '', '');
INSERT INTO `config` VALUES ('42d237f9dfd852318cdc66319043536d', '', 'FOAF_SHOW_IDENTITY', '', 1, 'boolean', 'user', '', 0, 0, 0, 'Schaltet für einen User ein oder aus, ob dieser in FOAS-Ketten angezeigt wird, Systemdefault', '', '');
INSERT INTO `config` VALUES ('6ae7aecf299930cbb8a5e89bbab4da55', '', 'FOAF_ENABLE', '1', 1, 'boolean', 'global', '', 0, 0, 0, 'FOAF Feature benutzen?', '', '');
INSERT INTO `config` VALUES ('a52e3b62ac0bee819b782d8979960b7b', '', 'RESOURCES_ENABLE_GROUPING', '1', 1, 'boolean', 'global', '', 0, 0, 1121861801, 'Schaltet in der Ressourcenverwaltung die Funktion zur Verwaltung von Raumgruppen ein oder aus', '', '');
INSERT INTO `config` VALUES ('76cac679fa57fdbb3f9d6cee20bf8c6f', '', 'RESOURCES_ENABLE_SEM_SCHEDULE', '1', 1, 'boolean', 'global', '', 0, 0, 0, 'Schaltet in der Ressourcenverwaltung ein, ob ein Semesterbelegungsplan erstellt werden kann', '', '');
INSERT INTO `config` VALUES ('3af783748f92cdf99b066d4227f8dffc', '', 'RESOURCES_SEARCH_ONLY_REQUESTABLE_PROPERTY', '', 1, 'boolean', 'global', '', 0, 0, 0, 'Schaltet in der Suche der Ressourcenverwaltun das Durchsuchen von nicht wünschbaren Eigenschaften ein oder aus', '', '');
INSERT INTO `config` VALUES ('fe498bb91a4cbfdfd5078915e979153c', '', 'RESOURCES_ENABLE_VIRTUAL_ROOM_GROUPS', '1', 1, 'boolean', 'global', '', 0, 0, 0, 'Schaltet in der Ressourcenverwaltung automatische gebildete Raumgruppen neben per Konfigurationsdatei definierten Gruppen ein oder aus', '', '');
INSERT INTO `config` VALUES ('68b127dde744085637d221e11d4e8cf2', '', 'RESOURCES_ALLOW_CREATE_TOP_LEVEL', '', 1, 'boolean', 'global', '', 0, 0, 0, 'Schaltet für die Ressourcenverwaltung ein, ob neue Hierachieebenen von anderen Nutzern als Admins angelegt werden können oder nicht', '', '');
INSERT INTO `config` VALUES ('b16359d5514b13794689eab669124c69', '', 'ALLOW_DOZENT_VISIBILITY', '', 1, 'boolean', 'global', '', 0, 0, 0, 'Schaltet ein oder aus, ob ein Dozent eigene Veranstaltungen selbst verstecken darf oder nicht', '', '');
INSERT INTO `config` VALUES ('e8cd96580149cde65ad69b6cf18d5c39', '', 'ALLOW_DOZENT_ARCHIV', '', 1, 'boolean', 'global', '', 0, 0, 1109946684, 'Schaltet ein oder aus, ob ein Dozent eigene Veranstaltungen selbst archivieren darf oder nicht', '', '');
INSERT INTO `config` VALUES ('24ecbeb431826c61fd8b53b3aa41bfa6', '', 'SHOWSEM_ENABLE', '1', 1, 'boolean', 'user', '', 0, 1122461027, 1122461027, 'Einstellung für Nutzer, ob Semesterangaben in der Übersicht "Meine Veranstaltung" nach dem Titel der Veranstaltung gemacht werden; Systemdefault', '', '');
INSERT INTO `config` VALUES ('91e6e53b3748a53c42440453e8045be3', '', 'RESOURCES_ALLOW_SEMASSI_SKIP_REQUEST', '1', 1, 'boolean', 'global', '', 0, 1122565305, 1122565305, 'Schaltet das Pflicht, eine Raumanfrage beim Anlegen einer Veranstaltung machen zu müssen, ein oder aus', '', '');
INSERT INTO `config` VALUES ('f32367b1542a1d513ecee8a26e26d239', '', 'RESOURCES_SCHEDULE_EXPLAIN_USER_NAME', '1', 1, 'boolean', 'global', '', 0, 1123516671, 1123516671, 'Schaltet in der Ressourcenverwaltung die Anzeige der Namen des Belegers in der Ausgabe von Belegungsplänen ein oder aus', '', '');
INSERT INTO `config` VALUES ('4c52bfa598daa03944a401b66c53d828', '', 'NEWS_DISABLE_GARBAGE_COLLECT', '0', 1, 'boolean', 'global', '', 0, 1123751948, 1123751948, 'Schaltet den Garbage-Collect für News ein oder aus', '', '');
