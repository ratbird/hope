CREATE TABLE IF NOT EXISTS `themen` (
  `issue_id` varchar(32) NOT NULL default '',
  `seminar_id` varchar(32) NOT NULL default '',
  `author_id` varchar(32) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `description` mediumtext NOT NULL,
  `priority` int(11) NOT NULL default '0',
  `mkdate` int(11) NOT NULL default '0',
  `chdate` int(11) NOT NULL default '0',
  PRIMARY KEY (`issue_id`)
);

CREATE TABLE IF NOT EXISTS `ex_termine` (
  `termin_id` varchar(32) NOT NULL default '',
  `range_id` varchar(32) NOT NULL default '',
  `autor_id` varchar(32) NOT NULL default '',
  `content` varchar(255) NOT NULL default '',
  `description` text,
  `date` int(20) NOT NULL default '0',
  `end_time` int(20) NOT NULL default '0',
  `mkdate` int(20) NOT NULL default '0',
  `chdate` int(20) NOT NULL default '0',
  `date_typ` tinyint(4) NOT NULL default '0',
  `topic_id` varchar(32) default NULL,
  `expire` int(20) default NULL,
  `repeat` varchar(128) default NULL,
  `color` varchar(20) default NULL,
  `priority` tinyint(4) default NULL,
  `raum` varchar(255) default NULL,
  `metadate_id` varchar(32) default NULL,
  `resource_id` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`termin_id`),
  KEY `range_id` (`range_id`),
  KEY `autor_id` (`autor_id`)
) TYPE=MyISAM PACK_KEYS=1;

CREATE TABLE IF NOT EXISTS `themen_termine` (
  `issue_id` varchar(32) NOT NULL default '',
  `termin_id` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`issue_id`,`termin_id`)
) TYPE=MyISAM;

ALTER TABLE `termine` ADD `metadate_id` VARCHAR( 32 );
ALTER TABLE `termine` ADD INDEX ( `metadate_id` );

ALTER TABLE `resources_requests` ADD `reply_comment` TEXT AFTER `comment`;