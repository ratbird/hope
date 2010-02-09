# mysql migration script
# base version: studip-1.2
# update version: studip-1.3

PLEASE NOTE: Since there exists no migration-tool, please use this script to convert your old database to
a newer version.
Don`t paste this script directly into your SQL-client, because you may have to excute some convert scripts and/or 
delete-queries at specified points! 

# For detailed informations, please take a look at the update protocol from our installation in Goettingen!
# (Should be located in the same folder)

# #1
# StEP00038 Performanceoptimierung Messaging
#
ALTER TABLE `message_user` ADD `mkdate` INT UNSIGNED NOT NULL ;
UPDATE message_user mu INNER JOIN message m USING ( message_id ) SET mu.mkdate = m.mkdate;
ALTER TABLE `message_user` DROP INDEX `user_id` ,
ADD INDEX `user_id` ( `user_id` , `snd_rec` , `deleted` , `readed` , `mkdate` );
ALTER TABLE `message_user` DROP INDEX `user_id_2` ,
ADD INDEX `user_id_2` ( `user_id` , `snd_rec` , `deleted` , `folder` , `mkdate` );
ALTER TABLE `message` DROP INDEX `mkdate` ;


# #2
# StEP00032: Email-Benachrichtigung bei neuen Inhalten
#
ALTER TABLE `seminar_user` ADD `notification` INT( 10 ) DEFAULT '0' NOT NULL AFTER `admission_studiengang_id` ;
INSERT INTO `config` VALUES ('7291d64d9cc4ea43ee9e8260f05a4111', '', 'MAIL_NOTIFICATION_ENABLE', '0', 1, 'boolean', 'global', '', 0, 1122996278, 1122996278, 'Informationen über neue Inhalte per email verschicken', '', '');

# #3
# StEP0039 ILIAS-3-Anbindung
#
ALTER TABLE `object_user_visits` CHANGE `type` `type` ENUM( 'vote', 'documents', 'forum', 'literature', 'schedule', 'scm', 'sem', 'wiki', 'news', 'eval', 'inst', 'ilias_connect', 'elearning_interface') NOT NULL DEFAULT 'vote';
CREATE TABLE `auth_extern` (
`studip_user_id` varchar(32) NOT NULL default '',
`external_user_id` varchar(32) NOT NULL default '',
`external_user_name` varchar(64) NOT NULL default '',
`external_user_password` varchar(32) NOT NULL default '',
`external_user_category` varchar(32) NOT NULL default '',
`external_user_system_type` varchar(32) NOT NULL default '',
`external_user_type` smallint(6) NOT NULL default '0',
PRIMARY KEY (`studip_user_id`,`external_user_system_type`)
) TYPE=MyISAM;
CREATE TABLE `object_contentmodules` (
`object_id` varchar(32) NOT NULL default '',
`module_id` varchar(32) NOT NULL default '',
`system_type` varchar(32) NOT NULL default '',
`module_type` varchar(32) NOT NULL default '',
`mkdate` int(20) NOT NULL default '0',
`chdate` int(20) NOT NULL default '0',
PRIMARY KEY (`object_id`,`module_id`,`system_type`)
) TYPE=MyISAM;

# #4
# StEP00048: Löschen von Raumanfragen 
#
INSERT INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES ('9f6d7e248f58d1b211314dfb26c77d63', '', 'RESOURCES_ALLOW_DELETE_REQUESTS', '0', 1, 'boolean', 'global', '', 0, 1136826903, 1136826903, 'Erlaubt das Löschen von Raumanfragen für globale Ressourcenadmins', '', '');

# #5
# StEP00051: Wartungsmodus 
#
INSERT INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES ('25bdaf939c88ee79bf3da54165d61a48', '', 'MAINTENANCE_MODE_ENABLE', '0', 1, 'boolean', 'global', '', 0, 0, 0, 'Schaltet das System in den Wartungsmodus, so dass nur noch Administratoren Zugriff haben', '', '');


# #6
# StEP00022: ZIP Upload
#

INSERT INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES ('88c038ca4fb36764ff6486d72379e1ae', '', 'ZIP_UPLOAD_MAX_FILES', '100', 1, 'integer', 'global', '', 0, 1130840930, 1130840930, '', '', '');
INSERT INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES ('c1f9ef95f501893c73e2654296c425f2', '', 'ZIP_UPLOAD_ENABLE', '1', 1, 'boolean', 'global', '', 0, 1130840930, 1130840930, '', '', '');
INSERT INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES ('d733eb0f9ef6db9fb3b461dd4df22376', '', 'ZIP_UPLOAD_MAX_DIRS', '10', 1, 'integer', 'global', '', 0, 1130840962, 1130840962, '', '', '');

# #7
# StEP00049: RSS Feeds für alle News
#

CREATE TABLE `news_rss_range` (
`range_id` char(32) NOT NULL default '',
`rss_id` char(32) NOT NULL default '',
`range_type` enum('user','sem','inst','global') NOT NULL default 'user',
PRIMARY KEY (`range_id`),
KEY `rss_id` (`rss_id`)
) TYPE=MyISAM;
INSERT INTO news_rss_range SELECT user_id, news_author_id,'user' FROM user_info WHERE news_author_id != '';
ALTER TABLE `user_info` DROP `news_author_id` ;

# #8
# StEP00045: Optimierungen für manuellen Import aus Fremdsystemen
#
ALTER TABLE `user_info` ADD `privatcell` VARCHAR( 32 ) NOT NULL AFTER `privatnr` ;

# #9
# StEP00041: Grafische Auswertung von Evaluationen
#
CREATE TABLE `eval_group_template` (
`evalgroup_id` varchar(32) NOT NULL default '',
`user_id` varchar(32) NOT NULL default '',
`group_type` varchar(250) NOT NULL default 'normal',
PRIMARY KEY (`evalgroup_id`,`user_id`)
) TYPE=MyISAM;
CREATE TABLE `eval_templates` (
`template_id` varchar(32) NOT NULL default '',
`user_id` varchar(32) default NULL,
`institution_id` varchar(32) default NULL,
`name` varchar(255) NOT NULL default '',
`show_questions` tinyint(1) NOT NULL default '1',
`show_total_stats` tinyint(1) NOT NULL default '1',
`show_graphics` tinyint(1) NOT NULL default '1',
`show_questionblock_headline` tinyint(1) NOT NULL default '1',
`show_group_headline` tinyint(1) NOT NULL default '1',
`polscale_gfx_type` varchar(255) NOT NULL default 'bars',
`likertscale_gfx_type` varchar(255) NOT NULL default 'bars',
`mchoice_scale_gfx_type` varchar(255) NOT NULL default 'bars',
`kurzbeschreibung` varchar(255) default NULL,
PRIMARY KEY (`template_id`),
KEY `user_id` (`user_id`,`institution_id`,`name`)
) TYPE=MyISAM;
CREATE TABLE `eval_templates_eval` (
`eval_id` varchar(32) NOT NULL default '',
`template_id` varchar(32) NOT NULL default '',
PRIMARY KEY (`eval_id`),
KEY `eval_id` (`eval_id`)
) TYPE=MyISAM;
CREATE TABLE `eval_templates_user` (
`eval_id` varchar(32) NOT NULL default '',
`template_id` varchar(32) NOT NULL default '',
`user_id` varchar(32) NOT NULL default '',
KEY `eval_id` (`eval_id`)
) TYPE=MyISAM;

# #10
# StEP00044: Umgestaltung der Downloadlinks (apache rewrite)
#
INSERT INTO `config` VALUES ('1c07aa46c6fe6fea26d9b0cfd8fbcd19', '', 'SENDFILE_LINK_MODE', 'normal', 1, 'string', 'global', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Format der Downloadlinks: normal=sendfile.php?parameter=x, old=sendfile.php?/parameter=x, rewrite=download/parameter/file.txt', '', '');

# #11
# StEP00046: Nutzerbasierter Konsum von RSS Feeds
#
CREATE TABLE `rss_feeds` (
`feed_id` varchar(32) NOT NULL default '',
`user_id` varchar(32) NOT NULL default '',
`name` varchar(255) NOT NULL default '',
`url` text NOT NULL,
`mkdate` int(20) NOT NULL default '0',
`chdate` int(20) NOT NULL default '0',
`priority` int(11) NOT NULL default '0',
`hidden` tinyint(4) NOT NULL default '0',
`fetch_title` tinyint(3) unsigned NOT NULL default '0',
PRIMARY KEY (`feed_id`),
KEY `user_id` (`user_id`)
) TYPE=MyISAM;

# #12
# StEP00040: Event logging
#
CREATE TABLE `log_events` (
`event_id` varchar(32) NOT NULL default '',
`user_id` varchar(32) NOT NULL default '',
`action_id` varchar(32) NOT NULL default '',
`affected_range_id` varchar(32) default NULL,
`coaffected_range_id` varchar(32) default NULL,
`info` text,
`dbg_info` text,
`mkdate` int(20) NOT NULL default '0',
PRIMARY KEY (`event_id`)
);

CREATE TABLE `log_actions` (
`action_id` varchar(32) NOT NULL default '',
`name` varchar(128) NOT NULL default '',
`description` varchar(64) default NULL,
`info_template` text,
`active` tinyint(1) NOT NULL default '1',
`expires` int(20) default NULL,
PRIMARY KEY (`action_id`)
);

INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('0ee290df95f0547caafa163c4d533991', 'SEM_VISIBLE', 'Veranstaltung sichtbar schalten', '%user schaltet %sem(%affected) sichtbar.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('a94706b41493e32f8336194262418c01', 'SEM_INVISIBLE', 'Veranstaltung unsichtbar schalten', '%user versteckt %sem(%affected).', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('bd2103035a8021942390a78a431ba0c4', 'DUMMY', 'Dummy-Aktion', '%user tut etwas.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('4490aa3d29644e716440fada68f54032', 'LOG_ERROR', 'Allgemeiner Log-Fehler', 'Allgemeiner Logging-Fehler, Details siehe Debug-Info.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('f858b05c11f5faa2198a109a783087a8', 'SEM_CREATE', 'Veranstaltung anlegen', '%user legt %sem(%affected) an.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('5b96f2fe994637253ba0fe4a94ad1b98', 'SEM_ARCHIVE', 'Veranstaltung archivieren', '%user archiviert %info (ID: %affected).', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('bf192518a9c3587129ed2fdb9ea56f73', 'SEM_DELETE_FROM_ARCHIVE', 'Veranstaltung aus Archiv löschen', '%user löscht %info aus dem Archiv (ID: %affected).', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('4869cd69f20d4d7ed4207e027d763a73', 'INST_USER_STATUS', 'Einrichtungsnutzerstatus ändern', '%user ändert Status für %user(%coaffected) in Einrichtung %inst(%affected): %info.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('6be59dcd70197c59d7bf3bcd3fec616f', 'INST_USER_DEL', 'Benutzer aus Einrichtung löschen', '%user löscht %user(%coaffected) aus Einrichtung %inst(%affected).', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('cf8986a67e67ca273e15fd9230f6e872', 'USER_CHANGE_TITLE', 'Akademische Titel ändern', '%user ändert/setzt akademischen Titel für %user(%affected) - %info.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('ca216ccdf753f59ba7fd621f7b22f7bd', 'USER_CHANGE_NAME', 'Personennamen ändern', '%user ändert/setzt Name für %user(%affected) - %info.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('8aad296e52423452fc75cabaf2bee384', 'USER_CHANGE_USERNAME', 'Benutzernamen ändern', '%user ändert/setzt Benutzernamen für %user(%affected): %info.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('59f3f38c905fded82bbfdf4f04c16729', 'INST_CREATE', 'Einrichtung anlegen', '%user legt Einrichtung %inst(%affected) an.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('1a1e8c9c3125ea8d2c58c875a41226d6', 'INST_DEL', 'Einrichtung löschen', '%user löscht Einrichtung %info (%affected).', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('d18d750fb2c166e1c425976e8bca96e7', 'USER_CHANGE_EMAIL', 'E-Mail-Adresse ändern', '%user ändert/setzt E-Mail-Adresse für %user(%affected): %info.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('a92afa63584cc2a62d2dd2996727b2c5', 'USER_CREATE', 'Nutzer anlegen', '%user legt Nutzer %user(%affected) an.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('e406e407501c8418f752e977182cd782', 'USER_CHANGE_PERMS', 'Globalen Nutzerstatus ändern', '%user ändert/setzt globalen Status von %user(%affected): %info', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('63042706e5cd50924987b9515e1e6cae', 'INST_USER_ADD', 'Benutzer zu Einrichtung hinzufügen', '%user fügt %user(%coaffected) zu Einrichtung %inst(%affected) mit Status %info hinzu.', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('4dd6b4101f7bf3bd7fe8374042da95e9', 'USER_NEWPWD', 'Neues Passwort', '%user generiert neues Passwort für %user(%affected)', 1, NULL);
INSERT INTO `log_actions` (`action_id`, `name`, `description`, `info_template`, `active`, `expires`) VALUES ('e8646729e5e04970954c8b9679af389b', 'USER_DEL', 'Benutzer löschen', '%user löscht %user(%affected) (%info)', '1', NULL);

# #13
# StEP00028: Integration einer Plugin-Schnittstelle
#
CREATE TABLE `plugins` (
`pluginid` int(10) unsigned NOT NULL auto_increment,
`pluginclassname` varchar(255) NOT NULL default '',
`pluginpath` varchar(255) NOT NULL default '',
`pluginname` varchar(45) NOT NULL default '',
`plugindesc` varchar(45) NOT NULL default '',
`plugintype` enum('Standard','Administration','System') NOT NULL default 'Standard',
`enabled` enum('yes','no') NOT NULL default 'no',
`navigationpos` int(10) unsigned NOT NULL default '4294967295',
PRIMARY KEY (`pluginid`)
) TYPE=MyISAM;

CREATE TABLE `plugins_activated` (
`pluginid` int(10) unsigned NOT NULL default '0',
`poiid` varchar(255) NOT NULL default '',
`state` enum('on','off') NOT NULL default 'on',
PRIMARY KEY (`pluginid`,`poiid`)
) TYPE=MyISAM;

CREATE TABLE `plugins_default_activations` (
`pluginid` int(10) unsigned NOT NULL default '0',
`institutid` varchar(32) NOT NULL default '',
PRIMARY KEY (`pluginid`,`institutid`)
) TYPE=MyISAM COMMENT='default activations of standard plugins';

INSERT INTO plugins( pluginid,pluginclassname, pluginpath, pluginname, plugindesc, plugintype, enabled, navigationpos )
VALUES (1,'PluginAdministrationPlugin', 'core', 'Plugin-Administration', 'Administrationsoberfläche für Plugins', 'Administration', 'yes', 0);
INSERT INTO plugins_activated( pluginid, poiid, state ) VALUES ( 1, 'admin', 'on' ) ;

# #14
# StEP00047: Semestervorauswahl für Admins
#
INSERT INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES ('9d4956b4eac20f03b60b17d7ac30b40a', '', 'SEMESTER_TIME_SWITCH', '0', 1, 'integer', 'global', '', 0, 1140013696, 1140013696, 'Anzahl der Wochen vor Semesterende zu dem das vorgewählte Semester umspringt', '', '');

# #15
# Indizes
#
ALTER TABLE `user_info` ADD INDEX ( `guestbook` , `user_id` );

