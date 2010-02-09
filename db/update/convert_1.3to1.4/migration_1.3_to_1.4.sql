# mysql migration script
# base version: studip-1.2
# update version: studip-1.3

PLEASE NOTE: Since there exists no migration-tool, please use this script to convert your old database to
a newer version.
Don`t paste this script directly into your SQL-client, because you may have to excute some convert scripts and/or 
delete-queries at specified points! 

# For detailed informations, please take a look at the update protocol from our installation in Goettingen!
# (Should be located in the same folder)

#    	
#StEP00053: Root-Funktion zum Sperren einzelner Benutzer
#
ALTER TABLE `auth_user_md5`
  ADD `locked` TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
  ADD `lock_comment` VARCHAR( 255 ),
  ADD `locked_by` VARCHAR( 32 ); 
  
#    	
#StEP00054: Sichtbarkeit von NutzerInnen
#
ALTER TABLE `auth_user_md5` ADD `visible` ENUM( 'always', 'yes', 'unknown', 'no', 'never' ) DEFAULT 'unknown' NOT NULL ;
ALTER TABLE `seminar_user` ADD `visible` ENUM( 'yes', 'no', 'unknown' ) DEFAULT 'unknown' NOT NULL ;
ALTER TABLE `admission_seminar_user` ADD `visible` ENUM( 'yes', 'no', 'unknown' ) DEFAULT 'unknown' NOT NULL ;
INSERT INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES (
'781e0998a1b5c998ebbc02a4f0d907ac', '', 'USER_VISIBILITY_UNKNOWN', '1', '1', 'boolean', 'global', '', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Sollen Nutzer mit Sichtbarkeit "unknown" wie sichtbare behandelt werden?', '', ''
); 

#    	
#StEP00059: Ordnerberechtigungen im Dateibereich
#
ALTER TABLE `folder` ADD `permission` TINYINT UNSIGNED NOT NULL DEFAULT '7' AFTER `description` ;

#
#StEP00062: Erweiterung der Plugin-Schnittstelle
#
ALTER TABLE `plugins` MODIFY COLUMN `plugintype` ENUM('Standard','Administration','System','Homepage','Portal','Core') NOT NULL DEFAULT 'Standard';

# Anpassung der Plugin-Tabelle für abhängige Plugins
ALTER TABLE `plugins` ADD COLUMN `dependentonid` INTEGER UNSIGNED;

# Rollen-Tabelle
CREATE TABLE `roles` (
`roleid` int(10) unsigned NOT NULL auto_increment,
`rolename` varchar(80) NOT NULL default '',
`system` enum('y','n') NOT NULL default 'n',
PRIMARY KEY (`roleid`)
);

# Tabelle für die Zuweisung von Rollen zu Plugins
CREATE TABLE `roles_plugins` (
`roleid` int(10) unsigned NOT NULL default '0',
`pluginid` int(10) unsigned NOT NULL default '0',
PRIMARY KEY (`roleid`,`pluginid`)
);

# Tabelle für die Zuweisung von Rollen zu Stud.IP-Rechtegruppen
CREATE TABLE `roles_studipperms` (
`roleid` int(10) unsigned NOT NULL default '0',
`permname` varchar(255) NOT NULL default '',
PRIMARY KEY (`roleid`,`permname`)
);

# Tabelle für die Zuweisung von Rollen zu Nutzern
CREATE TABLE `roles_user` (
`roleid` int(10) unsigned NOT NULL default '0',
`userid` char(32) NOT NULL default '',
PRIMARY KEY (`roleid`,`userid`)
);

# Erzeugung von Standard-System-Rollen
insert into roles (roleid,rolename,system) values (1,'Root-Administrator(in)','y');
insert into roles (roleid,rolename,system) values (2,'Administrator(in)','y');
insert into roles (roleid,rolename,system) values (3,'Mitarbeiter(in)','y');
insert into roles (roleid,rolename,system) values (4,'Lehrende(r)','y');
insert into roles (roleid,rolename,system) values (5,'Studierende(r)','y');
insert into roles (roleid,rolename,system) values (6,'Tutor(in)','y');
insert into roles (roleid,rolename,system) values (7,'Nobody','y');

# Zuweisung der Nobody-Rolle zum Nobody-User
insert into roles_user (roleid,userid) values (7,'nobody');

# Zuweisung von Rollen zu den Standard-Stud.IP-Rechtegruppen
insert into roles_studipperms (roleid,permname) values (1,'root');
insert into roles_studipperms (roleid,permname) values (3,'root');
insert into roles_studipperms (roleid,permname) values (2,'admin');
insert into roles_studipperms (roleid,permname) values (3,'admin');
insert into roles_studipperms (roleid,permname) values (4,'dozent');
insert into roles_studipperms (roleid,permname) values (5,'autor');
insert into roles_studipperms (roleid,permname) values (5,'tutor');
insert into roles_studipperms (roleid,permname) values (6,'tutor');

# neue Core-Plugins
insert into plugins (pluginclassname, pluginpath, pluginname, plugindesc, plugintype, enabled, navigationpos, dependentonid) values ('de_studip_core_UserManagementPlugin', 'core', 'UserManagement', '', 'Core', 'yes', 1, 1);
insert into plugins (pluginclassname, pluginpath, pluginname, plugindesc, plugintype, enabled, navigationpos, dependentonid) values ('de_studip_core_RoleManagementPlugin', 'core', 'RollenManagement', 'Administration der Rollen', 'Administration', 'yes', 1, 1);
insert into plugins_activated (pluginid,poiid,state) values (last_insert_id(),'admin','on');

# Standard-Rollen zu bestehenden Plugins zuweisen
insert into roles_plugins (roleid,pluginid) select 1,pluginid from plugins;
insert into roles_plugins (roleid,pluginid) select 2,pluginid from plugins;
insert into roles_plugins (roleid,pluginid) select 3,pluginid from plugins;
insert into roles_plugins (roleid,pluginid) select 4,pluginid from plugins;
insert into roles_plugins (roleid,pluginid) select 5,pluginid from plugins;
insert into roles_plugins (roleid,pluginid) select 6,pluginid from plugins;

#
#StEP00063: Erweiterung der Evaluation
#
-- ALTER TABLE evalgroup MODIFY COLUMN `child_type` enum('EvaluationGroup','EvaluationQuestion','EvaluationText','EvaluationLink') NOT NULL default 'EvaluationGroup';

-- ALTER TABLE `eval` ADD COLUMN `protected` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;

-- CREATE TABLE `eval_link` (
-- `eval_id` varchar(32) NOT NULL default '',
-- `linked_eval_id` varchar(32) NOT NULL default '',
-- PRIMARY KEY (`eval_id`,`linked_eval_id`)
-- );

#
#StEP00065: Neues Hilfe-System
#
INSERT INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES (
MD5( 'EXTERNAL_HELP' ) , '', 'EXTERNAL_HELP', '1', '1', 'boolean', 'global', '', '0', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Schaltet das externe Hilfesystem ein', '', ''
);
INSERT INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES (
MD5( 'EXTERNAL_HELP_LOCATIONID' ) , '', 'EXTERNAL_HELP_LOCATIONID', '', '1', 'string', 'global', '', '0', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Eine eindeutige ID zur Identifikation der gewünschten Hilfeseiten, leer bedeutet Standardhilfe', '', ''
);
INSERT INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES (
MD5( 'EXTERNAL_HELP_URL' ) , '', 'EXTERNAL_HELP_URL', 'http://hilfe.studip.de/index.php/%s', '1', 'string', 'global', '', '0', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'URL Template für das externe Hilfesystem', '', ''
);

#
#StEP00065: Nutzerkommentare bei vorläufiger Anmeldung / Teilnehmerexport anpassen
#
INSERT INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES (
MD5( 'ADMISSION_PRELIM_COMMENT_ENABLE' ) , '', 'ADMISSION_PRELIM_COMMENT_ENABLE', '0', '1', 'boolean', 'global', '', '0', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Schaltet ein oder aus, ob ein Nutzer im Modus "Vorläufiger Eintrag" eine Bemerkung hinterlegen kann', '', ''
);

#
#StEP00070: Anzeige des Namens auf der wer-ist-online konfigurierbar, "Motto"
#
INSERT INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES (
MD5( 'ONLINE_NAME_FORMAT' ) , '', 'ONLINE_NAME_FORMAT', 'full_rev', '1', 'string', 'user', '', '0', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Default-Wert für wer-ist-online Namensformatierung', '', ''
);
ALTER TABLE `user_info` ADD `motto` VARCHAR( 255 ) NOT NULL ;

#
#StEP00070: alternativer Chatclient (AJAX)
#
INSERT INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES (
MD5( 'CHAT_USE_AJAX_CLIENT' ) , '', 'CHAT_USE_AJAX_CLIENT', '0', '1', 'boolean', 'user', '', '0', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Einstellung für Nutzer, ob der AJAX chatclient benutzt werden soll (experimental); Systemdefault', '', ''
);

