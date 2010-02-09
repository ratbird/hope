# mysql migration script
# base version: 0.9.5
# update version: 1.0

PLEASE NOTE: Since a migration-tool is not written yet, please use this script MANUEL to convert your old database to
a newer version.
Dont't paste this script directly into your SQL-client, because you have to excute the convert scripts and/or delete-queries 
in the specified order!

# For detailed informations, please take a look at the update protocol from our installation in goettingen!
# (Should be located in the same folder)
#
#
# #1
# create new table for studip-wap
#

CREATE table wap_sessions (user_id CHAR(32) NOT NULL, session_id CHAR(32) NOT NULL, creation_time DATETIME);

# #2
# changes for new messaging functionality 
#

ALTER TABLE `message_user` DROP PRIMARY KEY, ADD PRIMARY KEY (user_id,message_id,snd_rec), ADD KEY (message_id);
ALTER TABLE `user_info` ADD `smsforward_copy` TINYINT( 1 ) DEFAULT '1' NOT NULL ;
ALTER TABLE `user_info` ADD `smsforward_rec` VARCHAR( 32 ) NOT NULL ;
ALTER TABLE `message_user` ADD `dont_delete` TINYINT( 1 ) DEFAULT '0' NOT NULL; 
ALTER TABLE `message_user` ADD `folder` INT( 5 ) DEFAULT '0' NOT NULL;

# #3
# changes for new vote functionality 
#

ALTER TABLE `vote` CHANGE `question` `question` TEXT NOT NULL;

# #4
# new tables and changes for the guestbook
#

CREATE TABLE guestbook (
post_id varchar(32) NOT NULL default '',
range_id varchar(32) NOT NULL default '',
user_id varchar(32) NOT NULL default '',
mkdate int(20) NOT NULL default '0',
content text NOT NULL,
PRIMARY KEY (post_id),
KEY range_id (range_id),
KEY user_id (user_id)
) TYPE=MyISAM;

ALTER TABLE `user_info` ADD `guestbook` TINYINT( 4 ) DEFAULT '0' NOT NULL ;

# #5
# changes for the datafields
#

ALTER TABLE `datafields` ADD INDEX ( `object_type` );

# #6
# new table for the banner ads (Note to create a new directory 'pictures/banner' 
# with write access for the webserver
#

CREATE TABLE banner_ads (  
ad_id varchar(32) NOT NULL default '',  
banner_path varchar(255) NOT NULL default '',  
description varchar(255) default NULL,  
alttext varchar(255) default NULL,  
target_type enum('url','seminar','inst','user','none') NOT NULL default 'url',  
target varchar(255) NOT NULL default '',  
startdate int(20) NOT NULL default '0',  
enddate int(20) NOT NULL default '0',  
priority int(4) NOT NULL default '0',  
views int(11) NOT NULL default '0',  
clicks int(11) NOT NULL default '0',  
mkdate int(20) NOT NULL default '0',  
chdate int(20) NOT NULL default '0',  
PRIMARY KEY  (ad_id)
) TYPE=MyISAM;

# #7
# changes for statusgruppen
#

ALTER TABLE `statusgruppe_user` ADD `position` INT( 11 ) NOT NULL ;
ALTER TABLE `statusgruppen` ADD `selfassign` TINYINT( 4 ) NOT NULL AFTER `size` ;

# #8
# >>>please use the script convert_statusgruppe_user.php at this point!
#

# #9
# changes for new simple-content-system
#

CREATE TABLE `scm` (  
`scm_id` varchar(32) NOT NULL default '',  
`range_id` varchar(32) NOT NULL default '',  
`user_id` varchar(32) NOT NULL default '',  
`tab_name` varchar(20) NOT NULL default 'Info',  
`content` text,  `mkdate` int(20) NOT NULL default '0',  
`chdate` int(20) NOT NULL default '0',  
PRIMARY KEY  (`scm_id`),  
UNIQUE KEY `range_id` (`range_id`),  
KEY `chdate` (`chdate`)
) TYPE=MyISAM;

# #10
# >>>please use the script convert_literatur_to_scm.php at this point!
#

# #11
# new tables for new literature-system
#

CREATE TABLE `lit_catalog` (  
`catalog_id` varchar(32) NOT NULL default '',  
`user_id` varchar(32) NOT NULL default '',  
`mkdate` int(11) NOT NULL default '0',  
`chdate` int(11) NOT NULL default '0',  
`lit_plugin` varchar(100) NOT NULL default 'Studip',  
`accession_number` varchar(100) default NULL,  
`dc_title` varchar(255) NOT NULL default '',  
`dc_creator` varchar(255) NOT NULL default '',  
`dc_subject` varchar(255) default NULL,  
`dc_description` text,  
`dc_publisher` varchar(255) default NULL,  
`dc_contributor` varchar(255) default NULL,  
`dc_date` date default NULL,  
`dc_type` varchar(100) default NULL,  
`dc_format` varchar(100) default NULL,  
`dc_identifier` varchar(255) default NULL,  
`dc_source` varchar(255) default NULL,  
`dc_language` varchar(10) default NULL,  
`dc_relation` varchar(255) default NULL,  
`dc_coverage` varchar(255) default NULL,  
`dc_rights` varchar(255) default NULL,  
PRIMARY KEY  (`catalog_id`)
) TYPE=MyISAM;

CREATE TABLE `lit_list` (  
`list_id` varchar(32) NOT NULL default '',  
`range_id` varchar(32) NOT NULL default '',  
`name` varchar(255) NOT NULL default '',  
`format` varchar(255) NOT NULL default '',  
`user_id` varchar(32) NOT NULL default '',  
`mkdate` int(11) NOT NULL default '0',  
`chdate` int(11) NOT NULL default '0',  
`priority` smallint(6) NOT NULL default '0',  
`visibility` tinyint(4) NOT NULL default '0',  
PRIMARY KEY  (`list_id`),  
KEY `range_id` (`range_id`),  
KEY `priority` (`priority`),  
KEY `visibility` (`visibility`)
) TYPE=MyISAM;

CREATE TABLE `lit_list_content` (  
`list_element_id` varchar(32) NOT NULL default '',  
`list_id` varchar(32) NOT NULL default '',  
`catalog_id` varchar(32) NOT NULL default '',  
`user_id` varchar(32) NOT NULL default '',  
`mkdate` int(11) NOT NULL default '0',  
`chdate` int(11) NOT NULL default '0',  
`note` varchar(255) default NULL,  
`priority` smallint(6) NOT NULL default '0',  
PRIMARY KEY  (`list_element_id`),  
KEY `list_id` (`list_id`),  
KEY `catalog_id` (`catalog_id`),  
KEY `priority` (`priority`)
) TYPE=MyISAM;

# #12
# changes for new calendar functionalities
#

CREATE TABLE `calendar_events` (
`event_id` varchar(32) NOT NULL default '',
`range_id` varchar(32) NOT NULL default '',
`autor_id` varchar(32) NOT NULL default '',
`uid` varchar(255) NOT NULL default '',
`start` int(10) unsigned NOT NULL default '0',
`end` int(10) unsigned NOT NULL default '0',
`summary` varchar(255) NOT NULL default '',
`description` text,
`class` enum('PUBLIC','PRIVATE','CONFIDENTIAL') NOT NULL default 'PRIVATE',
`categories` tinytext,
`category_intern` tinyint(3) NOT NULL default '0',
`priority` tinyint(3) unsigned NOT NULL default '0',
`location` tinytext,
`ts` int(10) unsigned NOT NULL default '0',
`linterval` smallint(5) unsigned default NULL,
`sinterval` smallint(5) unsigned default NULL,
`wdays` varchar(7) default NULL,
`month` tinyint(3) unsigned default NULL,
`day` tinyint(3) unsigned default NULL,
`rtype` enum('SINGLE','DAILY','WEEKLY','MONTHLY','YEARLY') NOT NULL default 'SINGLE',
`duration` smallint(5) unsigned NOT NULL default '0',
`count` smallint(5) unsigned default '0',
`expire` int(10) unsigned NOT NULL default '0',
`exceptions` text,
`mkdate` int(10) unsigned NOT NULL default '0',
`chdate` int(10) unsigned NOT NULL default '0',
PRIMARY KEY (`event_id`),
UNIQUE KEY `uid_range` (`uid`,`range_id`),
KEY `range_id` (`range_id`),
KEY `autor_id` (`autor_id`)
) TYPE=MyISAM;

# #13
# >>>please use the script convert_termine_calendar_events.php at this point!
#

# #14
# changes for the record of study feature
#

ALTER TABLE `archiv` ADD `VeranstaltungsNummer` VARCHAR(32) NOT NULL;

# #15
# >>>please use the script convert_archiv.php at this point!
#

# #16
# >>>please use the script convert_messaging.php at this point!
#