-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 07, 2014 at 05:28 PM
-- Server version: 5.5.37-35.0-657.precise
-- PHP Version: 5.3.10-1ubuntu3.11

--
-- Database: `studip_30`
--

-- --------------------------------------------------------

--
-- Table structure for table `abschluss`
--

DROP TABLE IF EXISTS `abschluss`;
CREATE TABLE IF NOT EXISTS `abschluss` (
  `abschluss_id` char(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `beschreibung` text,
  `mkdate` int(20) DEFAULT NULL,
  `chdate` int(20) DEFAULT NULL,
  PRIMARY KEY (`abschluss_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `admissionfactor`
--

DROP TABLE IF EXISTS `admissionfactor`;
CREATE TABLE IF NOT EXISTS `admissionfactor` (
  `list_id` varchar(32) NOT NULL,
  `name` varchar(255) NOT NULL,
  `factor` decimal(5,2) NOT NULL DEFAULT '1.00',
  `owner_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`list_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `admissionrules`
--

DROP TABLE IF EXISTS `admissionrules`;
CREATE TABLE IF NOT EXISTS `admissionrules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ruletype` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `mkdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ruletype` (`ruletype`)
) ENGINE=MyISAM ;

-- --------------------------------------------------------

--
-- Table structure for table `admissionrule_inst`
--

DROP TABLE IF EXISTS `admissionrule_inst`;
CREATE TABLE IF NOT EXISTS `admissionrule_inst` (
  `rule_id` varchar(32) NOT NULL,
  `institute_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rule_id`,`institute_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `admission_condition`
--

DROP TABLE IF EXISTS `admission_condition`;
CREATE TABLE IF NOT EXISTS `admission_condition` (
  `rule_id` varchar(32) NOT NULL,
  `filter_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rule_id`,`filter_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `admission_seminar_user`
--

DROP TABLE IF EXISTS `admission_seminar_user`;
CREATE TABLE IF NOT EXISTS `admission_seminar_user` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `status` varchar(16) NOT NULL DEFAULT '',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `position` int(5) DEFAULT NULL,
  `comment` tinytext,
  `visible` enum('yes','no','unknown') NOT NULL DEFAULT 'unknown',
  PRIMARY KEY (`user_id`,`seminar_id`),
  KEY `seminar_id` (`seminar_id`,`status`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `api_consumers`
--

DROP TABLE IF EXISTS `api_consumers`;
CREATE TABLE IF NOT EXISTS `api_consumers` (
  `consumer_id` char(32) NOT NULL DEFAULT '',
  `consumer_type` enum('http','studip','oauth') NOT NULL DEFAULT 'studip',
  `auth_key` varchar(64) DEFAULT NULL,
  `auth_secret` varchar(64) DEFAULT NULL,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `system` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `type` enum('website','mobile','desktop') DEFAULT 'website',
  `title` varchar(128) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `callback` varchar(255) DEFAULT NULL,
  `commercial` tinyint(1) DEFAULT NULL,
  `description` text,
  `priority` int(11) unsigned NOT NULL DEFAULT '0',
  `notes` text,
  `mkdate` int(11) unsigned NOT NULL,
  `chdate` int(11) unsigned NOT NULL,
  PRIMARY KEY (`consumer_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `api_consumer_permissions`
--

DROP TABLE IF EXISTS `api_consumer_permissions`;
CREATE TABLE IF NOT EXISTS `api_consumer_permissions` (
  `route_id` char(32) NOT NULL,
  `consumer_id` char(32) NOT NULL DEFAULT '',
  `method` char(6) NOT NULL,
  `granted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `route_id` (`route_id`,`consumer_id`,`method`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `api_oauth_user_mapping`
--

DROP TABLE IF EXISTS `api_oauth_user_mapping`;
CREATE TABLE IF NOT EXISTS `api_oauth_user_mapping` (
  `oauth_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(32) NOT NULL DEFAULT '',
  `mkdate` int(11) unsigned NOT NULL,
  PRIMARY KEY (`oauth_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `api_user_permissions`
--

DROP TABLE IF EXISTS `api_user_permissions`;
CREATE TABLE IF NOT EXISTS `api_user_permissions` (
  `user_id` char(32) NOT NULL DEFAULT '',
  `consumer_id` char(32) NOT NULL DEFAULT '',
  `granted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `mkdate` int(11) unsigned NOT NULL,
  `chdate` int(11) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`consumer_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `archiv`
--

DROP TABLE IF EXISTS `archiv`;
CREATE TABLE IF NOT EXISTS `archiv` (
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `untertitel` varchar(255) NOT NULL DEFAULT '',
  `beschreibung` text NOT NULL,
  `start_time` int(20) NOT NULL DEFAULT '0',
  `semester` varchar(16) NOT NULL DEFAULT '',
  `heimat_inst_id` varchar(32) NOT NULL DEFAULT '',
  `institute` varchar(255) NOT NULL DEFAULT '',
  `dozenten` varchar(255) NOT NULL DEFAULT '',
  `fakultaet` varchar(255) NOT NULL DEFAULT '',
  `dump` mediumtext NOT NULL,
  `archiv_file_id` varchar(32) NOT NULL DEFAULT '',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `forumdump` longtext NOT NULL,
  `wikidump` longtext,
  `studienbereiche` text NOT NULL,
  `VeranstaltungsNummer` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`seminar_id`),
  KEY `heimat_inst_id` (`heimat_inst_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `archiv_user`
--

DROP TABLE IF EXISTS `archiv_user`;
CREATE TABLE IF NOT EXISTS `archiv_user` (
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `status` enum('user','autor','tutor','dozent') NOT NULL DEFAULT 'user',
  PRIMARY KEY (`seminar_id`,`user_id`),
  KEY `user_id` (`user_id`,`status`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `auth_extern`
--

DROP TABLE IF EXISTS `auth_extern`;
CREATE TABLE IF NOT EXISTS `auth_extern` (
  `studip_user_id` varchar(32) NOT NULL DEFAULT '',
  `external_user_id` varchar(32) NOT NULL DEFAULT '',
  `external_user_name` varchar(64) NOT NULL DEFAULT '',
  `external_user_password` varchar(32) NOT NULL DEFAULT '',
  `external_user_category` varchar(32) NOT NULL DEFAULT '',
  `external_user_system_type` varchar(32) NOT NULL DEFAULT '',
  `external_user_type` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`studip_user_id`,`external_user_system_type`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `auth_user_md5`
--

DROP TABLE IF EXISTS `auth_user_md5`;
CREATE TABLE IF NOT EXISTS `auth_user_md5` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `username` varchar(64) NOT NULL DEFAULT '',
  `password` varbinary(64) NOT NULL DEFAULT '',
  `perms` enum('user','autor','tutor','dozent','admin','root') NOT NULL DEFAULT 'user',
  `Vorname` varchar(64) DEFAULT NULL,
  `Nachname` varchar(64) DEFAULT NULL,
  `Email` varchar(64) DEFAULT NULL,
  `validation_key` varchar(10) NOT NULL DEFAULT '',
  `auth_plugin` varchar(64) DEFAULT 'standard',
  `locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `lock_comment` varchar(255) DEFAULT NULL,
  `locked_by` varchar(32) DEFAULT NULL,
  `visible` enum('global','always','yes','unknown','no','never') NOT NULL DEFAULT 'unknown',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `k_username` (`username`),
  KEY `perms` (`perms`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `auto_insert_sem`
--

DROP TABLE IF EXISTS `auto_insert_sem`;
CREATE TABLE IF NOT EXISTS `auto_insert_sem` (
  `seminar_id` char(32) NOT NULL,
  `status` enum('autor','tutor','dozent') NOT NULL DEFAULT 'autor',
  `domain_id` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`seminar_id`,`status`,`domain_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `auto_insert_user`
--

DROP TABLE IF EXISTS `auto_insert_user`;
CREATE TABLE IF NOT EXISTS `auto_insert_user` (
  `seminar_id` char(32) NOT NULL,
  `user_id` char(32) NOT NULL,
  `mkdate` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`seminar_id`,`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `aux_lock_rules`
--

DROP TABLE IF EXISTS `aux_lock_rules`;
CREATE TABLE IF NOT EXISTS `aux_lock_rules` (
  `lock_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `attributes` text NOT NULL,
  `sorting` text NOT NULL,
  PRIMARY KEY (`lock_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `banner_ads`
--

DROP TABLE IF EXISTS `banner_ads`;
CREATE TABLE IF NOT EXISTS `banner_ads` (
  `ad_id` varchar(32) NOT NULL DEFAULT '',
  `banner_path` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) DEFAULT NULL,
  `alttext` varchar(255) DEFAULT NULL,
  `target_type` enum('url','seminar','inst','user','none') NOT NULL DEFAULT 'url',
  `target` varchar(255) NOT NULL DEFAULT '',
  `startdate` int(20) NOT NULL DEFAULT '0',
  `enddate` int(20) NOT NULL DEFAULT '0',
  `priority` int(4) NOT NULL DEFAULT '0',
  `views` int(11) NOT NULL DEFAULT '0',
  `clicks` int(11) NOT NULL DEFAULT '0',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ad_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `blubber`
--

DROP TABLE IF EXISTS `blubber`;
CREATE TABLE IF NOT EXISTS `blubber` (
  `topic_id` varchar(32) NOT NULL DEFAULT '',
  `parent_id` varchar(32) NOT NULL DEFAULT '',
  `root_id` varchar(32) NOT NULL DEFAULT '',
  `context_type` enum('public','private','course') NOT NULL DEFAULT 'public',
  `name` varchar(255) DEFAULT NULL,
  `description` text,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `author_host` varchar(255) DEFAULT NULL,
  `Seminar_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `external_contact` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`topic_id`),
  KEY `parent_id` (`parent_id`),
  KEY `chdate` (`chdate`),
  KEY `mkdate` (`mkdate`),
  KEY `user_id` (`user_id`,`Seminar_id`),
  KEY `root_id` (`root_id`,`mkdate`),
  KEY `Seminar_id` (`Seminar_id`,`context_type`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `blubber_events_queue`
--

DROP TABLE IF EXISTS `blubber_events_queue`;
CREATE TABLE IF NOT EXISTS `blubber_events_queue` (
  `event_type` varchar(32) NOT NULL,
  `item_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL,
  PRIMARY KEY (`event_type`,`item_id`,`mkdate`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `blubber_external_contact`
--

DROP TABLE IF EXISTS `blubber_external_contact`;
CREATE TABLE IF NOT EXISTS `blubber_external_contact` (
  `external_contact_id` varchar(32) NOT NULL,
  `mail_identifier` varchar(256) DEFAULT NULL,
  `contact_type` varchar(16) NOT NULL DEFAULT 'anonymous',
  `name` varchar(256) NOT NULL,
  `data` text,
  `chdate` bigint(20) NOT NULL,
  `mkdate` bigint(20) NOT NULL,
  PRIMARY KEY (`external_contact_id`),
  KEY `mail_identifier` (`mail_identifier`),
  KEY `contact_type` (`contact_type`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `blubber_follower`
--

DROP TABLE IF EXISTS `blubber_follower`;
CREATE TABLE IF NOT EXISTS `blubber_follower` (
  `studip_user_id` varchar(32) NOT NULL,
  `external_contact_id` varchar(32) NOT NULL,
  `left_follows_right` tinyint(1) NOT NULL,
  KEY `studip_user_id` (`studip_user_id`),
  KEY `external_contact_id` (`external_contact_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `blubber_mentions`
--

DROP TABLE IF EXISTS `blubber_mentions`;
CREATE TABLE IF NOT EXISTS `blubber_mentions` (
  `topic_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `external_contact` tinyint(4) NOT NULL DEFAULT '0',
  `mkdate` int(11) NOT NULL,
  UNIQUE KEY `unique_users_per_topic` (`topic_id`,`user_id`,`external_contact`),
  KEY `topic_id` (`topic_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `blubber_reshares`
--

DROP TABLE IF EXISTS `blubber_reshares`;
CREATE TABLE IF NOT EXISTS `blubber_reshares` (
  `topic_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `external_contact` tinyint(4) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL,
  UNIQUE KEY `unique_reshares` (`topic_id`,`user_id`,`external_contact`),
  KEY `topic_id` (`topic_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `blubber_streams`
--

DROP TABLE IF EXISTS `blubber_streams`;
CREATE TABLE IF NOT EXISTS `blubber_streams` (
  `stream_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `name` varchar(32) NOT NULL,
  `sort` enum('activity','age') NOT NULL DEFAULT 'age',
  `defaultstream` tinyint(2) NOT NULL DEFAULT '0',
  `pool_courses` text,
  `pool_groups` text,
  `pool_hashtags` text,
  `filter_type` text,
  `filter_courses` text,
  `filter_groups` text,
  `filter_users` text,
  `filter_hashtags` text,
  `filter_nohashtags` text,
  `chdate` bigint(20) NOT NULL,
  `mkdate` bigint(20) NOT NULL,
  PRIMARY KEY (`stream_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `blubber_tags`
--

DROP TABLE IF EXISTS `blubber_tags`;
CREATE TABLE IF NOT EXISTS `blubber_tags` (
  `topic_id` varchar(32) NOT NULL,
  `tag` varchar(128) NOT NULL,
  PRIMARY KEY (`topic_id`,`tag`),
  KEY `tag` (`tag`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

DROP TABLE IF EXISTS `calendar_events`;
CREATE TABLE IF NOT EXISTS `calendar_events` (
  `event_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `autor_id` varchar(32) NOT NULL DEFAULT '',
  `editor_id` varchar(32) NOT NULL,
  `uid` varchar(255) NOT NULL DEFAULT '',
  `start` int(10) unsigned NOT NULL DEFAULT '0',
  `end` int(10) unsigned NOT NULL DEFAULT '0',
  `summary` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `class` enum('PUBLIC','PRIVATE','CONFIDENTIAL') NOT NULL DEFAULT 'PRIVATE',
  `categories` tinytext,
  `category_intern` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `location` tinytext,
  `ts` int(10) unsigned NOT NULL DEFAULT '0',
  `linterval` smallint(5) unsigned DEFAULT NULL,
  `sinterval` smallint(5) unsigned DEFAULT NULL,
  `wdays` varchar(7) DEFAULT NULL,
  `month` tinyint(3) unsigned DEFAULT NULL,
  `day` tinyint(3) unsigned DEFAULT NULL,
  `rtype` enum('SINGLE','DAILY','WEEKLY','MONTHLY','YEARLY') NOT NULL DEFAULT 'SINGLE',
  `duration` smallint(5) unsigned NOT NULL DEFAULT '0',
  `count` smallint(5) DEFAULT '0',
  `expire` int(10) unsigned NOT NULL DEFAULT '0',
  `exceptions` text,
  `mkdate` int(10) unsigned NOT NULL DEFAULT '0',
  `chdate` int(10) unsigned NOT NULL DEFAULT '0',
  `importdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`event_id`),
  UNIQUE KEY `uid_range` (`uid`,`range_id`),
  KEY `autor_id` (`autor_id`),
  KEY `range_id` (`range_id`,`class`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `comment_id` varchar(32) NOT NULL DEFAULT '',
  `object_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_id`),
  KEY `object_id` (`object_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `conditionaladmissions`
--

DROP TABLE IF EXISTS `conditionaladmissions`;
CREATE TABLE IF NOT EXISTS `conditionaladmissions` (
  `rule_id` varchar(32) NOT NULL,
  `message` text,
  `start_time` int(11) NOT NULL DEFAULT '0',
  `end_time` int(11) NOT NULL DEFAULT '0',
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `conditions_stopped` tinyint(1) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rule_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE IF NOT EXISTS `config` (
  `config_id` varchar(32) NOT NULL DEFAULT '',
  `parent_id` varchar(32) NOT NULL DEFAULT '',
  `field` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `is_default` tinyint(4) NOT NULL DEFAULT '0',
  `type` enum('boolean','integer','string','array') NOT NULL DEFAULT 'boolean',
  `range` enum('global','user') NOT NULL DEFAULT 'global',
  `section` varchar(255) NOT NULL DEFAULT '',
  `position` int(11) NOT NULL DEFAULT '0',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `message_template` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`config_id`),
  KEY `parent_id` (`parent_id`),
  KEY `field` (`field`,`range`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

DROP TABLE IF EXISTS `contact`;
CREATE TABLE IF NOT EXISTS `contact` (
  `contact_id` varchar(32) NOT NULL DEFAULT '',
  `owner_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `buddy` tinyint(4) NOT NULL DEFAULT '1',
  `calpermission` tinyint(2) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`contact_id`),
  KEY `owner_id` (`owner_id`,`buddy`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `contact_userinfo`
--

DROP TABLE IF EXISTS `contact_userinfo`;
CREATE TABLE IF NOT EXISTS `contact_userinfo` (
  `userinfo_id` varchar(32) NOT NULL DEFAULT '',
  `contact_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`userinfo_id`),
  KEY `contact_id` (`contact_id`),
  KEY `priority` (`priority`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `coursesets`
--

DROP TABLE IF EXISTS `coursesets`;
CREATE TABLE IF NOT EXISTS `coursesets` (
  `set_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `name` varchar(255) NOT NULL,
  `infotext` text NOT NULL,
  `algorithm` varchar(255) NOT NULL,
  `algorithm_run` tinyint(1) NOT NULL DEFAULT '0',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`set_id`),
  KEY `set_user` (`user_id`,`set_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `courseset_factorlist`
--

DROP TABLE IF EXISTS `courseset_factorlist`;
CREATE TABLE IF NOT EXISTS `courseset_factorlist` (
  `set_id` varchar(32) NOT NULL,
  `factorlist_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`set_id`,`factorlist_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `courseset_institute`
--

DROP TABLE IF EXISTS `courseset_institute`;
CREATE TABLE IF NOT EXISTS `courseset_institute` (
  `set_id` varchar(32) NOT NULL,
  `institute_id` varchar(32) NOT NULL,
  `mkdate` int(11) DEFAULT NULL,
  `chdate` int(11) DEFAULT NULL,
  PRIMARY KEY (`set_id`,`institute_id`),
  KEY `institute_id` (`institute_id`,`set_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `courseset_rule`
--

DROP TABLE IF EXISTS `courseset_rule`;
CREATE TABLE IF NOT EXISTS `courseset_rule` (
  `set_id` varchar(32) NOT NULL,
  `rule_id` varchar(32) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `mkdate` int(11) DEFAULT NULL,
  PRIMARY KEY (`set_id`,`rule_id`),
  KEY `type` (`set_id`,`type`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `cronjobs_logs`
--

DROP TABLE IF EXISTS `cronjobs_logs`;
CREATE TABLE IF NOT EXISTS `cronjobs_logs` (
  `log_id` char(32) NOT NULL DEFAULT '',
  `schedule_id` char(32) NOT NULL DEFAULT '',
  `scheduled` int(11) unsigned NOT NULL,
  `executed` int(11) unsigned NOT NULL,
  `exception` text,
  `output` text,
  `duration` float NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `schedule_id` (`schedule_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `cronjobs_schedules`
--

DROP TABLE IF EXISTS `cronjobs_schedules`;
CREATE TABLE IF NOT EXISTS `cronjobs_schedules` (
  `schedule_id` char(32) NOT NULL DEFAULT '',
  `task_id` char(32) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(4096) DEFAULT NULL,
  `parameters` text,
  `priority` enum('low','normal','high') NOT NULL DEFAULT 'normal',
  `type` enum('periodic','once') NOT NULL DEFAULT 'periodic',
  `minute` tinyint(2) DEFAULT NULL,
  `hour` tinyint(2) DEFAULT NULL,
  `day` tinyint(2) DEFAULT NULL,
  `month` tinyint(2) DEFAULT NULL,
  `day_of_week` tinyint(1) unsigned DEFAULT NULL,
  `next_execution` int(11) unsigned NOT NULL DEFAULT '0',
  `last_execution` int(11) unsigned DEFAULT NULL,
  `last_result` text,
  `execution_count` bigint(20) unsigned NOT NULL DEFAULT '0',
  `mkdate` int(11) unsigned NOT NULL,
  `chdate` int(11) unsigned NOT NULL,
  PRIMARY KEY (`schedule_id`),
  KEY `task_id` (`task_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `cronjobs_tasks`
--

DROP TABLE IF EXISTS `cronjobs_tasks`;
CREATE TABLE IF NOT EXISTS `cronjobs_tasks` (
  `task_id` char(32) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `execution_count` bigint(20) unsigned NOT NULL DEFAULT '0',
  `assigned_count` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `datafields`
--

DROP TABLE IF EXISTS `datafields`;
CREATE TABLE IF NOT EXISTS `datafields` (
  `datafield_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) DEFAULT NULL,
  `object_type` enum('sem','inst','user','userinstrole','usersemdata','roleinstdata') DEFAULT NULL,
  `object_class` varchar(10) DEFAULT NULL,
  `edit_perms` enum('user','autor','tutor','dozent','admin','root') DEFAULT NULL,
  `view_perms` enum('all','user','autor','tutor','dozent','admin','root') DEFAULT NULL,
  `priority` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `mkdate` int(20) unsigned DEFAULT NULL,
  `chdate` int(20) unsigned DEFAULT NULL,
  `type` enum('bool','textline','textarea','selectbox','date','time','email','phone','radio','combo','link') NOT NULL DEFAULT 'textline',
  `typeparam` text NOT NULL,
  `is_required` tinyint(4) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  PRIMARY KEY (`datafield_id`),
  KEY `object_type` (`object_type`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `datafields_entries`
--

DROP TABLE IF EXISTS `datafields_entries`;
CREATE TABLE IF NOT EXISTS `datafields_entries` (
  `datafield_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `content` text,
  `mkdate` int(20) unsigned DEFAULT NULL,
  `chdate` int(20) unsigned DEFAULT NULL,
  `sec_range_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`datafield_id`,`range_id`,`sec_range_id`),
  KEY `range_id` (`range_id`,`datafield_id`),
  KEY `datafield_id_2` (`datafield_id`,`sec_range_id`),
  KEY `datafields_contents` (`datafield_id`,`content`(32))
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `deputies`
--

DROP TABLE IF EXISTS `deputies`;
CREATE TABLE IF NOT EXISTS `deputies` (
  `range_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `gruppe` tinyint(4) NOT NULL DEFAULT '0',
  `notification` int(10) NOT NULL DEFAULT '0',
  `edit_about` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`range_id`,`user_id`),
  KEY `user_id` (`user_id`,`range_id`,`edit_about`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `dokumente`
--

DROP TABLE IF EXISTS `dokumente`;
CREATE TABLE IF NOT EXISTS `dokumente` (
  `dokument_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `seminar_id` varchar(32) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `filesize` int(20) NOT NULL DEFAULT '0',
  `autor_host` varchar(20) NOT NULL DEFAULT '',
  `downloads` int(20) NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `protected` tinyint(4) NOT NULL DEFAULT '0',
  `priority` smallint(5) unsigned NOT NULL DEFAULT '0',
  `author_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`dokument_id`),
  KEY `range_id` (`range_id`),
  KEY `seminar_id` (`seminar_id`),
  KEY `user_id` (`user_id`),
  KEY `chdate` (`chdate`),
  KEY `mkdate` (`mkdate`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `eval`
--

DROP TABLE IF EXISTS `eval`;
CREATE TABLE IF NOT EXISTS `eval` (
  `eval_id` varchar(32) NOT NULL DEFAULT '',
  `author_id` varchar(32) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `startdate` int(20) DEFAULT NULL,
  `stopdate` int(20) DEFAULT NULL,
  `timespan` int(20) DEFAULT NULL,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `anonymous` tinyint(1) NOT NULL DEFAULT '1',
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `shared` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`eval_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `evalanswer`
--

DROP TABLE IF EXISTS `evalanswer`;
CREATE TABLE IF NOT EXISTS `evalanswer` (
  `evalanswer_id` varchar(32) NOT NULL DEFAULT '',
  `parent_id` varchar(32) NOT NULL DEFAULT '',
  `position` int(11) NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `rows` tinyint(4) NOT NULL DEFAULT '0',
  `counter` int(11) NOT NULL DEFAULT '0',
  `residual` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`evalanswer_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `evalanswer_user`
--

DROP TABLE IF EXISTS `evalanswer_user`;
CREATE TABLE IF NOT EXISTS `evalanswer_user` (
  `evalanswer_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`evalanswer_id`,`user_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `evalgroup`
--

DROP TABLE IF EXISTS `evalgroup`;
CREATE TABLE IF NOT EXISTS `evalgroup` (
  `evalgroup_id` varchar(32) NOT NULL DEFAULT '',
  `parent_id` varchar(32) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  `child_type` enum('EvaluationGroup','EvaluationQuestion') NOT NULL DEFAULT 'EvaluationGroup',
  `mandatory` tinyint(1) NOT NULL DEFAULT '0',
  `template_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`evalgroup_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `evalquestion`
--

DROP TABLE IF EXISTS `evalquestion`;
CREATE TABLE IF NOT EXISTS `evalquestion` (
  `evalquestion_id` varchar(32) NOT NULL DEFAULT '',
  `parent_id` varchar(32) NOT NULL DEFAULT '',
  `type` enum('likertskala','multiplechoice','polskala') NOT NULL DEFAULT 'multiplechoice',
  `position` int(11) NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `multiplechoice` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`evalquestion_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `eval_group_template`
--

DROP TABLE IF EXISTS `eval_group_template`;
CREATE TABLE IF NOT EXISTS `eval_group_template` (
  `evalgroup_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `group_type` varchar(250) NOT NULL DEFAULT 'normal',
  PRIMARY KEY (`evalgroup_id`,`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `eval_range`
--

DROP TABLE IF EXISTS `eval_range`;
CREATE TABLE IF NOT EXISTS `eval_range` (
  `eval_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`eval_id`,`range_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `eval_templates`
--

DROP TABLE IF EXISTS `eval_templates`;
CREATE TABLE IF NOT EXISTS `eval_templates` (
  `template_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) DEFAULT NULL,
  `institution_id` varchar(32) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `show_questions` tinyint(1) NOT NULL DEFAULT '1',
  `show_total_stats` tinyint(1) NOT NULL DEFAULT '1',
  `show_graphics` tinyint(1) NOT NULL DEFAULT '1',
  `show_questionblock_headline` tinyint(1) NOT NULL DEFAULT '1',
  `show_group_headline` tinyint(1) NOT NULL DEFAULT '1',
  `polscale_gfx_type` varchar(255) NOT NULL DEFAULT 'bars',
  `likertscale_gfx_type` varchar(255) NOT NULL DEFAULT 'bars',
  `mchoice_scale_gfx_type` varchar(255) NOT NULL DEFAULT 'bars',
  `kurzbeschreibung` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`template_id`),
  KEY `user_id` (`user_id`,`institution_id`,`name`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `eval_templates_eval`
--

DROP TABLE IF EXISTS `eval_templates_eval`;
CREATE TABLE IF NOT EXISTS `eval_templates_eval` (
  `eval_id` varchar(32) NOT NULL DEFAULT '',
  `template_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`eval_id`),
  KEY `eval_id` (`eval_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `eval_templates_user`
--

DROP TABLE IF EXISTS `eval_templates_user`;
CREATE TABLE IF NOT EXISTS `eval_templates_user` (
  `eval_id` varchar(32) NOT NULL DEFAULT '',
  `template_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  KEY `eval_id` (`eval_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `eval_user`
--

DROP TABLE IF EXISTS `eval_user`;
CREATE TABLE IF NOT EXISTS `eval_user` (
  `eval_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`eval_id`,`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `extern_config`
--

DROP TABLE IF EXISTS `extern_config`;
CREATE TABLE IF NOT EXISTS `extern_config` (
  `config_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `config_type` int(4) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `is_standard` int(4) NOT NULL DEFAULT '0',
  `config` mediumtext NOT NULL,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`config_id`,`range_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `ex_termine`
--

DROP TABLE IF EXISTS `ex_termine`;
CREATE TABLE IF NOT EXISTS `ex_termine` (
  `termin_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `autor_id` varchar(32) NOT NULL DEFAULT '',
  `content` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `date` int(20) NOT NULL DEFAULT '0',
  `end_time` int(20) NOT NULL DEFAULT '0',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `date_typ` tinyint(4) NOT NULL DEFAULT '0',
  `topic_id` varchar(32) DEFAULT NULL,
  `raum` varchar(255) DEFAULT NULL,
  `metadate_id` varchar(32) DEFAULT NULL,
  `resource_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`termin_id`),
  KEY `range_id` (`range_id`,`date`),
  KEY `metadate_id` (`metadate_id`,`date`),
  KEY `autor_id` (`autor_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `folder`
--

DROP TABLE IF EXISTS `folder`;
CREATE TABLE IF NOT EXISTS `folder` (
  `folder_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `seminar_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `permission` tinyint(3) unsigned NOT NULL DEFAULT '7',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `priority` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`folder_id`),
  KEY `user_id` (`user_id`),
  KEY `range_id` (`range_id`),
  KEY `chdate` (`chdate`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `forum_abo_users`
--

DROP TABLE IF EXISTS `forum_abo_users`;
CREATE TABLE IF NOT EXISTS `forum_abo_users` (
  `topic_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  PRIMARY KEY (`topic_id`,`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `forum_categories`
--

DROP TABLE IF EXISTS `forum_categories`;
CREATE TABLE IF NOT EXISTS `forum_categories` (
  `category_id` varchar(32) NOT NULL,
  `seminar_id` varchar(32) NOT NULL,
  `entry_name` varchar(255) NOT NULL,
  `pos` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`category_id`),
  KEY `seminar_id` (`seminar_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `forum_categories_entries`
--

DROP TABLE IF EXISTS `forum_categories_entries`;
CREATE TABLE IF NOT EXISTS `forum_categories_entries` (
  `category_id` varchar(32) NOT NULL,
  `topic_id` varchar(32) NOT NULL,
  `pos` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`category_id`,`topic_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `forum_entries`
--

DROP TABLE IF EXISTS `forum_entries`;
CREATE TABLE IF NOT EXISTS `forum_entries` (
  `topic_id` varchar(32) NOT NULL,
  `seminar_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `area` tinyint(4) NOT NULL DEFAULT '0',
  `mkdate` int(20) NOT NULL,
  `latest_chdate` int(11) DEFAULT NULL,
  `chdate` int(20) NOT NULL,
  `author` varchar(255) NOT NULL,
  `author_host` varchar(255) NOT NULL,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `depth` int(11) NOT NULL,
  `anonymous` tinyint(4) NOT NULL DEFAULT '0',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `sticky` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`topic_id`),
  KEY `seminar_id` (`seminar_id`,`lft`),
  KEY `seminar_id_2` (`seminar_id`,`rgt`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `forum_entries_issues`
--

DROP TABLE IF EXISTS `forum_entries_issues`;
CREATE TABLE IF NOT EXISTS `forum_entries_issues` (
  `topic_id` varchar(32) NOT NULL,
  `issue_id` varchar(32) NOT NULL,
  PRIMARY KEY (`topic_id`,`issue_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `forum_favorites`
--

DROP TABLE IF EXISTS `forum_favorites`;
CREATE TABLE IF NOT EXISTS `forum_favorites` (
  `user_id` varchar(32) NOT NULL,
  `topic_id` varchar(32) NOT NULL,
  PRIMARY KEY (`user_id`,`topic_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `forum_likes`
--

DROP TABLE IF EXISTS `forum_likes`;
CREATE TABLE IF NOT EXISTS `forum_likes` (
  `topic_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  PRIMARY KEY (`topic_id`,`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `forum_visits`
--

DROP TABLE IF EXISTS `forum_visits`;
CREATE TABLE IF NOT EXISTS `forum_visits` (
  `user_id` varchar(32) NOT NULL,
  `seminar_id` varchar(32) NOT NULL,
  `visitdate` int(11) NOT NULL,
  `last_visitdate` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`seminar_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `his_abschl`
--

DROP TABLE IF EXISTS `his_abschl`;
CREATE TABLE IF NOT EXISTS `his_abschl` (
  `abint` char(2) NOT NULL DEFAULT '',
  `aikz` char(1) DEFAULT NULL,
  `ktxt` char(10) DEFAULT NULL,
  `dtxt` char(25) DEFAULT NULL,
  `ltxt` char(100) DEFAULT NULL,
  `astat` char(2) DEFAULT NULL,
  `hrst` char(10) DEFAULT NULL,
  `part` char(2) DEFAULT NULL,
  `anzstg` smallint(6) DEFAULT NULL,
  `kzfaarray` char(10) DEFAULT NULL,
  `mag_laa` char(1) DEFAULT NULL,
  `sortkz1` char(2) DEFAULT NULL,
  `anzstgmin` smallint(6) DEFAULT NULL,
  `sprache` char(3) DEFAULT NULL,
  `refabint` char(2) DEFAULT NULL,
  `efh` char(4) DEFAULT NULL,
  PRIMARY KEY (`abint`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `his_abstgv`
--

DROP TABLE IF EXISTS `his_abstgv`;
CREATE TABLE IF NOT EXISTS `his_abstgv` (
  `ktxt` varchar(50) DEFAULT NULL,
  `dtxt` varchar(50) DEFAULT NULL,
  `ltxt` varchar(100) DEFAULT NULL,
  `fb` char(2) DEFAULT NULL,
  `kzfa` char(1) NOT NULL DEFAULT '',
  `kzfaarray` char(3) DEFAULT NULL,
  `abschl` char(2) NOT NULL DEFAULT '',
  `stg` char(3) NOT NULL DEFAULT '',
  `pversion` int(11) NOT NULL DEFAULT '0',
  `regelstz` tinyint(2) DEFAULT NULL,
  `login_part` char(2) DEFAULT NULL,
  `studip_studiengang` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`abschl`,`stg`,`kzfa`,`pversion`),
  KEY `studip_studiengang` (`studip_studiengang`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `his_pvers`
--

DROP TABLE IF EXISTS `his_pvers`;
CREATE TABLE IF NOT EXISTS `his_pvers` (
  `pvers` smallint(6) NOT NULL DEFAULT '0',
  `aikz` char(1) DEFAULT NULL,
  `ktxt` char(10) DEFAULT NULL,
  `dtxt` char(25) DEFAULT NULL,
  `ltxt` char(50) DEFAULT NULL,
  `sprache` char(3) DEFAULT NULL,
  `refpvers` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`pvers`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `his_stg`
--

DROP TABLE IF EXISTS `his_stg`;
CREATE TABLE IF NOT EXISTS `his_stg` (
  `stg` char(3) NOT NULL DEFAULT '',
  `ktxt` varchar(10) DEFAULT NULL,
  `dtxt` varchar(25) DEFAULT NULL,
  `ltxt` varchar(100) DEFAULT NULL,
  `fb` char(2) DEFAULT NULL,
  PRIMARY KEY (`stg`)
) ENGINE=MyISAM COMMENT='Studienfaecher aus der HIS DB';

-- --------------------------------------------------------

--
-- Table structure for table `Institute`
--

DROP TABLE IF EXISTS `Institute`;
CREATE TABLE IF NOT EXISTS `Institute` (
  `Institut_id` varchar(32) NOT NULL DEFAULT '',
  `Name` varchar(255) NOT NULL DEFAULT '',
  `fakultaets_id` varchar(32) NOT NULL DEFAULT '',
  `Strasse` varchar(255) NOT NULL DEFAULT '',
  `Plz` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT 'http://www.studip.de',
  `telefon` varchar(32) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `fax` varchar(32) NOT NULL DEFAULT '',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `modules` int(10) unsigned DEFAULT NULL,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `lit_plugin_name` varchar(255) DEFAULT NULL,
  `srienabled` tinyint(4) NOT NULL DEFAULT '0',
  `lock_rule` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`Institut_id`),
  KEY `fakultaets_id` (`fakultaets_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `kategorien`
--

DROP TABLE IF EXISTS `kategorien`;
CREATE TABLE IF NOT EXISTS `kategorien` (
  `kategorie_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`kategorie_id`),
  KEY `priority` (`priority`),
  KEY `range_id` (`range_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `limitedadmissions`
--

DROP TABLE IF EXISTS `limitedadmissions`;
CREATE TABLE IF NOT EXISTS `limitedadmissions` (
  `rule_id` varchar(32) NOT NULL,
  `message` text NOT NULL,
  `start_time` int(11) NOT NULL DEFAULT '0',
  `end_time` int(11) NOT NULL DEFAULT '0',
  `maxnumber` int(11) NOT NULL DEFAULT '0',
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rule_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `lit_catalog`
--

DROP TABLE IF EXISTS `lit_catalog`;
CREATE TABLE IF NOT EXISTS `lit_catalog` (
  `catalog_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  `lit_plugin` varchar(100) NOT NULL DEFAULT 'Studip',
  `accession_number` varchar(100) DEFAULT NULL,
  `dc_title` varchar(255) NOT NULL DEFAULT '',
  `dc_creator` varchar(255) NOT NULL DEFAULT '',
  `dc_subject` varchar(255) DEFAULT NULL,
  `dc_description` text,
  `dc_publisher` varchar(255) DEFAULT NULL,
  `dc_contributor` varchar(255) DEFAULT NULL,
  `dc_date` date DEFAULT NULL,
  `dc_type` varchar(100) DEFAULT NULL,
  `dc_format` varchar(100) DEFAULT NULL,
  `dc_identifier` varchar(255) DEFAULT NULL,
  `dc_source` varchar(255) DEFAULT NULL,
  `dc_language` varchar(10) DEFAULT NULL,
  `dc_relation` varchar(255) DEFAULT NULL,
  `dc_coverage` varchar(255) DEFAULT NULL,
  `dc_rights` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`catalog_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `lit_list`
--

DROP TABLE IF EXISTS `lit_list`;
CREATE TABLE IF NOT EXISTS `lit_list` (
  `list_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `format` varchar(255) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  `priority` smallint(6) NOT NULL DEFAULT '0',
  `visibility` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`list_id`),
  KEY `range_id` (`range_id`),
  KEY `priority` (`priority`),
  KEY `visibility` (`visibility`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `lit_list_content`
--

DROP TABLE IF EXISTS `lit_list_content`;
CREATE TABLE IF NOT EXISTS `lit_list_content` (
  `list_element_id` varchar(32) NOT NULL DEFAULT '',
  `list_id` varchar(32) NOT NULL DEFAULT '',
  `catalog_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  `note` text,
  `priority` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`list_element_id`),
  KEY `list_id` (`list_id`),
  KEY `catalog_id` (`catalog_id`),
  KEY `priority` (`priority`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `lockedadmissions`
--

DROP TABLE IF EXISTS `lockedadmissions`;
CREATE TABLE IF NOT EXISTS `lockedadmissions` (
  `rule_id` varchar(32) NOT NULL,
  `message` text NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rule_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `lock_rules`
--

DROP TABLE IF EXISTS `lock_rules`;
CREATE TABLE IF NOT EXISTS `lock_rules` (
  `lock_id` varchar(32) NOT NULL DEFAULT '',
  `permission` enum('autor','tutor','dozent','admin','root') NOT NULL DEFAULT 'dozent',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `attributes` text NOT NULL,
  `object_type` enum('sem','inst','user') NOT NULL DEFAULT 'sem',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`lock_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `log_actions`
--

DROP TABLE IF EXISTS `log_actions`;
CREATE TABLE IF NOT EXISTS `log_actions` (
  `action_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT '',
  `description` varchar(64) DEFAULT NULL,
  `info_template` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `expires` int(20) NOT NULL DEFAULT '0',
  `filename` varchar(255) DEFAULT NULL,
  `class` varchar(255) DEFAULT NULL,
  `type` enum('core','plugin','file') DEFAULT NULL,
  PRIMARY KEY (`action_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `log_events`
--

DROP TABLE IF EXISTS `log_events`;
CREATE TABLE IF NOT EXISTS `log_events` (
  `event_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `action_id` varchar(32) NOT NULL DEFAULT '',
  `affected_range_id` varchar(32) DEFAULT NULL,
  `coaffected_range_id` varchar(32) DEFAULT NULL,
  `info` text,
  `dbg_info` text,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`event_id`),
  KEY `action_id` (`action_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `mail_queue_entries`
--

DROP TABLE IF EXISTS `mail_queue_entries`;
CREATE TABLE IF NOT EXISTS `mail_queue_entries` (
  `mail_queue_id` varchar(32) NOT NULL,
  `mail` text NOT NULL,
  `message_id` varchar(32) DEFAULT NULL,
  `user_id` varchar(32) DEFAULT NULL,
  `tries` int(11) NOT NULL,
  `last_try` int(11) NOT NULL DEFAULT '0',
  `mkdate` bigint(20) NOT NULL,
  `chdate` bigint(20) NOT NULL,
  PRIMARY KEY (`mail_queue_id`),
  KEY `message_id` (`message_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `media_cache`
--

DROP TABLE IF EXISTS `media_cache`;
CREATE TABLE IF NOT EXISTS `media_cache` (
  `id` varchar(32) NOT NULL,
  `type` varchar(64) NOT NULL,
  `chdate` timestamp NOT NULL,
  `expires` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
CREATE TABLE IF NOT EXISTS `message` (
  `message_id` varchar(32) NOT NULL DEFAULT '',
  `chat_id` varchar(32) DEFAULT NULL,
  `autor_id` varchar(32) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `readed` tinyint(1) NOT NULL DEFAULT '0',
  `reading_confirmation` tinyint(1) NOT NULL DEFAULT '0',
  `priority` enum('normal','high') NOT NULL DEFAULT 'normal',
  PRIMARY KEY (`message_id`),
  KEY `chat_id` (`chat_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `message_user`
--

DROP TABLE IF EXISTS `message_user`;
CREATE TABLE IF NOT EXISTS `message_user` (
  `user_id` char(32) NOT NULL DEFAULT '',
  `message_id` char(32) NOT NULL DEFAULT '',
  `readed` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `snd_rec` enum('rec','snd') NOT NULL DEFAULT 'rec',
  `folder` tinyint(4) NOT NULL DEFAULT '0',
  `confirmed_read` tinyint(1) NOT NULL DEFAULT '0',
  `answered` tinyint(1) NOT NULL DEFAULT '0',
  `mkdate` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`message_id`,`snd_rec`,`user_id`),
  KEY `user_id` (`user_id`,`snd_rec`,`deleted`,`readed`,`mkdate`),
  KEY `user_id_2` (`user_id`,`snd_rec`,`deleted`,`folder`,`mkdate`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
CREATE TABLE IF NOT EXISTS `news` (
  `news_id` varchar(32) NOT NULL DEFAULT '',
  `topic` varchar(255) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `author` varchar(255) NOT NULL DEFAULT '',
  `date` int(11) NOT NULL DEFAULT '0',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `expire` int(11) NOT NULL DEFAULT '0',
  `allow_comments` tinyint(1) NOT NULL DEFAULT '0',
  `chdate` int(10) unsigned NOT NULL DEFAULT '0',
  `chdate_uid` varchar(32) NOT NULL DEFAULT '',
  `mkdate` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`news_id`),
  KEY `date` (`date`),
  KEY `chdate` (`chdate`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `news_range`
--

DROP TABLE IF EXISTS `news_range`;
CREATE TABLE IF NOT EXISTS `news_range` (
  `news_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`news_id`,`range_id`),
  KEY `range_id` (`range_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `news_rss_range`
--

DROP TABLE IF EXISTS `news_rss_range`;
CREATE TABLE IF NOT EXISTS `news_rss_range` (
  `range_id` char(32) NOT NULL DEFAULT '',
  `rss_id` char(32) NOT NULL DEFAULT '',
  `range_type` enum('user','sem','inst','global') NOT NULL DEFAULT 'user',
  PRIMARY KEY (`range_id`),
  KEY `rss_id` (`rss_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_consumer_registry`
--

DROP TABLE IF EXISTS `oauth_consumer_registry`;
CREATE TABLE IF NOT EXISTS `oauth_consumer_registry` (
  `ocr_id` int(11) NOT NULL AUTO_INCREMENT,
  `ocr_usa_id_ref` int(11) DEFAULT NULL,
  `ocr_consumer_key` varchar(128) binary NOT NULL,
  `ocr_consumer_secret` varchar(128) binary NOT NULL,
  `ocr_signature_methods` varchar(128) NOT NULL DEFAULT 'HMAC-SHA1,PLAINTEXT',
  `ocr_server_uri` varchar(128) NOT NULL,
  `ocr_server_uri_host` varchar(128) NOT NULL,
  `ocr_server_uri_path` varchar(128) binary NOT NULL,
  `ocr_request_token_uri` varchar(255) NOT NULL,
  `ocr_authorize_uri` varchar(255) NOT NULL,
  `ocr_access_token_uri` varchar(255) NOT NULL,
  `ocr_timestamp` timestamp NOT NULL,
  PRIMARY KEY (`ocr_id`),
  UNIQUE KEY `ocr_consumer_key` (`ocr_consumer_key`,`ocr_usa_id_ref`,`ocr_server_uri`),
  KEY `ocr_server_uri` (`ocr_server_uri`),
  KEY `ocr_server_uri_host` (`ocr_server_uri_host`,`ocr_server_uri_path`),
  KEY `ocr_usa_id_ref` (`ocr_usa_id_ref`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_consumer_token`
--

DROP TABLE IF EXISTS `oauth_consumer_token`;
CREATE TABLE IF NOT EXISTS `oauth_consumer_token` (
  `oct_id` int(11) NOT NULL AUTO_INCREMENT,
  `oct_ocr_id_ref` int(11) NOT NULL,
  `oct_usa_id_ref` int(11) NOT NULL,
  `oct_name` varchar(64) binary NOT NULL DEFAULT '',
  `oct_token` varchar(128) binary NOT NULL,
  `oct_token_secret` varchar(128) binary NOT NULL,
  `oct_token_type` enum('request','authorized','access') DEFAULT NULL,
  `oct_token_ttl` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `oct_timestamp` timestamp NOT NULL,
  PRIMARY KEY (`oct_id`),
  UNIQUE KEY `oct_ocr_id_ref` (`oct_ocr_id_ref`,`oct_token`),
  UNIQUE KEY `oct_usa_id_ref` (`oct_usa_id_ref`,`oct_ocr_id_ref`,`oct_token_type`,`oct_name`),
  KEY `oct_token_ttl` (`oct_token_ttl`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_log`
--

DROP TABLE IF EXISTS `oauth_log`;
CREATE TABLE IF NOT EXISTS `oauth_log` (
  `olg_id` int(11) NOT NULL AUTO_INCREMENT,
  `olg_osr_consumer_key` varchar(64) binary DEFAULT NULL,
  `olg_ost_token` varchar(64) binary DEFAULT NULL,
  `olg_ocr_consumer_key` varchar(64) binary DEFAULT NULL,
  `olg_oct_token` varchar(64) binary DEFAULT NULL,
  `olg_usa_id_ref` int(11) DEFAULT NULL,
  `olg_received` text NOT NULL,
  `olg_sent` text NOT NULL,
  `olg_base_string` text NOT NULL,
  `olg_notes` text NOT NULL,
  `olg_timestamp` timestamp NOT NULL,
  `olg_remote_ip` bigint(20) NOT NULL,
  PRIMARY KEY (`olg_id`),
  KEY `olg_osr_consumer_key` (`olg_osr_consumer_key`,`olg_id`),
  KEY `olg_ost_token` (`olg_ost_token`,`olg_id`),
  KEY `olg_ocr_consumer_key` (`olg_ocr_consumer_key`,`olg_id`),
  KEY `olg_oct_token` (`olg_oct_token`,`olg_id`),
  KEY `olg_usa_id_ref` (`olg_usa_id_ref`,`olg_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_server_nonce`
--

DROP TABLE IF EXISTS `oauth_server_nonce`;
CREATE TABLE IF NOT EXISTS `oauth_server_nonce` (
  `osn_id` int(11) NOT NULL AUTO_INCREMENT,
  `osn_consumer_key` varchar(64) binary NOT NULL,
  `osn_token` varchar(64) binary NOT NULL,
  `osn_timestamp` bigint(20) NOT NULL,
  `osn_nonce` varchar(80) binary NOT NULL,
  PRIMARY KEY (`osn_id`),
  UNIQUE KEY `osn_consumer_key` (`osn_consumer_key`,`osn_token`,`osn_timestamp`,`osn_nonce`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_server_registry`
--

DROP TABLE IF EXISTS `oauth_server_registry`;
CREATE TABLE IF NOT EXISTS `oauth_server_registry` (
  `osr_id` int(11) NOT NULL AUTO_INCREMENT,
  `osr_usa_id_ref` int(11) DEFAULT NULL,
  `osr_consumer_key` varchar(64) binary NOT NULL,
  `osr_consumer_secret` varchar(64) binary NOT NULL,
  `osr_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `osr_status` varchar(16) NOT NULL,
  `osr_requester_name` varchar(64) NOT NULL,
  `osr_requester_email` varchar(64) NOT NULL,
  `osr_callback_uri` varchar(255) NOT NULL,
  `osr_application_uri` varchar(255) NOT NULL,
  `osr_application_title` varchar(80) NOT NULL,
  `osr_application_descr` text NOT NULL,
  `osr_application_notes` text NOT NULL,
  `osr_application_type` varchar(20) NOT NULL,
  `osr_application_commercial` tinyint(1) NOT NULL DEFAULT '0',
  `osr_issue_date` datetime NOT NULL,
  `osr_timestamp` timestamp NOT NULL,
  PRIMARY KEY (`osr_id`),
  UNIQUE KEY `osr_consumer_key` (`osr_consumer_key`),
  KEY `osr_usa_id_ref` (`osr_usa_id_ref`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_server_token`
--

DROP TABLE IF EXISTS `oauth_server_token`;
CREATE TABLE IF NOT EXISTS `oauth_server_token` (
  `ost_id` int(11) NOT NULL AUTO_INCREMENT,
  `ost_osr_id_ref` int(11) NOT NULL,
  `ost_usa_id_ref` int(11) NOT NULL,
  `ost_token` varchar(64) binary NOT NULL,
  `ost_token_secret` varchar(64) binary NOT NULL,
  `ost_token_type` enum('request','access') DEFAULT NULL,
  `ost_authorized` tinyint(1) NOT NULL DEFAULT '0',
  `ost_referrer_host` varchar(128) NOT NULL DEFAULT '',
  `ost_token_ttl` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `ost_timestamp` timestamp NOT NULL,
  `ost_verifier` char(10) DEFAULT NULL,
  `ost_callback_url` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`ost_id`),
  UNIQUE KEY `ost_token` (`ost_token`),
  KEY `ost_osr_id_ref` (`ost_osr_id_ref`),
  KEY `ost_token_ttl` (`ost_token_ttl`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `object_contentmodules`
--

DROP TABLE IF EXISTS `object_contentmodules`;
CREATE TABLE IF NOT EXISTS `object_contentmodules` (
  `object_id` varchar(32) NOT NULL DEFAULT '',
  `module_id` varchar(255) NOT NULL DEFAULT '',
  `system_type` varchar(32) NOT NULL DEFAULT '',
  `module_type` varchar(32) NOT NULL DEFAULT '',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`module_id`,`system_type`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `object_rate`
--

DROP TABLE IF EXISTS `object_rate`;
CREATE TABLE IF NOT EXISTS `object_rate` (
  `object_id` varchar(32) NOT NULL DEFAULT '',
  `rate` int(10) NOT NULL DEFAULT '0',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  KEY `object_id` (`object_id`),
  KEY `rate` (`rate`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `object_user`
--

DROP TABLE IF EXISTS `object_user`;
CREATE TABLE IF NOT EXISTS `object_user` (
  `object_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `flag` varchar(32) NOT NULL DEFAULT '',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`user_id`,`flag`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `object_user_visits`
--

DROP TABLE IF EXISTS `object_user_visits`;
CREATE TABLE IF NOT EXISTS `object_user_visits` (
  `object_id` char(32) NOT NULL DEFAULT '',
  `user_id` char(32) NOT NULL DEFAULT '',
  `type` enum('vote','documents','forum','literature','schedule','scm','sem','wiki','news','eval','inst','ilias_connect','elearning_interface','participants') NOT NULL DEFAULT 'vote',
  `visitdate` int(20) NOT NULL DEFAULT '0',
  `last_visitdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`user_id`,`type`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `object_views`
--

DROP TABLE IF EXISTS `object_views`;
CREATE TABLE IF NOT EXISTS `object_views` (
  `object_id` varchar(32) NOT NULL DEFAULT '',
  `views` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`),
  KEY `views` (`views`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `opengraphdata`
--

DROP TABLE IF EXISTS `opengraphdata`;
CREATE TABLE IF NOT EXISTS `opengraphdata` (
  `url` varchar(1000) NOT NULL,
  `is_opengraph` tinyint(2) DEFAULT NULL,
  `title` text,
  `image` varchar(1024) DEFAULT NULL,
  `description` text,
  `type` varchar(64) DEFAULT NULL,
  `data` text NOT NULL,
  `last_update` bigint(20) NOT NULL,
  `chdate` bigint(20) NOT NULL,
  `mkdate` bigint(20) NOT NULL,
  PRIMARY KEY (`url`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `participantrestrictedadmissions`
--

DROP TABLE IF EXISTS `participantrestrictedadmissions`;
CREATE TABLE IF NOT EXISTS `participantrestrictedadmissions` (
  `rule_id` varchar(32) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `distribution_time` int(11) NOT NULL DEFAULT '0',
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rule_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `passwordadmissions`
--

DROP TABLE IF EXISTS `passwordadmissions`;
CREATE TABLE IF NOT EXISTS `passwordadmissions` (
  `rule_id` varchar(32) NOT NULL,
  `message` text,
  `start_time` int(11) NOT NULL DEFAULT '0',
  `end_time` int(11) NOT NULL DEFAULT '0',
  `password` varchar(255) DEFAULT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rule_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `personal_notifications`
--

DROP TABLE IF EXISTS `personal_notifications`;
CREATE TABLE IF NOT EXISTS `personal_notifications` (
  `personal_notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(512) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `avatar` varchar(256) NOT NULL DEFAULT '',
  `html_id` varchar(64) NOT NULL DEFAULT '',
  `mkdate` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`personal_notification_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `personal_notifications_user`
--

DROP TABLE IF EXISTS `personal_notifications_user`;
CREATE TABLE IF NOT EXISTS `personal_notifications_user` (
  `personal_notification_id` int(10) unsigned NOT NULL,
  `user_id` binary(32) NOT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`personal_notification_id`,`user_id`),
  KEY `user_id` (`user_id`,`seen`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `plugins`
--

DROP TABLE IF EXISTS `plugins`;
CREATE TABLE IF NOT EXISTS `plugins` (
  `pluginid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pluginclassname` varchar(255) NOT NULL DEFAULT '',
  `pluginpath` varchar(255) NOT NULL DEFAULT '',
  `pluginname` varchar(45) NOT NULL DEFAULT '',
  `plugintype` text NOT NULL,
  `enabled` enum('yes','no') NOT NULL DEFAULT 'no',
  `navigationpos` int(10) unsigned NOT NULL DEFAULT '0',
  `dependentonid` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`pluginid`)
) ENGINE=MyISAM ;

-- --------------------------------------------------------

--
-- Table structure for table `plugins_activated`
--

DROP TABLE IF EXISTS `plugins_activated`;
CREATE TABLE IF NOT EXISTS `plugins_activated` (
  `pluginid` int(10) unsigned NOT NULL DEFAULT '0',
  `poiid` varchar(36) NOT NULL DEFAULT '',
  `state` enum('on','off') NOT NULL DEFAULT 'on',
  PRIMARY KEY (`pluginid`,`poiid`),
  UNIQUE KEY `poiid` (`poiid`,`pluginid`,`state`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `plugins_default_activations`
--

DROP TABLE IF EXISTS `plugins_default_activations`;
CREATE TABLE IF NOT EXISTS `plugins_default_activations` (
  `pluginid` int(10) unsigned NOT NULL DEFAULT '0',
  `institutid` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`pluginid`,`institutid`)
) ENGINE=MyISAM COMMENT='default activations of standard plugins';

-- --------------------------------------------------------

--
-- Table structure for table `priorities`
--

DROP TABLE IF EXISTS `priorities`;
CREATE TABLE IF NOT EXISTS `priorities` (
  `user_id` varchar(32) NOT NULL,
  `set_id` varchar(32) NOT NULL,
  `seminar_id` varchar(32) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`set_id`,`seminar_id`),
  KEY `user_rule_priority` (`user_id`,`priority`,`set_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `px_topics`
--

DROP TABLE IF EXISTS `px_topics`;
CREATE TABLE IF NOT EXISTS `px_topics` (
  `topic_id` varchar(32) NOT NULL DEFAULT '',
  `parent_id` varchar(32) NOT NULL DEFAULT '',
  `root_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) DEFAULT NULL,
  `description` text,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `author` varchar(255) DEFAULT NULL,
  `author_host` varchar(255) DEFAULT NULL,
  `Seminar_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `anonymous` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`topic_id`),
  KEY `root_id` (`root_id`),
  KEY `Seminar_id` (`Seminar_id`),
  KEY `parent_id` (`parent_id`),
  KEY `chdate` (`chdate`),
  KEY `mkdate` (`mkdate`),
  KEY `user_id` (`user_id`,`Seminar_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `range_tree`
--

DROP TABLE IF EXISTS `range_tree`;
CREATE TABLE IF NOT EXISTS `range_tree` (
  `item_id` varchar(32) NOT NULL DEFAULT '',
  `parent_id` varchar(32) NOT NULL DEFAULT '',
  `level` int(11) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `studip_object` varchar(10) DEFAULT NULL,
  `studip_object_id` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `parent_id` (`parent_id`),
  KEY `priority` (`priority`),
  KEY `studip_object_id` (`studip_object_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_assign`
--

DROP TABLE IF EXISTS `resources_assign`;
CREATE TABLE IF NOT EXISTS `resources_assign` (
  `assign_id` varchar(32) NOT NULL DEFAULT '',
  `resource_id` varchar(32) NOT NULL DEFAULT '',
  `assign_user_id` varchar(32) DEFAULT NULL,
  `user_free_name` varchar(255) DEFAULT NULL,
  `begin` int(20) NOT NULL DEFAULT '0',
  `end` int(20) NOT NULL DEFAULT '0',
  `repeat_end` int(20) DEFAULT NULL,
  `repeat_quantity` int(2) DEFAULT NULL,
  `repeat_interval` int(2) DEFAULT NULL,
  `repeat_month_of_year` int(2) DEFAULT NULL,
  `repeat_day_of_month` int(2) DEFAULT NULL,
  `repeat_week_of_month` int(2) DEFAULT NULL,
  `repeat_day_of_week` int(2) DEFAULT NULL,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `comment_internal` text,
  PRIMARY KEY (`assign_id`),
  KEY `resource_id` (`resource_id`),
  KEY `assign_user_id` (`assign_user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_categories`
--

DROP TABLE IF EXISTS `resources_categories`;
CREATE TABLE IF NOT EXISTS `resources_categories` (
  `category_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `system` tinyint(4) NOT NULL DEFAULT '0',
  `is_room` tinyint(4) NOT NULL DEFAULT '0',
  `iconnr` int(3) DEFAULT '1',
  PRIMARY KEY (`category_id`),
  KEY `is_room` (`is_room`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_categories_properties`
--

DROP TABLE IF EXISTS `resources_categories_properties`;
CREATE TABLE IF NOT EXISTS `resources_categories_properties` (
  `category_id` varchar(32) NOT NULL DEFAULT '',
  `property_id` varchar(32) NOT NULL DEFAULT '',
  `requestable` tinyint(4) NOT NULL DEFAULT '0',
  `system` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`category_id`,`property_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_locks`
--

DROP TABLE IF EXISTS `resources_locks`;
CREATE TABLE IF NOT EXISTS `resources_locks` (
  `lock_id` varchar(32) NOT NULL DEFAULT '',
  `lock_begin` int(20) unsigned DEFAULT NULL,
  `lock_end` int(20) unsigned DEFAULT NULL,
  `type` varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (`lock_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_objects`
--

DROP TABLE IF EXISTS `resources_objects`;
CREATE TABLE IF NOT EXISTS `resources_objects` (
  `resource_id` varchar(32) NOT NULL DEFAULT '',
  `root_id` varchar(32) NOT NULL DEFAULT '',
  `parent_id` varchar(32) NOT NULL DEFAULT '',
  `category_id` varchar(32) NOT NULL DEFAULT '',
  `owner_id` varchar(32) NOT NULL DEFAULT '',
  `institut_id` varchar(32) NOT NULL DEFAULT '',
  `level` int(4) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `lockable` tinyint(4) DEFAULT NULL,
  `multiple_assign` tinyint(4) DEFAULT NULL,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`resource_id`),
  KEY `institut_id` (`institut_id`),
  KEY `root_id` (`root_id`),
  KEY `parent_id` (`parent_id`),
  KEY `category_id` (`category_id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_objects_properties`
--

DROP TABLE IF EXISTS `resources_objects_properties`;
CREATE TABLE IF NOT EXISTS `resources_objects_properties` (
  `resource_id` varchar(32) NOT NULL DEFAULT '',
  `property_id` varchar(32) NOT NULL DEFAULT '',
  `state` text NOT NULL,
  PRIMARY KEY (`resource_id`,`property_id`),
  KEY `property_id` (`property_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_properties`
--

DROP TABLE IF EXISTS `resources_properties`;
CREATE TABLE IF NOT EXISTS `resources_properties` (
  `property_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `type` set('bool','text','num','select') NOT NULL DEFAULT 'bool',
  `options` text NOT NULL,
  `system` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`property_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_requests`
--

DROP TABLE IF EXISTS `resources_requests`;
CREATE TABLE IF NOT EXISTS `resources_requests` (
  `request_id` varchar(32) NOT NULL DEFAULT '',
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `termin_id` varchar(32) NOT NULL DEFAULT '',
  `metadate_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `resource_id` varchar(32) NOT NULL DEFAULT '',
  `category_id` varchar(32) NOT NULL DEFAULT '',
  `comment` text,
  `reply_comment` text,
  `closed` tinyint(3) unsigned DEFAULT NULL,
  `mkdate` int(20) unsigned DEFAULT NULL,
  `chdate` int(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `termin_id` (`termin_id`),
  KEY `seminar_id` (`seminar_id`),
  KEY `user_id` (`user_id`),
  KEY `resource_id` (`resource_id`),
  KEY `category_id` (`category_id`),
  KEY `closed` (`closed`,`request_id`,`resource_id`),
  KEY `metadate_id` (`metadate_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_requests_properties`
--

DROP TABLE IF EXISTS `resources_requests_properties`;
CREATE TABLE IF NOT EXISTS `resources_requests_properties` (
  `request_id` varchar(32) NOT NULL DEFAULT '',
  `property_id` varchar(32) NOT NULL DEFAULT '',
  `state` text,
  `mkdate` int(20) unsigned DEFAULT NULL,
  `chdate` int(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`request_id`,`property_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_temporary_events`
--

DROP TABLE IF EXISTS `resources_temporary_events`;
CREATE TABLE IF NOT EXISTS `resources_temporary_events` (
  `event_id` char(32) NOT NULL DEFAULT '',
  `resource_id` char(32) NOT NULL DEFAULT '',
  `assign_id` char(32) NOT NULL DEFAULT '',
  `begin` int(20) NOT NULL DEFAULT '0',
  `end` int(20) NOT NULL DEFAULT '0',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`event_id`),
  KEY `resource_id` (`resource_id`,`begin`),
  KEY `assign_object_id` (`assign_id`,`resource_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_user_resources`
--

DROP TABLE IF EXISTS `resources_user_resources`;
CREATE TABLE IF NOT EXISTS `resources_user_resources` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `resource_id` varchar(32) NOT NULL DEFAULT '',
  `perms` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`,`resource_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `roleid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rolename` varchar(80) NOT NULL DEFAULT '',
  `system` enum('y','n') NOT NULL DEFAULT 'n',
  PRIMARY KEY (`roleid`)
) ENGINE=MyISAM ;

-- --------------------------------------------------------

--
-- Table structure for table `roles_plugins`
--

DROP TABLE IF EXISTS `roles_plugins`;
CREATE TABLE IF NOT EXISTS `roles_plugins` (
  `roleid` int(10) unsigned NOT NULL DEFAULT '0',
  `pluginid` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`roleid`,`pluginid`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `roles_studipperms`
--

DROP TABLE IF EXISTS `roles_studipperms`;
CREATE TABLE IF NOT EXISTS `roles_studipperms` (
  `roleid` int(10) unsigned NOT NULL DEFAULT '0',
  `permname` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`roleid`,`permname`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `roles_user`
--

DROP TABLE IF EXISTS `roles_user`;
CREATE TABLE IF NOT EXISTS `roles_user` (
  `roleid` int(10) unsigned NOT NULL DEFAULT '0',
  `userid` char(32) NOT NULL DEFAULT '',
  `institut_id` char(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`roleid`,`userid`,`institut_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `rss_feeds`
--

DROP TABLE IF EXISTS `rss_feeds`;
CREATE TABLE IF NOT EXISTS `rss_feeds` (
  `feed_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` text NOT NULL,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT '0',
  `hidden` tinyint(4) NOT NULL DEFAULT '0',
  `fetch_title` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`feed_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

DROP TABLE IF EXISTS `schedule`;
CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start` smallint(6) NOT NULL COMMENT 'start hour and minutes',
  `end` smallint(6) NOT NULL COMMENT 'end hour and minutes',
  `day` tinyint(4) NOT NULL COMMENT 'day of week, 0-6',
  `title` varchar(255) NOT NULL,
  `content` varchar(255) NOT NULL,
  `color` varchar(7) NOT NULL COMMENT 'color, rgb in hex',
  `user_id` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `schedule_seminare`
--

DROP TABLE IF EXISTS `schedule_seminare`;
CREATE TABLE IF NOT EXISTS `schedule_seminare` (
  `user_id` varchar(32) NOT NULL,
  `seminar_id` varchar(32) NOT NULL,
  `metadate_id` varchar(32) NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `color` varchar(7) DEFAULT NULL COMMENT 'color, rgb in hex',
  PRIMARY KEY (`user_id`,`seminar_id`,`metadate_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `schema_version`
--

DROP TABLE IF EXISTS `schema_version`;
CREATE TABLE IF NOT EXISTS `schema_version` (
  `domain` varchar(255) NOT NULL DEFAULT '',
  `version` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`domain`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `scm`
--

DROP TABLE IF EXISTS `scm`;
CREATE TABLE IF NOT EXISTS `scm` (
  `scm_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `tab_name` varchar(20) NOT NULL DEFAULT 'Info',
  `content` text,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `position` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`scm_id`),
  KEY `chdate` (`chdate`),
  KEY `range_id` (`range_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `semester_data`
--

DROP TABLE IF EXISTS `semester_data`;
CREATE TABLE IF NOT EXISTS `semester_data` (
  `semester_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `semester_token` varchar(10) NOT NULL DEFAULT '',
  `beginn` int(20) unsigned DEFAULT NULL,
  `ende` int(20) unsigned DEFAULT NULL,
  `vorles_beginn` int(20) unsigned DEFAULT NULL,
  `vorles_ende` int(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`semester_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `semester_holiday`
--

DROP TABLE IF EXISTS `semester_holiday`;
CREATE TABLE IF NOT EXISTS `semester_holiday` (
  `holiday_id` varchar(32) NOT NULL DEFAULT '',
  `semester_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `beginn` int(20) unsigned DEFAULT NULL,
  `ende` int(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`holiday_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `seminare`
--

DROP TABLE IF EXISTS `seminare`;
CREATE TABLE IF NOT EXISTS `seminare` (
  `Seminar_id` varchar(32) NOT NULL DEFAULT '0',
  `VeranstaltungsNummer` varchar(100) DEFAULT NULL,
  `Institut_id` varchar(32) NOT NULL DEFAULT '0',
  `Name` varchar(255) NOT NULL DEFAULT '',
  `Untertitel` varchar(255) DEFAULT NULL,
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1',
  `Beschreibung` text NOT NULL,
  `Ort` varchar(255) DEFAULT NULL,
  `Sonstiges` text,
  `Lesezugriff` tinyint(4) NOT NULL DEFAULT '0',
  `Schreibzugriff` tinyint(4) NOT NULL DEFAULT '0',
  `start_time` int(20) DEFAULT '0',
  `duration_time` int(20) DEFAULT NULL,
  `art` varchar(255) DEFAULT NULL,
  `teilnehmer` text,
  `vorrausetzungen` text,
  `lernorga` text,
  `leistungsnachweis` text,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `ects` varchar(32) DEFAULT NULL,
  `admission_turnout` int(5) DEFAULT NULL,
  `admission_binding` tinyint(4) DEFAULT NULL,
  `admission_prelim` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `admission_prelim_txt` text,
  `admission_disable_waitlist` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `visible` tinyint(2) unsigned NOT NULL DEFAULT '1',
  `showscore` tinyint(3) DEFAULT '0',
  `modules` int(10) unsigned DEFAULT NULL,
  `aux_lock_rule` varchar(32) DEFAULT NULL,
  `aux_lock_rule_forced` tinyint(4) NOT NULL DEFAULT '0',
  `lock_rule` varchar(32) DEFAULT NULL,
  `admission_waitlist_max` int(10) unsigned NOT NULL DEFAULT '0',
  `admission_disable_waitlist_move` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Seminar_id`),
  KEY `Institut_id` (`Institut_id`),
  KEY `visible` (`visible`),
  KEY `status` (`status`,`Seminar_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_courseset`
--

DROP TABLE IF EXISTS `seminar_courseset`;
CREATE TABLE IF NOT EXISTS `seminar_courseset` (
  `set_id` varchar(32) NOT NULL,
  `seminar_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`set_id`,`seminar_id`),
  KEY `seminar_id` (`seminar_id`,`set_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_cycle_dates`
--

DROP TABLE IF EXISTS `seminar_cycle_dates`;
CREATE TABLE IF NOT EXISTS `seminar_cycle_dates` (
  `metadate_id` varchar(32) NOT NULL,
  `seminar_id` varchar(32) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `weekday` tinyint(3) unsigned NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `sws` decimal(2,1) NOT NULL DEFAULT '0.0',
  `cycle` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `week_offset` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `sorter` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `mkdate` int(10) unsigned NOT NULL,
  `chdate` int(10) unsigned NOT NULL,
  PRIMARY KEY (`metadate_id`),
  KEY `seminar_id` (`seminar_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_inst`
--

DROP TABLE IF EXISTS `seminar_inst`;
CREATE TABLE IF NOT EXISTS `seminar_inst` (
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `institut_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`seminar_id`,`institut_id`),
  KEY `institut_id` (`institut_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_sem_tree`
--

DROP TABLE IF EXISTS `seminar_sem_tree`;
CREATE TABLE IF NOT EXISTS `seminar_sem_tree` (
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `sem_tree_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`seminar_id`,`sem_tree_id`),
  KEY `sem_tree_id` (`sem_tree_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_user`
--

DROP TABLE IF EXISTS `seminar_user`;
CREATE TABLE IF NOT EXISTS `seminar_user` (
  `Seminar_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `status` enum('user','autor','tutor','dozent') NOT NULL DEFAULT 'user',
  `position` int(11) NOT NULL DEFAULT '0',
  `gruppe` tinyint(4) NOT NULL DEFAULT '0',
  `notification` int(10) NOT NULL DEFAULT '0',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `comment` varchar(255) NOT NULL DEFAULT '',
  `visible` enum('yes','no','unknown') NOT NULL DEFAULT 'unknown',
  `label` varchar(128) NOT NULL DEFAULT '',
  `bind_calendar` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`Seminar_id`,`user_id`),
  KEY `status` (`status`,`Seminar_id`),
  KEY `user_id` (`user_id`,`Seminar_id`,`status`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_userdomains`
--

DROP TABLE IF EXISTS `seminar_userdomains`;
CREATE TABLE IF NOT EXISTS `seminar_userdomains` (
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `userdomain_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`seminar_id`,`userdomain_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `sem_classes`
--

DROP TABLE IF EXISTS `sem_classes`;
CREATE TABLE IF NOT EXISTS `sem_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `compact_mode` tinyint(4) NOT NULL,
  `workgroup_mode` tinyint(4) NOT NULL,
  `only_inst_user` tinyint(4) NOT NULL,
  `turnus_default` int(11) NOT NULL,
  `default_read_level` int(11) NOT NULL,
  `default_write_level` int(11) NOT NULL,
  `bereiche` tinyint(4) NOT NULL,
  `show_browse` tinyint(4) NOT NULL,
  `write_access_nobody` tinyint(4) NOT NULL,
  `topic_create_autor` tinyint(4) NOT NULL,
  `visible` tinyint(4) NOT NULL,
  `course_creation_forbidden` tinyint(4) NOT NULL,
  `overview` varchar(64) DEFAULT NULL,
  `forum` varchar(64) DEFAULT NULL,
  `admin` varchar(64) DEFAULT NULL,
  `documents` varchar(64) DEFAULT NULL,
  `schedule` varchar(64) DEFAULT NULL,
  `participants` varchar(64) DEFAULT NULL,
  `literature` varchar(64) DEFAULT NULL,
  `scm` varchar(64) DEFAULT NULL,
  `wiki` varchar(64) DEFAULT NULL,
  `resources` varchar(64) DEFAULT NULL,
  `calendar` varchar(64) DEFAULT NULL,
  `elearning_interface` varchar(64) DEFAULT NULL,
  `modules` text NOT NULL,
  `description` text NOT NULL,
  `create_description` text NOT NULL,
  `studygroup_mode` tinyint(4) NOT NULL,
  `admission_prelim_default` tinyint(4) NOT NULL DEFAULT '0',
  `admission_type_default` tinyint(4) NOT NULL DEFAULT '0',
  `title_dozent` varchar(64) DEFAULT NULL,
  `title_dozent_plural` varchar(64) DEFAULT NULL,
  `title_tutor` varchar(64) DEFAULT NULL,
  `title_tutor_plural` varchar(64) DEFAULT NULL,
  `title_autor` varchar(64) DEFAULT NULL,
  `title_autor_plural` varchar(64) DEFAULT NULL,
  `mkdate` bigint(20) NOT NULL,
  `chdate` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM ;

-- --------------------------------------------------------

--
-- Table structure for table `sem_tree`
--

DROP TABLE IF EXISTS `sem_tree`;
CREATE TABLE IF NOT EXISTS `sem_tree` (
  `sem_tree_id` varchar(32) NOT NULL DEFAULT '',
  `parent_id` varchar(32) NOT NULL DEFAULT '',
  `priority` tinyint(4) NOT NULL DEFAULT '0',
  `info` text NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `studip_object_id` varchar(32) DEFAULT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`sem_tree_id`),
  KEY `parent_id` (`parent_id`),
  KEY `priority` (`priority`),
  KEY `studip_object_id` (`studip_object_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `sem_types`
--

DROP TABLE IF EXISTS `sem_types`;
CREATE TABLE IF NOT EXISTS `sem_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `class` int(11) NOT NULL,
  `mkdate` bigint(20) NOT NULL,
  `chdate` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM ;

-- --------------------------------------------------------

--
-- Table structure for table `session_data`
--

DROP TABLE IF EXISTS `session_data`;
CREATE TABLE IF NOT EXISTS `session_data` (
  `sid` varchar(32) NOT NULL DEFAULT '',
  `val` mediumtext NOT NULL,
  `changed` timestamp NOT NULL,
  PRIMARY KEY (`sid`),
  KEY `changed` (`changed`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `siteinfo_details`
--

DROP TABLE IF EXISTS `siteinfo_details`;
CREATE TABLE IF NOT EXISTS `siteinfo_details` (
  `detail_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `rubric_id` smallint(5) unsigned NOT NULL,
  `position` tinyint(3) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`detail_id`)
) ENGINE=MyISAM ;

-- --------------------------------------------------------

--
-- Table structure for table `siteinfo_rubrics`
--

DROP TABLE IF EXISTS `siteinfo_rubrics`;
CREATE TABLE IF NOT EXISTS `siteinfo_rubrics` (
  `rubric_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `position` tinyint(3) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`rubric_id`)
) ENGINE=MyISAM ;

-- --------------------------------------------------------

--
-- Table structure for table `smiley`
--

DROP TABLE IF EXISTS `smiley`;
CREATE TABLE IF NOT EXISTS `smiley` (
  `smiley_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `smiley_name` varchar(50) NOT NULL DEFAULT '',
  `smiley_width` int(11) NOT NULL DEFAULT '0',
  `smiley_height` int(11) NOT NULL DEFAULT '0',
  `short_name` varchar(50) NOT NULL DEFAULT '',
  `smiley_counter` int(11) unsigned NOT NULL DEFAULT '0',
  `short_counter` int(11) unsigned NOT NULL DEFAULT '0',
  `fav_counter` int(11) unsigned NOT NULL DEFAULT '0',
  `mkdate` int(10) unsigned DEFAULT NULL,
  `chdate` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`smiley_id`),
  UNIQUE KEY `name` (`smiley_name`),
  KEY `short` (`short_name`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `statusgruppen`
--

DROP TABLE IF EXISTS `statusgruppen`;
CREATE TABLE IF NOT EXISTS `statusgruppen` (
  `statusgruppe_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `position` int(20) NOT NULL DEFAULT '0',
  `size` int(20) NOT NULL DEFAULT '0',
  `selfassign` tinyint(4) NOT NULL DEFAULT '0',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `calendar_group` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `name_w` varchar(255) DEFAULT NULL,
  `name_m` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`statusgruppe_id`),
  KEY `range_id` (`range_id`),
  KEY `position` (`position`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `statusgruppe_user`
--

DROP TABLE IF EXISTS `statusgruppe_user`;
CREATE TABLE IF NOT EXISTS `statusgruppe_user` (
  `statusgruppe_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `position` int(11) NOT NULL DEFAULT '0',
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `inherit` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`statusgruppe_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `stm_abstract`
--

DROP TABLE IF EXISTS `stm_abstract`;
CREATE TABLE IF NOT EXISTS `stm_abstract` (
  `stm_abstr_id` varchar(32) NOT NULL DEFAULT '',
  `id_number` varchar(10) DEFAULT NULL COMMENT 'alphanummerische Identifikationsnummer für das Modul',
  `duration` varchar(155) DEFAULT NULL,
  `credits` tinyint(3) unsigned DEFAULT NULL COMMENT 'Anzahl der Leistungspunkte/Kreditpunkte',
  `workload` smallint(6) unsigned DEFAULT NULL COMMENT 'Studentischer Arbeitsaufwand in Stunden',
  `turnus` tinyint(1) DEFAULT NULL COMMENT '(optional) Angebotsturnus - Modulbeginn',
  `mkdate` int(20) DEFAULT NULL COMMENT 'Erstellungdatum',
  `chdate` int(20) DEFAULT NULL COMMENT 'Datum der letzten Aenderung',
  `homeinst` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`stm_abstr_id`)
) ENGINE=MyISAM COMMENT='abstrakte Module';

-- --------------------------------------------------------

--
-- Table structure for table `stm_abstract_assign`
--

DROP TABLE IF EXISTS `stm_abstract_assign`;
CREATE TABLE IF NOT EXISTS `stm_abstract_assign` (
  `stm_abstr_id` varchar(32) NOT NULL DEFAULT '',
  `stm_type_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID eines Modultyps',
  `abschl` char(3) NOT NULL DEFAULT '' COMMENT 'ID eines Studienabschlusses',
  `stg` char(3) NOT NULL DEFAULT '' COMMENT 'ID eines Studienprogramms/-fachs',
  `pversion` varchar(8) NOT NULL DEFAULT '' COMMENT 'Version der Prüfungsordnung',
  `earliest` tinyint(4) DEFAULT NULL COMMENT 'frührester Zeitpunkt (Semester)',
  `latest` tinyint(4) DEFAULT NULL COMMENT 'spätester Zpkt.',
  `recommed` tinyint(4) DEFAULT NULL COMMENT 'empfohlener Zpkt.',
  PRIMARY KEY (`stm_abstr_id`,`abschl`,`stg`,`pversion`),
  KEY `studycourse` (`abschl`,`stg`)
) ENGINE=MyISAM COMMENT='Zuordnung abstrakte Module <-> Studienprogramme';

-- --------------------------------------------------------

--
-- Table structure for table `stm_abstract_elements`
--

DROP TABLE IF EXISTS `stm_abstract_elements`;
CREATE TABLE IF NOT EXISTS `stm_abstract_elements` (
  `element_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID eines abstrakten Modulbestandzeiles',
  `stm_abstr_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID eines abstrakten Studienmodules',
  `element_type_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'um welche Art von Element handelt es sich',
  `custom_name` varchar(50) DEFAULT NULL COMMENT 'selbstgewählter Name',
  `sws` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Semesterwochenstunden für den Bestandteil',
  `workload` int(4) NOT NULL DEFAULT '0',
  `semester` tinyint(1) DEFAULT NULL COMMENT 'Sommer od. Winter (Sommer = 1; Winter = 2)',
  `elementgroup` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Kombinationsvariante',
  `position` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Reihenfolge ',
  PRIMARY KEY (`element_id`),
  UNIQUE KEY `elem_integr` (`stm_abstr_id`,`elementgroup`,`position`)
) ENGINE=MyISAM COMMENT='Bestandteile eines Abstrakten Moduls (Elemente)';

-- --------------------------------------------------------

--
-- Table structure for table `stm_abstract_text`
--

DROP TABLE IF EXISTS `stm_abstract_text`;
CREATE TABLE IF NOT EXISTS `stm_abstract_text` (
  `stm_abstr_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID des abstrakten Studienmodules',
  `lang_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID der verwendeten Sprache',
  `title` varchar(155) NOT NULL DEFAULT '' COMMENT 'Allgemeiner Modultitel (Name des Moduls)',
  `subtitle` varchar(155) DEFAULT NULL COMMENT 'optionaler Untertitel',
  `topics` text NOT NULL COMMENT 'Inhalte (behandelte Themen etc.)',
  `aims` text NOT NULL COMMENT 'Lernziele',
  `hints` text,
  PRIMARY KEY (`stm_abstr_id`,`lang_id`)
) ENGINE=MyISAM COMMENT='(mehrsprachige) Texte der abstrakten Module';

-- --------------------------------------------------------

--
-- Table structure for table `stm_abstract_types`
--

DROP TABLE IF EXISTS `stm_abstract_types`;
CREATE TABLE IF NOT EXISTS `stm_abstract_types` (
  `stm_type_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID eines Modultyps',
  `lang_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID der verwendeten Sprache',
  `abbrev` varchar(5) NOT NULL DEFAULT '' COMMENT 'Abkuerzung',
  `name` varchar(25) NOT NULL DEFAULT '' COMMENT 'vollstaendige Bezeichnung',
  PRIMARY KEY (`stm_type_id`,`lang_id`)
) ENGINE=MyISAM COMMENT='Typen abstrakter Module';

-- --------------------------------------------------------

--
-- Table structure for table `stm_element_types`
--

DROP TABLE IF EXISTS `stm_element_types`;
CREATE TABLE IF NOT EXISTS `stm_element_types` (
  `element_type_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID des Modulbestandteils',
  `lang_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID der verwendeten Sprache',
  `abbrev` varchar(5) DEFAULT NULL COMMENT 'Kurzname',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT 'Name',
  PRIMARY KEY (`element_type_id`,`lang_id`)
) ENGINE=MyISAM COMMENT='Typen von müglichen Bestandteilen eines abstrakten Moduls';

-- --------------------------------------------------------

--
-- Table structure for table `stm_instances`
--

DROP TABLE IF EXISTS `stm_instances`;
CREATE TABLE IF NOT EXISTS `stm_instances` (
  `stm_instance_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID eines konkreten Studienmodules',
  `stm_abstr_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID eines abstrakten Studienmodules',
  `semester_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID des ersten Semesters in dem die Instanz stattfindet',
  `lang_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID der Sprache in der das Modul angeboten wird',
  `homeinst` varchar(32) DEFAULT NULL COMMENT 'ID des anbietenden Institutes',
  `creator` varchar(32) NOT NULL,
  `responsible` varchar(32) DEFAULT NULL COMMENT 'ID des Modulverantwortlichen Dozenten',
  `complete` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Erfassung komplett (0=FALSE)',
  PRIMARY KEY (`stm_instance_id`)
) ENGINE=MyISAM COMMENT='Instanzen der abstrakten Module';

-- --------------------------------------------------------

--
-- Table structure for table `stm_instances_elements`
--

DROP TABLE IF EXISTS `stm_instances_elements`;
CREATE TABLE IF NOT EXISTS `stm_instances_elements` (
  `stm_instance_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID eines konkreten Studienmodules',
  `element_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID des abstrakten Modulbestandteils',
  `sem_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID der konkreten Veranstaltung',
  PRIMARY KEY (`stm_instance_id`,`element_id`,`sem_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `stm_instances_text`
--

DROP TABLE IF EXISTS `stm_instances_text`;
CREATE TABLE IF NOT EXISTS `stm_instances_text` (
  `stm_instance_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID eines konkreten Studienmodules',
  `lang_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ID der verwendeten Sprache',
  `title` varchar(155) NOT NULL DEFAULT '' COMMENT 'Allgemeiner Modultitel',
  `subtitle` varchar(155) DEFAULT NULL COMMENT 'optionaler Untertitel',
  `topics` text NOT NULL COMMENT 'Inhalte',
  `hints` text,
  PRIMARY KEY (`stm_instance_id`,`lang_id`)
) ENGINE=MyISAM COMMENT='(mehrsprachige) Texte der instanziierten abstrakten Module';

-- --------------------------------------------------------

--
-- Table structure for table `studiengaenge`
--

DROP TABLE IF EXISTS `studiengaenge`;
CREATE TABLE IF NOT EXISTS `studiengaenge` (
  `studiengang_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) DEFAULT NULL,
  `beschreibung` text,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`studiengang_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `termine`
--

DROP TABLE IF EXISTS `termine`;
CREATE TABLE IF NOT EXISTS `termine` (
  `termin_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `autor_id` varchar(32) NOT NULL DEFAULT '',
  `content` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `date` int(20) NOT NULL DEFAULT '0',
  `end_time` int(20) NOT NULL DEFAULT '0',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `date_typ` tinyint(4) NOT NULL DEFAULT '0',
  `topic_id` varchar(32) DEFAULT NULL,
  `raum` varchar(255) DEFAULT NULL,
  `metadate_id` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`termin_id`),
  KEY `metadate_id` (`metadate_id`,`date`),
  KEY `range_id` (`range_id`,`date`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `termin_related_groups`
--

DROP TABLE IF EXISTS `termin_related_groups`;
CREATE TABLE IF NOT EXISTS `termin_related_groups` (
  `termin_id` varchar(32) NOT NULL,
  `statusgruppe_id` varchar(45) NOT NULL,
  UNIQUE KEY `unique` (`termin_id`,`statusgruppe_id`),
  KEY `termin_id` (`termin_id`),
  KEY `statusgruppe_id` (`statusgruppe_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `termin_related_persons`
--

DROP TABLE IF EXISTS `termin_related_persons`;
CREATE TABLE IF NOT EXISTS `termin_related_persons` (
  `range_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  PRIMARY KEY (`range_id`,`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `themen`
--

DROP TABLE IF EXISTS `themen`;
CREATE TABLE IF NOT EXISTS `themen` (
  `issue_id` varchar(32) NOT NULL DEFAULT '',
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `author_id` varchar(32) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `priority` smallint(5) unsigned NOT NULL DEFAULT '0',
  `mkdate` int(10) unsigned NOT NULL DEFAULT '0',
  `chdate` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`issue_id`),
  KEY `seminar_id` (`seminar_id`,`priority`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `themen_termine`
--

DROP TABLE IF EXISTS `themen_termine`;
CREATE TABLE IF NOT EXISTS `themen_termine` (
  `issue_id` varchar(32) NOT NULL DEFAULT '',
  `termin_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`issue_id`,`termin_id`),
  KEY `termin_id` (`termin_id`,`issue_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `timedadmissions`
--

DROP TABLE IF EXISTS `timedadmissions`;
CREATE TABLE IF NOT EXISTS `timedadmissions` (
  `rule_id` varchar(32) NOT NULL,
  `message` text NOT NULL,
  `start_time` int(11) NOT NULL DEFAULT '0',
  `end_time` int(11) NOT NULL DEFAULT '0',
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rule_id`),
  KEY `start_time` (`start_time`),
  KEY `end_time` (`end_time`),
  KEY `start_end` (`start_time`,`end_time`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `userdomains`
--

DROP TABLE IF EXISTS `userdomains`;
CREATE TABLE IF NOT EXISTS `userdomains` (
  `userdomain_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`userdomain_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `userfilter`
--

DROP TABLE IF EXISTS `userfilter`;
CREATE TABLE IF NOT EXISTS `userfilter` (
  `filter_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`filter_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `userfilter_fields`
--

DROP TABLE IF EXISTS `userfilter_fields`;
CREATE TABLE IF NOT EXISTS `userfilter_fields` (
  `field_id` varchar(32) NOT NULL,
  `filter_id` varchar(32) NOT NULL,
  `type` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `compare_op` varchar(255) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`field_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `userlimits`
--

DROP TABLE IF EXISTS `userlimits`;
CREATE TABLE IF NOT EXISTS `userlimits` (
  `rule_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `maxnumber` int(11) DEFAULT NULL,
  `mkdate` int(11) DEFAULT NULL,
  `chdate` int(11) DEFAULT NULL,
  PRIMARY KEY (`rule_id`,`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_config`
--

DROP TABLE IF EXISTS `user_config`;
CREATE TABLE IF NOT EXISTS `user_config` (
  `userconfig_id` varchar(32) NOT NULL DEFAULT '',
  `parent_id` varchar(32) DEFAULT NULL,
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `field` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  PRIMARY KEY (`userconfig_id`),
  KEY `user_id` (`user_id`,`field`,`value`(5))
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_factorlist`
--

DROP TABLE IF EXISTS `user_factorlist`;
CREATE TABLE IF NOT EXISTS `user_factorlist` (
  `list_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `mkdate` int(11) DEFAULT NULL,
  PRIMARY KEY (`list_id`,`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

DROP TABLE IF EXISTS `user_info`;
CREATE TABLE IF NOT EXISTS `user_info` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `hobby` varchar(255) NOT NULL DEFAULT '',
  `lebenslauf` text,
  `publi` text NOT NULL,
  `schwerp` text NOT NULL,
  `Home` varchar(200) NOT NULL DEFAULT '',
  `privatnr` varchar(32) NOT NULL DEFAULT '',
  `privatcell` varchar(32) NOT NULL DEFAULT '',
  `privadr` varchar(64) NOT NULL DEFAULT '',
  `score` int(11) unsigned NOT NULL DEFAULT '0',
  `geschlecht` tinyint(4) NOT NULL DEFAULT '0',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `title_front` varchar(64) NOT NULL DEFAULT '',
  `title_rear` varchar(64) NOT NULL DEFAULT '',
  `preferred_language` varchar(20) DEFAULT NULL,
  `smsforward_copy` tinyint(1) NOT NULL DEFAULT '1',
  `smsforward_rec` varchar(32) NOT NULL DEFAULT '',
  `guestbook` tinyint(4) NOT NULL DEFAULT '0',
  `email_forward` tinyint(4) NOT NULL DEFAULT '0',
  `smiley_favorite` varchar(255) NOT NULL DEFAULT '',
  `motto` varchar(255) NOT NULL DEFAULT '',
  `lock_rule` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`),
  KEY `score` (`score`,`guestbook`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `user_inst`
--

DROP TABLE IF EXISTS `user_inst`;
CREATE TABLE IF NOT EXISTS `user_inst` (
  `user_id` varchar(32) NOT NULL DEFAULT '0',
  `Institut_id` varchar(32) NOT NULL DEFAULT '0',
  `inst_perms` enum('user','autor','tutor','dozent','admin') NOT NULL DEFAULT 'user',
  `sprechzeiten` varchar(200) NOT NULL DEFAULT '',
  `raum` varchar(200) NOT NULL DEFAULT '',
  `Telefon` varchar(32) NOT NULL DEFAULT '',
  `Fax` varchar(32) NOT NULL DEFAULT '',
  `externdefault` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `visible` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`Institut_id`,`user_id`),
  KEY `inst_perms` (`inst_perms`,`Institut_id`),
  KEY `user_id` (`user_id`,`inst_perms`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `user_online`
--

DROP TABLE IF EXISTS `user_online`;
CREATE TABLE IF NOT EXISTS `user_online` (
  `user_id` char(32) NOT NULL,
  `last_lifesign` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `last_lifesign` (`last_lifesign`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_studiengang`
--

DROP TABLE IF EXISTS `user_studiengang`;
CREATE TABLE IF NOT EXISTS `user_studiengang` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `studiengang_id` varchar(32) NOT NULL DEFAULT '',
  `semester` tinyint(2) DEFAULT '0',
  `abschluss_id` char(32) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`studiengang_id`,`abschluss_id`),
  KEY `studiengang_id` (`studiengang_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_token`
--

DROP TABLE IF EXISTS `user_token`;
CREATE TABLE IF NOT EXISTS `user_token` (
  `user_id` varchar(32) NOT NULL,
  `token` varchar(32) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`token`,`expiration`),
  KEY `index_expiration` (`expiration`),
  KEY `index_token` (`token`),
  KEY `index_user_id` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_userdomains`
--

DROP TABLE IF EXISTS `user_userdomains`;
CREATE TABLE IF NOT EXISTS `user_userdomains` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `userdomain_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`,`userdomain_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_visibility`
--

DROP TABLE IF EXISTS `user_visibility`;
CREATE TABLE IF NOT EXISTS `user_visibility` (
  `user_id` varchar(32) NOT NULL,
  `online` tinyint(1) NOT NULL DEFAULT '1',
  `search` tinyint(1) NOT NULL DEFAULT '1',
  `email` tinyint(1) NOT NULL DEFAULT '1',
  `homepage` text NOT NULL,
  `default_homepage_visibility` int(11) NOT NULL DEFAULT '0',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_visibility_settings`
--

DROP TABLE IF EXISTS `user_visibility_settings`;
CREATE TABLE IF NOT EXISTS `user_visibility_settings` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `visibilityid` int(32) NOT NULL AUTO_INCREMENT,
  `parent_id` int(32) NOT NULL,
  `category` varchar(128) NOT NULL,
  `name` varchar(128) NOT NULL,
  `state` int(2) DEFAULT NULL,
  `plugin` int(11) DEFAULT NULL,
  `identifier` varchar(64) NOT NULL,
  PRIMARY KEY (`visibilityid`),
  KEY `parent_id` (`parent_id`),
  KEY `identifier` (`identifier`),
  KEY `userid` (`user_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `vote`
--

DROP TABLE IF EXISTS `vote`;
CREATE TABLE IF NOT EXISTS `vote` (
  `vote_id` varchar(32) NOT NULL DEFAULT '',
  `author_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `type` enum('vote','test') NOT NULL DEFAULT 'vote',
  `title` varchar(100) NOT NULL DEFAULT '',
  `question` text NOT NULL,
  `state` enum('new','active','stopvis','stopinvis') NOT NULL DEFAULT 'new',
  `startdate` int(20) DEFAULT NULL,
  `stopdate` int(20) DEFAULT NULL,
  `timespan` int(20) DEFAULT NULL,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0',
  `resultvisibility` enum('ever','delivery','end','never') NOT NULL DEFAULT 'ever',
  `multiplechoice` tinyint(1) NOT NULL DEFAULT '0',
  `anonymous` tinyint(1) NOT NULL DEFAULT '1',
  `changeable` tinyint(1) NOT NULL DEFAULT '0',
  `co_visibility` tinyint(1) DEFAULT NULL,
  `namesvisibility` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vote_id`),
  KEY `range_id` (`range_id`),
  KEY `state` (`state`),
  KEY `startdate` (`startdate`),
  KEY `stopdate` (`stopdate`),
  KEY `resultvisibility` (`resultvisibility`),
  KEY `chdate` (`chdate`),
  KEY `author_id` (`author_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `voteanswers`
--

DROP TABLE IF EXISTS `voteanswers`;
CREATE TABLE IF NOT EXISTS `voteanswers` (
  `answer_id` varchar(32) NOT NULL DEFAULT '',
  `vote_id` varchar(32) NOT NULL DEFAULT '',
  `answer` varchar(255) NOT NULL DEFAULT '',
  `position` int(11) NOT NULL DEFAULT '0',
  `counter` int(11) NOT NULL DEFAULT '0',
  `correct` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`answer_id`),
  KEY `vote_id` (`vote_id`),
  KEY `position` (`position`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `voteanswers_user`
--

DROP TABLE IF EXISTS `voteanswers_user`;
CREATE TABLE IF NOT EXISTS `voteanswers_user` (
  `answer_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `votedate` int(20) DEFAULT NULL,
  PRIMARY KEY (`answer_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `vote_user`
--

DROP TABLE IF EXISTS `vote_user`;
CREATE TABLE IF NOT EXISTS `vote_user` (
  `vote_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `votedate` int(20) DEFAULT NULL,
  PRIMARY KEY (`vote_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `webservice_access_rules`
--

DROP TABLE IF EXISTS `webservice_access_rules`;
CREATE TABLE IF NOT EXISTS `webservice_access_rules` (
  `api_key` varchar(100) NOT NULL DEFAULT '',
  `method` varchar(100) NOT NULL DEFAULT '',
  `ip_range` varchar(200) NOT NULL DEFAULT '',
  `type` enum('allow','deny') NOT NULL DEFAULT 'allow',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `wiki`
--

DROP TABLE IF EXISTS `wiki`;
CREATE TABLE IF NOT EXISTS `wiki` (
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) DEFAULT NULL,
  `keyword` varchar(128) binary NOT NULL DEFAULT '',
  `body` text,
  `chdate` int(11) DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`range_id`,`keyword`,`version`),
  KEY `user_id` (`user_id`),
  KEY `chdate` (`chdate`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_links`
--

DROP TABLE IF EXISTS `wiki_links`;
CREATE TABLE IF NOT EXISTS `wiki_links` (
  `range_id` char(32) NOT NULL DEFAULT '',
  `from_keyword` char(128) binary NOT NULL DEFAULT '',
  `to_keyword` char(128) binary NOT NULL DEFAULT '',
  PRIMARY KEY (`range_id`,`to_keyword`,`from_keyword`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_locks`
--

DROP TABLE IF EXISTS `wiki_locks`;
CREATE TABLE IF NOT EXISTS `wiki_locks` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT '',
  `keyword` varchar(128) binary NOT NULL DEFAULT '',
  `chdate` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`range_id`,`user_id`,`keyword`),
  KEY `user_id` (`user_id`),
  KEY `chdate` (`chdate`)
) ENGINE=MyISAM;

