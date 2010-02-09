# mysql migration script
# base version: 1.1
# update version: 1.1.5

PLEASE NOTE: Since there exists no migration-tool, please use this script MANUEL to convert your old database to
a newer version.
Don`t paste this script directly into your SQL-client, because you have to excute the convert scripts and/or delete-queries 
in the specified order! 

# For detailed informations, please take a look at the update protocol from our installation in Goettingen!
# (Should be located in the same folder)

# #1
# create new table for visits
#

CREATE TABLE `object_user_visits` (
`object_id` varchar(32) NOT NULL default '',
`user_id` varchar(32) NOT NULL default '',
`type` varchar(20) NOT NULL default '',
`visitdate` int(20) NOT NULL default '0',
`last_visitdate` int(20) NOT NULL default '0',
PRIMARY KEY (`object_id`,`user_id`,`type`)
) TYPE=MyISAM;

# #2
# >>>please use the script convert_loginfile.php at this point
#

# #3
# changes to the messaging system
#

ALTER TABLE `message` ADD `subject` VARCHAR( 255 ) NOT NULL AFTER `autor_id` ;
ALTER TABLE `message` ADD `readed` TINYINT( 1 ) DEFAULT '0' NOT NULL AFTER `mkdate` ;
ALTER TABLE `message` ADD `reading_confirmation` INT ( 1 ) DEFAULT '0' NOT NULL;
ALTER TABLE `message_user` ADD `confirmed_read` TINYINT( 1 ) DEFAULT '0' NOT NULL ;
ALTER TABLE `message_user` ADD `answered` TINYINT( 1 ) DEFAULT '0' NOT NULL ;

# #4
# change the indexes of messaging for better performance
#

ALTER TABLE `message_user` DROP PRIMARY KEY ;
ALTER TABLE `message_user` ADD PRIMARY KEY ( `user_id` , `snd_rec` , `message_id` ) ;
ALTER TABLE `message_user` ADD INDEX ( `message_id` ) ;
ALTER TABLE `message_user` ADD INDEX ( `user_id` , `snd_rec` , `deleted` , `folder` ) ;
ALTER TABLE `message_user` ADD INDEX ( `user_id` , `snd_rec` , `deleted` , `folder` ) ;

# #5
# >>>please use the script convert_sms_subject.php at this point
#

# #6
# >>>please use the script convert_sms_user_info.php at this point
#

# #7
# changes to the wiki
#

ALTER TABLE `wiki` CHANGE `keyword` `keyword` VARCHAR( 128 ) BINARY NOT NULL ;
ALTER TABLE `wiki_links` CHANGE `to_keyword` `to_keyword` CHAR( 128 ) BINARY NOT NULL ;
ALTER TABLE `wiki_links` CHANGE `from_keyword` `from_keyword` CHAR( 128 ) BINARY NOT NULL ;
ALTER TABLE `wiki_locks` CHANGE `keyword` `keyword` VARCHAR( 128 ) BINARY NOT NULL ;

# #8
# create new table and changes for new smiley-management
#

CREATE TABLE `smiley` (
  `smiley_id` bigint(20) NOT NULL auto_increment,
  `smiley_name` varchar(50) NOT NULL default '',
  `smiley_width` int(11) NOT NULL default '0',
  `smiley_height` int(11) NOT NULL default '0',
  `short_name` varchar(50) NOT NULL default '',
  `smiley_counter` bigint(20) NOT NULL default '0',
  `short_counter` bigint(20) NOT NULL default '0',
  `fav_counter` bigint(20) NOT NULL default '0',
  `mkdate` int(10) unsigned default NULL,
  `chdate` int(10) unsigned default NULL,
  PRIMARY KEY  (`smiley_id`),
  UNIQUE KEY `name` (`smiley_name`),
  KEY `short` (`short_name`)
) TYPE=MyISAM;

ALTER TABLE user_info
  ADD smiley_favorite VARCHAR(255) NOT NULL ,
  ADD smiley_favorite_publish TINYINT(1) DEFAULT '0' NOT NULL ;
  
# #9
# changes to the table user_inst
#

ALTER TABLE `user_inst` ADD `externdefault` TINYINT( 3 ) UNSIGNED DEFAULT '0' NOT NULL ,
ADD `priority` TINYINT( 3 ) UNSIGNED DEFAULT '0' NOT NULL ;
ALTER TABLE `user_inst` CHANGE `raum` `raum` VARCHAR( 200 ) NOT NULL, 
ADD `visible` TINYINT UNSIGNED DEFAULT '1' NOT NULL ; 

# #10
# change the indexes of votes for better performance
#

ALTER TABLE `voteanswers_user` ADD INDEX ( `user_id` );

#11
#
#
ALTER TABLE `object_views` ADD INDEX ( `views` ) ;

#12
#
#
ALTER TABLE `seminar_user` DROP INDEX `Seminar_id` ;
ALTER TABLE `seminar_user` ADD INDEX ( `status` , `Seminar_id` ) ;

#13
#
#
ALTER TABLE `active_sessions` DROP PRIMARY KEY ;
ALTER TABLE `active_sessions` DROP INDEX `changed`;

ALTER TABLE `active_sessions` ADD PRIMARY KEY ( `sid` , `name` );
ALTER TABLE `active_sessions` ADD INDEX ( `name` , `changed`);
