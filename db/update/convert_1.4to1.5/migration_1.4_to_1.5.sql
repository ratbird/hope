# mysql migration script
# base version: studip-1.4
# update version: studip-1.5

PLEASE NOTE: Since there exists no migration-tool, please use this script to convert your old database to
a newer version.
Don`t paste this script directly into your SQL-client, because you may have to excute some convert scripts and/or
delete-queries at specified points!

# For detailed informations, please take a look at the update protocol from our installation in Goettingen!
# (Should be located in the same folder)

#
# StEP00069: Nicht abonnieren, sondern nur in Stundenplan eintragen
#
CREATE TABLE `seminar_user_schedule` (
	`range_id` varchar(32) NOT NULL default '',
	`user_id` varchar(32) NOT NULL default '',
	PRIMARY KEY  (`range_id`,`user_id`)
) TYPE=MyISAM;

#
# StEP00075: Dozentenreihenfolge und -bezeichnung
#

ALTER TABLE `seminar_user` ADD `position` TINYINT UNSIGNED NOT NULL AFTER `status` ;

#
# StEP00078: Performanceoptimierungen, Konfigurationsoptionen
#
ALTER TABLE `user_info` DROP INDEX `guestbook`;
INSERT INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES ('4cd2cd3cc207ffc0ae92721c291cd906', '', 'RESOURCES_SHOW_ROOM_NOT_BOOKED_HINT', '0', 1, 'boolean', 'global', '', 0, 1168444600, 1168444600, 'Einstellung, ob bei aktivierter Raumverwaltung Raumangaben die nicht gebucht sind gekennzeichnet werden', '', '');
INSERT INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES (
MD5( 'SEM_CREATE_PERM' ) , '', 'SEM_CREATE_PERM', 'dozent', '1', 'string', 'global', '', '0', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Bestimmt den globalen Nutzerstatus, ab dem Veranstaltungen angelegt werden dürfen (root,admin,dozent)', '', ''
);
INSERT INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES (
MD5( 'SEM_VISIBILITY_PERM' ) , '', 'SEM_VISIBILITY_PERM', 'root', '1', 'string', 'global', '', '0', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Bestimmt den globalen Nutzerstatus, ab dem versteckte Veranstaltungen in der Suche gefunden werden (root,admin,dozent)', '', ''
);
INSERT INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES (MD5('ENABLE_SKYPE_INFO'), '', 'ENABLE_SKYPE_INFO', '1', '1', 'boolean', 'global', '', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Ermöglicht die Eingabe / Anzeige eines Skype Namens ', '', '');


#
# StEP00082: Erweiterung SCM
#
ALTER TABLE `scm` DROP INDEX `range_id` ,
ADD INDEX `range_id` ( `range_id` ) ;

#
# StEP00085: Erweiterung SCM
#
ALTER TABLE `seminare` ADD `admission_disable_waitlist` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `admission_endtime_sem` ;
INSERT INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) VALUES (MD5('ADMISSION_ALLOW_DISABLE_WAITLIST'), '', 'ADMISSION_ALLOW_DISABLE_WAITLIST', '1', '1', 'boolean', 'global', '', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Schaltet ein oder aus, ob die Warteliste in Zugangsbeschränkten Veranstaltungen deaktiviert werden kann', '', '');


