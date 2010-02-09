# mysql migration script
# base version: 0.9.0
# update version: 0.9.5

PLEASE NOTE: Since a migration-tool is not written yet, please use this script MANUEL to convert your old database to
a newer version.
Dont't paste this script directly into your SQL-client, because you have to excute the convert scripts and/or delete-queries 
in the specified order!

# For detailed informations, please take a look at the update protocol from our installation in goettingen!
# (Should be located in the same folder)
#
#
# #1
# changes to the table user_info (this fields were ununsed since a longer time)
#

ALTER TABLE `user_info` DROP `raum` , DROP `sprechzeiten` , DROP `hide_studiengang` ;
ALTER TABLE `user_info` DROP `Lehre` ;

# #2
# changes to the table archiv (this fields were ununsed since a longer time)
#

ALTER TABLE `archiv` DROP `fakultaet_id`;
ALTER TABLE `archiv` CHANGE `institute` `institute` TEXT NOT NULL;
ALTER TABLE `archiv` CHANGE `dozenten` `dozenten` TEXT NOT NULL;

# #3
# changes to the table Institute
#

ALTER TABLE `Institute` CHANGE `url` `url` VARCHAR(255) NOT NULL;

# #4
# changes to the tablew news (bugfix - news title was to short)
#

ALTER TABLE `news` CHANGE `topic` `topic` VARCHAR(255) NOT NULL

# #5
# add new fields for the modularization-system
#

ALTER TABLE `seminare` ADD `modules` INT UNSIGNED AFTER `showscore`;
ALTER TABLE `Institute` ADD `modules` INT UNSIGNED AFTER `type`;

# #6
# add a new field for the ILIAS-connection-module
#

ALTER TABLE `seminar_lernmodul` ADD `status` TINYINT NOT NULL;
UPDATE `seminar_lernmodul` SET status = 1;

# #7
# add new tables for the WikiWikiWeb-module
#

DROP TABLE IF EXISTS `wiki`;
CREATE TABLE `wiki` (
`range_id` varchar(32) NOT NULL default '',
`user_id` varchar(32) default NULL,
`keyword` varchar(128) NOT NULL default '',
`body` text,
`chdate` int(11) default NULL,
`version` int(11) NOT NULL default '0',
PRIMARY KEY (`range_id`,`keyword`,`version`),
KEY `user_id` (`user_id`),
KEY `chdate` (`chdate`)
) TYPE=MyISAM;

DROP TABLE IF EXISTS wiki_locks;
CREATE TABLE `wiki_locks` (
`user_id` varchar(32) NOT NULL default '',
`range_id` varchar(32) NOT NULL default '',
`keyword` varchar(128) NOT NULL default '',
`chdate` int(11) NOT NULL default '0',
PRIMARY KEY (`range_id`,`keyword`,`user_id`),
KEY `user_id` (`user_id`),
KEY `chdate` (`chdate`)
) TYPE=MyISAM;

DROP TABLE IF EXISTS `wiki_links`;
CREATE TABLE `wiki_links` (
`range_id` char(32) NOT NULL default '',
`from_keyword` char(128) NOT NULL default '',
`to_keyword` char(128) NOT NULL default '',
PRIMARY KEY (`range_id`,`to_keyword`,`from_keyword`)
) TYPE=MyISAM;

# #8
# changes to the table auth_user_md5 for external plugin extension
#

ALTER TABLE `auth_user_md5` ADD `auth_plugin` VARCHAR( 64 ) ;

# #9
# create new tables for object-operation-system (rates and views)
#

CREATE TABLE `object_rate` (
`object_id` varchar(32) NOT NULL default '',
`rate` int(10) NOT NULL default '0',
`mkdate` int(20) NOT NULL default '0',
KEY `object_id` (`object_id`),
KEY `rate` (`rate`)
) TYPE=MyISAM;

CREATE TABLE `object_user` (
`object_id` varchar(32) NOT NULL default '',
`user_id` varchar(32) NOT NULL default '',
`flag` varchar(32) NOT NULL default '',
`mkdate` int(20) NOT NULL default '0',
PRIMARY KEY (`object_id`,`user_id`,`flag`)
) TYPE=MyISAM;

CREATE TABLE `object_views` (
`object_id` varchar(32) NOT NULL default '',
`views` int(20) NOT NULL default '0',
`chdate` int(20) NOT NULL default '0',
PRIMARY KEY (`object_id`)
) TYPE=MyISAM;

# #10
# create new tables for vote-module
#

CREATE TABLE `vote` (
`vote_id` varchar(32) NOT NULL default '',
`author_id` varchar(32) NOT NULL default '',
`range_id` varchar(32) NOT NULL default '',
`type` enum('vote','test') NOT NULL default 'vote',
`title` varchar(100) NOT NULL default '',
`question` varchar(255) NOT NULL default '',
`state` enum('new','active','stopvis','stopinvis') NOT NULL default 'new',
`startdate` int(20) default NULL,
`stopdate` int(20) default NULL,
`timespan` int(20) default NULL,
`mkdate` int(20) NOT NULL default '0',
`chdate` int(20) NOT NULL default '0',
`resultvisibility` enum('ever','delivery','end','never') NOT NULL default 'ever',
`namesvisibility` tinyint(1) NOT NULL default '0',
`multiplechoice` tinyint(1) NOT NULL default '0',
`anonymous` tinyint(1) NOT NULL default '1',
`changeable` tinyint(1) NOT NULL default '0',
`co_visibility` tinyint(1) default NULL,
PRIMARY KEY (`vote_id`),
KEY `range_id` (`range_id`),
KEY `state` (`state`),
KEY `startdate` (`startdate`),
KEY `stopdate` (`stopdate`),
KEY `resultvisibility` (`resultvisibility`),
KEY `chdate` (`chdate`),
KEY `author_id` (`author_id`)
) TYPE=MyISAM PACK_KEYS=1;

CREATE TABLE `vote_user` (
`vote_id` varchar(32) NOT NULL default '',
`user_id` varchar(32) NOT NULL default '',
`votedate` int(20) default NULL,
PRIMARY KEY (`vote_id`,`user_id`)
) TYPE=MyISAM PACK_KEYS=1;

CREATE TABLE `voteanswers` (
`answer_id` varchar(32) NOT NULL default '',
`vote_id` varchar(32) NOT NULL default '',
`answer` varchar(255) NOT NULL default '',
`position` int(11) NOT NULL default '0',
`counter` int(11) NOT NULL default '0',
`correct` tinyint(1) default NULL,
PRIMARY KEY (`answer_id`),
KEY `vote_id` (`vote_id`),
KEY `position` (`position`)
) TYPE=MyISAM PACK_KEYS=1;

CREATE TABLE `voteanswers_user` (
`answer_id` varchar(32) NOT NULL default '',
`user_id` varchar(32) NOT NULL default '',
`votedate` int(20) default NULL,
PRIMARY KEY (`answer_id`,`user_id`)
) TYPE=MyISAM PACK_KEYS=1;

# #11
# changes for extensions to the admission-system
#

ALTER TABLE `seminare` ADD `admission_group` VARCHAR(32) NULL AFTER `admission_selection_take_place`;
ALTER TABLE `seminare` ADD `admission_prelim` TINYINT(4) UNSIGNED DEFAULT "0" NOT NULL AFTER `admission_group`;
ALTER TABLE `seminare` ADD `admission_prelim_txt` TEXT AFTER `admission_prelim`;
ALTER TABLE `seminare` ADD `admission_starttime` INT(20) DEFAULT "-1" NOT NULL AFTER `admission_prelim_txt`;
ALTER TABLE `seminare` ADD `admission_endtime_sem` INT(20) DEFAULT "-1" NOT NULL AFTER `admission_starttime`;
ALTER TABLE `seminare` ADD `visible` TINYINT(2) UNSIGNED DEFAULT "1" NOT NULL AFTER `admission_endtime_sem`;

ALTER TABLE `seminar_user` ADD `comment` TINYTEXT;

ALTER TABLE `admission_seminar_user` ADD `comment` TINYTEXT;

# #12
# changes the table dokumente
#

ALTER TABLE `dokumente` ADD `name` VARCHAR(255) AFTER `seminar_id`

# #13
# create new tables for new massaging system
#

CREATE TABLE message_user (
user_id varchar(32) NOT NULL default '',
message_id varchar(32) NOT NULL default '',
readed tinyint(1) NOT NULL default '0',
deleted tinyint(1) NOT NULL default '0',
snd_rec char(3) NOT NULL default '',
PRIMARY KEY (user_id,message_id)
) TYPE=MyISAM;

CREATE TABLE message (
message_id varchar(32) NOT NULL default '',
chat_id varchar(32) default NULL,
autor_id varchar(32) NOT NULL default '',
message text NOT NULL,
mkdate int(20) NOT NULL default '0',
PRIMARY KEY (message_id),
KEY chat_id (chat_id),
KEY autor_id (autor_id)
) TYPE=MyISAM;

# #14
# >>>please use the script convert_globalmessages.php at this point!
#

# #15
# create new tables for the generic datafields
#

CREATE TABLE datafields (
datafield_id varchar(32) NOT NULL default '',
name varchar(255) default NULL,
object_type enum('sem','inst','user') default NULL,
object_class varchar(10) default NULL,
edit_perms enum('user','autor','tutor','dozent','admin','root') default NULL,
view_perms varchar(10) default NULL,
priority tinyint(3) unsigned NOT NULL default '0',
mkdate int(20) unsigned default NULL,
chdate int(20) unsigned default NULL,
PRIMARY KEY (datafield_id)
) TYPE=MyISAM;

CREATE TABLE datafields_entries (
datafield_id varchar(32) NOT NULL default '',
range_id varchar(32) NOT NULL default '',
content text,
mkdate int(20) unsigned default NULL,
chdate int(20) unsigned default NULL,
PRIMARY KEY (datafield_id,range_id)
) TYPE=MyISAM;

# #16
# changes to the resource-management-system 
#

ALTER TABLE `resources_assign` CHANGE `assign_user_id` `assign_user_id` VARCHAR(32)

# #17
# >>>please use the script convert_forum_edit.php at this point!
#

# #18
# drop the now unused old table globalmessages
#

DROP TABLE `globalmessages` 
