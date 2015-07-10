-- phpMyAdmin SQL Dump
-- version 4.4.11
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 10, 2015 at 08:22 PM
-- Server version: 5.6.25-73.1-log
-- PHP Version: 5.6.10-1+deb.sury.org~trusty+1

SET time_zone = "+00:00";

--
-- Database: `studip_32`
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
  `chdate` int(20) DEFAULT NULL
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
  `chdate` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `admissionrules`
--

DROP TABLE IF EXISTS `admissionrules`;
CREATE TABLE IF NOT EXISTS `admissionrules` (
  `id` int(11) NOT NULL,
  `ruletype` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `mkdate` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `admissionrule_inst`
--

DROP TABLE IF EXISTS `admissionrule_inst`;
CREATE TABLE IF NOT EXISTS `admissionrule_inst` (
  `rule_id` varchar(32) NOT NULL,
  `institute_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `admission_condition`
--

DROP TABLE IF EXISTS `admission_condition`;
CREATE TABLE IF NOT EXISTS `admission_condition` (
  `rule_id` varchar(32) NOT NULL,
  `filter_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0'
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
  `visible` enum('yes','no','unknown') NOT NULL DEFAULT 'unknown'
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
  `chdate` int(11) unsigned NOT NULL
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
  `granted` tinyint(1) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `api_oauth_user_mapping`
--

DROP TABLE IF EXISTS `api_oauth_user_mapping`;
CREATE TABLE IF NOT EXISTS `api_oauth_user_mapping` (
  `oauth_id` int(11) unsigned NOT NULL,
  `user_id` char(32) NOT NULL DEFAULT '',
  `mkdate` int(11) unsigned NOT NULL
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
  `chdate` int(11) unsigned NOT NULL
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
  `archiv_protected_file_id` varchar(32) NOT NULL DEFAULT '',
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `forumdump` longtext NOT NULL,
  `wikidump` longtext,
  `studienbereiche` text NOT NULL,
  `VeranstaltungsNummer` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `archiv_user`
--

DROP TABLE IF EXISTS `archiv_user`;
CREATE TABLE IF NOT EXISTS `archiv_user` (
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `status` enum('user','autor','tutor','dozent') NOT NULL DEFAULT 'user'
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
  `external_user_type` smallint(6) NOT NULL DEFAULT '0'
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
  `visible` enum('global','always','yes','unknown','no','never') NOT NULL DEFAULT 'unknown'
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `auto_insert_sem`
--

DROP TABLE IF EXISTS `auto_insert_sem`;
CREATE TABLE IF NOT EXISTS `auto_insert_sem` (
  `seminar_id` char(32) NOT NULL,
  `status` enum('autor','tutor','dozent') NOT NULL DEFAULT 'autor',
  `domain_id` varchar(45) NOT NULL DEFAULT ''
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `auto_insert_user`
--

DROP TABLE IF EXISTS `auto_insert_user`;
CREATE TABLE IF NOT EXISTS `auto_insert_user` (
  `seminar_id` char(32) NOT NULL,
  `user_id` char(32) NOT NULL,
  `mkdate` int(10) unsigned NOT NULL DEFAULT '0'
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
  `sorting` text NOT NULL
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
  `chdate` int(20) NOT NULL DEFAULT '0'
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
  `external_contact` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `blubber_events_queue`
--

DROP TABLE IF EXISTS `blubber_events_queue`;
CREATE TABLE IF NOT EXISTS `blubber_events_queue` (
  `event_type` varchar(32) NOT NULL,
  `item_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL
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
  `mkdate` bigint(20) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `blubber_follower`
--

DROP TABLE IF EXISTS `blubber_follower`;
CREATE TABLE IF NOT EXISTS `blubber_follower` (
  `studip_user_id` varchar(32) NOT NULL,
  `external_contact_id` varchar(32) NOT NULL,
  `left_follows_right` tinyint(1) NOT NULL
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
  `mkdate` int(11) NOT NULL
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
  `chdate` int(11) NOT NULL
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
  `mkdate` bigint(20) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `blubber_tags`
--

DROP TABLE IF EXISTS `blubber_tags`;
CREATE TABLE IF NOT EXISTS `blubber_tags` (
  `topic_id` varchar(32) NOT NULL,
  `tag` varchar(128) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_event`
--

DROP TABLE IF EXISTS `calendar_event`;
CREATE TABLE IF NOT EXISTS `calendar_event` (
  `range_id` varchar(32) NOT NULL,
  `event_id` varchar(32) NOT NULL,
  `group_status` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `mkdate` int(11) NOT NULL,
  `chdate` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_user`
--

DROP TABLE IF EXISTS `calendar_user`;
CREATE TABLE IF NOT EXISTS `calendar_user` (
  `owner_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `permission` int(2) NOT NULL,
  `mkdate` int(11) NOT NULL,
  `chdate` int(11) NOT NULL
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
  `chdate` int(20) NOT NULL DEFAULT '0'
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
  `chdate` int(11) NOT NULL DEFAULT '0'
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
  `message_template` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

DROP TABLE IF EXISTS `contact`;
CREATE TABLE IF NOT EXISTS `contact` (
  `owner_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `coursememberadmissions`
--

DROP TABLE IF EXISTS `coursememberadmissions`;
CREATE TABLE IF NOT EXISTS `coursememberadmissions` (
  `rule_id` varchar(32) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `start_time` int(11) NOT NULL DEFAULT '0',
  `end_time` int(11) NOT NULL DEFAULT '0',
  `course_id` varchar(32) NOT NULL DEFAULT '',
  `modus` tinyint(1) NOT NULL DEFAULT '0',
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0'
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
  `chdate` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `courseset_factorlist`
--

DROP TABLE IF EXISTS `courseset_factorlist`;
CREATE TABLE IF NOT EXISTS `courseset_factorlist` (
  `set_id` varchar(32) NOT NULL,
  `factorlist_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0'
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
  `chdate` int(11) DEFAULT NULL
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
  `mkdate` int(11) DEFAULT NULL
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
  `duration` float NOT NULL
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
  `chdate` int(11) unsigned NOT NULL
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
  `assigned_count` int(11) unsigned NOT NULL DEFAULT '0'
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
  `type` enum('bool','textline','textarea','selectbox','date','time','email','phone','radio','combo','link','selectboxmultiple') NOT NULL DEFAULT 'textline',
  `typeparam` text NOT NULL,
  `is_required` tinyint(4) NOT NULL DEFAULT '0',
  `is_userfilter` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `description` text NOT NULL
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
  `sec_range_id` varchar(32) NOT NULL DEFAULT ''
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
  `edit_about` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `doc_filetype`
--

DROP TABLE IF EXISTS `doc_filetype`;
CREATE TABLE IF NOT EXISTS `doc_filetype` (
  `id` int(11) NOT NULL,
  `type` varchar(45) NOT NULL,
  `description` text
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `doc_filetype_forbidden`
--

DROP TABLE IF EXISTS `doc_filetype_forbidden`;
CREATE TABLE IF NOT EXISTS `doc_filetype_forbidden` (
  `id` int(11) NOT NULL,
  `usergroup` varchar(45) NOT NULL,
  `dateityp_id` int(11) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `doc_usergroup_config`
--

DROP TABLE IF EXISTS `doc_usergroup_config`;
CREATE TABLE IF NOT EXISTS `doc_usergroup_config` (
  `id` int(11) NOT NULL,
  `usergroup` varchar(45) NOT NULL,
  `upload_quota` text NOT NULL,
  `upload_unit` varchar(45) DEFAULT NULL,
  `quota` text,
  `quota_unit` varchar(45) DEFAULT NULL,
  `upload_forbidden` int(11) NOT NULL DEFAULT '0',
  `area_close` int(11) NOT NULL DEFAULT '0',
  `area_close_text` text,
  `is_group_config` int(11) NOT NULL DEFAULT '0'
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
  `author_name` varchar(255) NOT NULL DEFAULT ''
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
  `shared` tinyint(1) NOT NULL DEFAULT '0'
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
  `residual` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `evalanswer_user`
--

DROP TABLE IF EXISTS `evalanswer_user`;
CREATE TABLE IF NOT EXISTS `evalanswer_user` (
  `evalanswer_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT ''
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
  `template_id` varchar(32) NOT NULL DEFAULT ''
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
  `multiplechoice` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `eval_group_template`
--

DROP TABLE IF EXISTS `eval_group_template`;
CREATE TABLE IF NOT EXISTS `eval_group_template` (
  `evalgroup_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `group_type` varchar(250) NOT NULL DEFAULT 'normal'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `eval_range`
--

DROP TABLE IF EXISTS `eval_range`;
CREATE TABLE IF NOT EXISTS `eval_range` (
  `eval_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT ''
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
  `kurzbeschreibung` varchar(255) DEFAULT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `eval_templates_eval`
--

DROP TABLE IF EXISTS `eval_templates_eval`;
CREATE TABLE IF NOT EXISTS `eval_templates_eval` (
  `eval_id` varchar(32) NOT NULL DEFAULT '',
  `template_id` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `eval_templates_user`
--

DROP TABLE IF EXISTS `eval_templates_user`;
CREATE TABLE IF NOT EXISTS `eval_templates_user` (
  `eval_id` varchar(32) NOT NULL DEFAULT '',
  `template_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `eval_user`
--

DROP TABLE IF EXISTS `eval_user`;
CREATE TABLE IF NOT EXISTS `eval_user` (
  `eval_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `event_data`
--

DROP TABLE IF EXISTS `event_data`;
CREATE TABLE IF NOT EXISTS `event_data` (
  `event_id` varchar(32) NOT NULL,
  `author_id` varchar(32) NOT NULL,
  `editor_id` varchar(32) DEFAULT NULL,
  `uid` varchar(255) NOT NULL,
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
  `importdate` int(11) NOT NULL DEFAULT '0'
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
  `chdate` int(20) NOT NULL DEFAULT '0'
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
  `resource_id` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
CREATE TABLE IF NOT EXISTS `files` (
  `file_id` char(32) NOT NULL,
  `user_id` char(32) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `mime_type` varchar(64) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `restricted` tinyint(1) NOT NULL DEFAULT '0',
  `storage` varchar(32) NOT NULL DEFAULT 'DiskFileStorage',
  `storage_id` varchar(32) NOT NULL,
  `mkdate` int(11) unsigned NOT NULL DEFAULT '0',
  `chdate` int(11) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `files_backend_studip`
--

DROP TABLE IF EXISTS `files_backend_studip`;
CREATE TABLE IF NOT EXISTS `files_backend_studip` (
  `id` int(10) unsigned NOT NULL,
  `files_id` varchar(64) NOT NULL,
  `path` varchar(256) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `files_backend_url`
--

DROP TABLE IF EXISTS `files_backend_url`;
CREATE TABLE IF NOT EXISTS `files_backend_url` (
  `id` int(10) unsigned NOT NULL,
  `files_id` varchar(64) NOT NULL,
  `url` varchar(256) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `files_share`
--

DROP TABLE IF EXISTS `files_share`;
CREATE TABLE IF NOT EXISTS `files_share` (
  `files_id` varchar(64) NOT NULL,
  `entity_id` varchar(32) NOT NULL,
  `description` mediumtext,
  `read_perm` tinyint(1) DEFAULT '0',
  `write_perm` tinyint(1) DEFAULT '0',
  `start_date` int(10) unsigned NOT NULL,
  `end_date` int(10) unsigned NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `file_refs`
--

DROP TABLE IF EXISTS `file_refs`;
CREATE TABLE IF NOT EXISTS `file_refs` (
  `id` char(32) NOT NULL,
  `file_id` char(32) NOT NULL,
  `parent_id` char(32) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `downloads` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

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
  `priority` smallint(5) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `forum_abo_users`
--

DROP TABLE IF EXISTS `forum_abo_users`;
CREATE TABLE IF NOT EXISTS `forum_abo_users` (
  `topic_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL
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
  `pos` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `forum_categories_entries`
--

DROP TABLE IF EXISTS `forum_categories_entries`;
CREATE TABLE IF NOT EXISTS `forum_categories_entries` (
  `category_id` varchar(32) NOT NULL,
  `topic_id` varchar(32) NOT NULL,
  `pos` int(11) NOT NULL DEFAULT '0'
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
  `sticky` int(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `forum_entries_issues`
--

DROP TABLE IF EXISTS `forum_entries_issues`;
CREATE TABLE IF NOT EXISTS `forum_entries_issues` (
  `topic_id` varchar(32) NOT NULL,
  `issue_id` varchar(32) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `forum_favorites`
--

DROP TABLE IF EXISTS `forum_favorites`;
CREATE TABLE IF NOT EXISTS `forum_favorites` (
  `user_id` varchar(32) NOT NULL,
  `topic_id` varchar(32) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `forum_likes`
--

DROP TABLE IF EXISTS `forum_likes`;
CREATE TABLE IF NOT EXISTS `forum_likes` (
  `topic_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL
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
  `last_visitdate` int(11) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `help_content`
--

DROP TABLE IF EXISTS `help_content`;
CREATE TABLE IF NOT EXISTS `help_content` (
  `global_content_id` varchar(32) NOT NULL,
  `content_id` char(32) NOT NULL,
  `language` char(2) NOT NULL DEFAULT 'de',
  `content` text NOT NULL,
  `route` varchar(255) NOT NULL,
  `studip_version` varchar(32) NOT NULL,
  `position` tinyint(4) NOT NULL DEFAULT '1',
  `custom` tinyint(4) NOT NULL DEFAULT '0',
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `author_email` varchar(255) NOT NULL,
  `installation_id` varchar(255) NOT NULL,
  `mkdate` int(11) unsigned NOT NULL,
  `chdate` int(11) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `help_tours`
--

DROP TABLE IF EXISTS `help_tours`;
CREATE TABLE IF NOT EXISTS `help_tours` (
  `global_tour_id` varchar(32) NOT NULL,
  `tour_id` char(32) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type` enum('tour','wizard') NOT NULL,
  `roles` varchar(255) NOT NULL,
  `version` int(11) unsigned NOT NULL DEFAULT '1',
  `language` char(2) NOT NULL DEFAULT 'de',
  `studip_version` varchar(32) NOT NULL DEFAULT '',
  `installation_id` varchar(255) NOT NULL DEFAULT 'demo-installation',
  `author_email` varchar(255) NOT NULL,
  `mkdate` int(11) unsigned NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `help_tour_audiences`
--

DROP TABLE IF EXISTS `help_tour_audiences`;
CREATE TABLE IF NOT EXISTS `help_tour_audiences` (
  `tour_id` char(32) NOT NULL,
  `range_id` char(32) NOT NULL,
  `type` enum('inst','sem','studiengang','abschluss','userdomain','tour') NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `help_tour_settings`
--

DROP TABLE IF EXISTS `help_tour_settings`;
CREATE TABLE IF NOT EXISTS `help_tour_settings` (
  `tour_id` varchar(32) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `access` enum('standard','link','autostart','autostart_once') DEFAULT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `help_tour_steps`
--

DROP TABLE IF EXISTS `help_tour_steps`;
CREATE TABLE IF NOT EXISTS `help_tour_steps` (
  `tour_id` char(32) NOT NULL DEFAULT '',
  `step` tinyint(4) NOT NULL DEFAULT '1',
  `title` varchar(255) NOT NULL DEFAULT '',
  `tip` text NOT NULL,
  `orientation` enum('T','TL','TR','L','LT','LB','B','BL','BR','R','RT','RB') NOT NULL DEFAULT 'B',
  `interactive` tinyint(4) NOT NULL,
  `css_selector` varchar(255) NOT NULL,
  `route` varchar(255) NOT NULL DEFAULT '',
  `action_prev` varchar(255) NOT NULL,
  `action_next` varchar(255) NOT NULL,
  `author_email` varchar(255) NOT NULL,
  `mkdate` int(11) unsigned NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `help_tour_user`
--

DROP TABLE IF EXISTS `help_tour_user`;
CREATE TABLE IF NOT EXISTS `help_tour_user` (
  `tour_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `step_nr` int(11) NOT NULL,
  `completed` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

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
  `lock_rule` varchar(32) NOT NULL DEFAULT ''
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
  `priority` int(11) NOT NULL DEFAULT '0'
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
  `chdate` int(11) NOT NULL DEFAULT '0'
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
  `dc_rights` varchar(255) DEFAULT NULL
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
  `visibility` tinyint(4) NOT NULL DEFAULT '0'
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
  `priority` smallint(6) NOT NULL DEFAULT '0'
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
  `chdate` int(11) NOT NULL DEFAULT '0'
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
  `user_id` varchar(32) NOT NULL DEFAULT ''
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
  `type` enum('core','plugin','file') DEFAULT NULL
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
  `mkdate` int(20) NOT NULL DEFAULT '0'
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
  `chdate` bigint(20) NOT NULL
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
  `expires` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
CREATE TABLE IF NOT EXISTS `message` (
  `message_id` varchar(32) NOT NULL DEFAULT '',
  `autor_id` varchar(32) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `mkdate` int(20) NOT NULL DEFAULT '0',
  `priority` enum('normal','high') NOT NULL DEFAULT 'normal'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `message_tags`
--

DROP TABLE IF EXISTS `message_tags`;
CREATE TABLE IF NOT EXISTS `message_tags` (
  `message_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `tag` varchar(64) NOT NULL,
  `chdate` bigint(20) NOT NULL,
  `mkdate` bigint(20) NOT NULL
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
  `answered` tinyint(1) NOT NULL DEFAULT '0',
  `mkdate` int(10) unsigned NOT NULL DEFAULT '0'
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
  `mkdate` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `news_range`
--

DROP TABLE IF EXISTS `news_range`;
CREATE TABLE IF NOT EXISTS `news_range` (
  `news_id` varchar(32) NOT NULL DEFAULT '',
  `range_id` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `news_rss_range`
--

DROP TABLE IF EXISTS `news_rss_range`;
CREATE TABLE IF NOT EXISTS `news_rss_range` (
  `range_id` char(32) NOT NULL DEFAULT '',
  `rss_id` char(32) NOT NULL DEFAULT '',
  `range_type` enum('user','sem','inst','global') NOT NULL DEFAULT 'user'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_consumer_registry`
--

DROP TABLE IF EXISTS `oauth_consumer_registry`;
CREATE TABLE IF NOT EXISTS `oauth_consumer_registry` (
  `ocr_id` int(11) NOT NULL,
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
  `ocr_timestamp` timestamp NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_consumer_token`
--

DROP TABLE IF EXISTS `oauth_consumer_token`;
CREATE TABLE IF NOT EXISTS `oauth_consumer_token` (
  `oct_id` int(11) NOT NULL,
  `oct_ocr_id_ref` int(11) NOT NULL,
  `oct_usa_id_ref` int(11) NOT NULL,
  `oct_name` varchar(64) binary NOT NULL DEFAULT '',
  `oct_token` varchar(128) binary NOT NULL,
  `oct_token_secret` varchar(128) binary NOT NULL,
  `oct_token_type` enum('request','authorized','access') DEFAULT NULL,
  `oct_token_ttl` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `oct_timestamp` timestamp NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_log`
--

DROP TABLE IF EXISTS `oauth_log`;
CREATE TABLE IF NOT EXISTS `oauth_log` (
  `olg_id` int(11) NOT NULL,
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
  `olg_remote_ip` bigint(20) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_server_nonce`
--

DROP TABLE IF EXISTS `oauth_server_nonce`;
CREATE TABLE IF NOT EXISTS `oauth_server_nonce` (
  `osn_id` int(11) NOT NULL,
  `osn_consumer_key` varchar(64) binary NOT NULL,
  `osn_token` varchar(64) binary NOT NULL,
  `osn_timestamp` bigint(20) NOT NULL,
  `osn_nonce` varchar(80) binary NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_server_registry`
--

DROP TABLE IF EXISTS `oauth_server_registry`;
CREATE TABLE IF NOT EXISTS `oauth_server_registry` (
  `osr_id` int(11) NOT NULL,
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
  `osr_timestamp` timestamp NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_server_token`
--

DROP TABLE IF EXISTS `oauth_server_token`;
CREATE TABLE IF NOT EXISTS `oauth_server_token` (
  `ost_id` int(11) NOT NULL,
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
  `ost_callback_url` varchar(512) DEFAULT NULL
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
  `chdate` int(20) NOT NULL DEFAULT '0'
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
  `last_visitdate` int(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `object_views`
--

DROP TABLE IF EXISTS `object_views`;
CREATE TABLE IF NOT EXISTS `object_views` (
  `object_id` varchar(32) NOT NULL DEFAULT '',
  `views` int(20) NOT NULL DEFAULT '0',
  `chdate` int(20) NOT NULL DEFAULT '0'
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
  `mkdate` bigint(20) NOT NULL
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
  `chdate` int(11) NOT NULL DEFAULT '0'
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
  `chdate` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `personal_notifications`
--

DROP TABLE IF EXISTS `personal_notifications`;
CREATE TABLE IF NOT EXISTS `personal_notifications` (
  `personal_notification_id` int(11) NOT NULL,
  `url` varchar(512) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `avatar` varchar(256) NOT NULL DEFAULT '',
  `html_id` varchar(64) NOT NULL DEFAULT '',
  `mkdate` int(11) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `personal_notifications_user`
--

DROP TABLE IF EXISTS `personal_notifications_user`;
CREATE TABLE IF NOT EXISTS `personal_notifications_user` (
  `personal_notification_id` int(10) unsigned NOT NULL,
  `user_id` binary(32) NOT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `plugins`
--

DROP TABLE IF EXISTS `plugins`;
CREATE TABLE IF NOT EXISTS `plugins` (
  `pluginid` int(10) unsigned NOT NULL,
  `pluginclassname` varchar(255) NOT NULL DEFAULT '',
  `pluginpath` varchar(255) NOT NULL DEFAULT '',
  `pluginname` varchar(45) NOT NULL DEFAULT '',
  `plugintype` text NOT NULL,
  `enabled` enum('yes','no') NOT NULL DEFAULT 'no',
  `navigationpos` int(10) unsigned NOT NULL DEFAULT '0',
  `dependentonid` int(10) unsigned DEFAULT NULL,
  `automatic_update_url` varchar(256) DEFAULT NULL,
  `automatic_update_secret` varchar(32) DEFAULT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `plugins_activated`
--

DROP TABLE IF EXISTS `plugins_activated`;
CREATE TABLE IF NOT EXISTS `plugins_activated` (
  `pluginid` int(10) unsigned NOT NULL DEFAULT '0',
  `poiid` varchar(36) NOT NULL DEFAULT '',
  `state` enum('on','off') NOT NULL DEFAULT 'on'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `plugins_default_activations`
--

DROP TABLE IF EXISTS `plugins_default_activations`;
CREATE TABLE IF NOT EXISTS `plugins_default_activations` (
  `pluginid` int(10) unsigned NOT NULL DEFAULT '0',
  `institutid` varchar(32) NOT NULL DEFAULT ''
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
  `chdate` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

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
  `studip_object_id` varchar(32) DEFAULT NULL
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
  `comment_internal` text
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
  `iconnr` int(3) DEFAULT '1'
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
  `system` tinyint(4) NOT NULL DEFAULT '0'
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
  `type` varchar(15) NOT NULL DEFAULT ''
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
  `chdate` int(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_objects_properties`
--

DROP TABLE IF EXISTS `resources_objects_properties`;
CREATE TABLE IF NOT EXISTS `resources_objects_properties` (
  `resource_id` varchar(32) NOT NULL DEFAULT '',
  `property_id` varchar(32) NOT NULL DEFAULT '',
  `state` text NOT NULL
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
  `system` tinyint(4) NOT NULL DEFAULT '0'
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
  `last_modified_by` varchar(32) NOT NULL DEFAULT '',
  `resource_id` varchar(32) NOT NULL DEFAULT '',
  `category_id` varchar(32) NOT NULL DEFAULT '',
  `comment` text,
  `reply_comment` text,
  `reply_recipients` enum('requester','lecturer') NOT NULL DEFAULT 'requester',
  `closed` tinyint(3) unsigned DEFAULT NULL,
  `mkdate` int(20) unsigned DEFAULT NULL,
  `chdate` int(20) unsigned DEFAULT NULL
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
  `chdate` int(20) unsigned DEFAULT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_requests_user_status`
--

DROP TABLE IF EXISTS `resources_requests_user_status`;
CREATE TABLE IF NOT EXISTS `resources_requests_user_status` (
  `request_id` char(32) NOT NULL DEFAULT '',
  `user_id` char(32) NOT NULL DEFAULT '',
  `mkdate` int(10) unsigned NOT NULL DEFAULT '0'
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
  `mkdate` int(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `resources_user_resources`
--

DROP TABLE IF EXISTS `resources_user_resources`;
CREATE TABLE IF NOT EXISTS `resources_user_resources` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `resource_id` varchar(32) NOT NULL DEFAULT '',
  `perms` varchar(10) NOT NULL DEFAULT ''
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `roleid` int(10) unsigned NOT NULL,
  `rolename` varchar(80) NOT NULL DEFAULT '',
  `system` enum('y','n') NOT NULL DEFAULT 'n'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `roles_plugins`
--

DROP TABLE IF EXISTS `roles_plugins`;
CREATE TABLE IF NOT EXISTS `roles_plugins` (
  `roleid` int(10) unsigned NOT NULL DEFAULT '0',
  `pluginid` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `roles_studipperms`
--

DROP TABLE IF EXISTS `roles_studipperms`;
CREATE TABLE IF NOT EXISTS `roles_studipperms` (
  `roleid` int(10) unsigned NOT NULL DEFAULT '0',
  `permname` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `roles_user`
--

DROP TABLE IF EXISTS `roles_user`;
CREATE TABLE IF NOT EXISTS `roles_user` (
  `roleid` int(10) unsigned NOT NULL DEFAULT '0',
  `userid` char(32) NOT NULL DEFAULT '',
  `institut_id` char(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

DROP TABLE IF EXISTS `schedule`;
CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int(11) NOT NULL,
  `start` smallint(6) NOT NULL COMMENT 'start hour and minutes',
  `end` smallint(6) NOT NULL COMMENT 'end hour and minutes',
  `day` tinyint(4) NOT NULL COMMENT 'day of week, 0-6',
  `title` varchar(255) NOT NULL,
  `content` varchar(255) NOT NULL,
  `color` varchar(7) NOT NULL COMMENT 'color, rgb in hex',
  `user_id` varchar(32) NOT NULL
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
  `color` varchar(7) DEFAULT NULL COMMENT 'color, rgb in hex'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `schema_version`
--

DROP TABLE IF EXISTS `schema_version`;
CREATE TABLE IF NOT EXISTS `schema_version` (
  `domain` varchar(255) NOT NULL DEFAULT '',
  `version` int(11) NOT NULL DEFAULT '0'
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
  `position` int(11) unsigned NOT NULL DEFAULT '0'
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
  `vorles_ende` int(20) unsigned DEFAULT NULL
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
  `ende` int(20) unsigned NOT NULL DEFAULT '0'
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
  `admission_disable_waitlist_move` tinyint(3) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_courseset`
--

DROP TABLE IF EXISTS `seminar_courseset`;
CREATE TABLE IF NOT EXISTS `seminar_courseset` (
  `set_id` varchar(32) NOT NULL,
  `seminar_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0'
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
  `chdate` int(10) unsigned NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_inst`
--

DROP TABLE IF EXISTS `seminar_inst`;
CREATE TABLE IF NOT EXISTS `seminar_inst` (
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `institut_id` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_sem_tree`
--

DROP TABLE IF EXISTS `seminar_sem_tree`;
CREATE TABLE IF NOT EXISTS `seminar_sem_tree` (
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `sem_tree_id` varchar(32) NOT NULL DEFAULT ''
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
  `bind_calendar` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `seminar_userdomains`
--

DROP TABLE IF EXISTS `seminar_userdomains`;
CREATE TABLE IF NOT EXISTS `seminar_userdomains` (
  `seminar_id` varchar(32) NOT NULL DEFAULT '',
  `userdomain_id` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `sem_classes`
--

DROP TABLE IF EXISTS `sem_classes`;
CREATE TABLE IF NOT EXISTS `sem_classes` (
  `id` int(11) NOT NULL,
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
  `chdate` bigint(20) NOT NULL
) ENGINE=MyISAM;

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
  `type` tinyint(3) unsigned NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `sem_types`
--

DROP TABLE IF EXISTS `sem_types`;
CREATE TABLE IF NOT EXISTS `sem_types` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `class` int(11) NOT NULL,
  `mkdate` bigint(20) NOT NULL,
  `chdate` bigint(20) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `session_data`
--

DROP TABLE IF EXISTS `session_data`;
CREATE TABLE IF NOT EXISTS `session_data` (
  `sid` varchar(32) NOT NULL DEFAULT '',
  `val` mediumtext NOT NULL,
  `changed` timestamp NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `siteinfo_details`
--

DROP TABLE IF EXISTS `siteinfo_details`;
CREATE TABLE IF NOT EXISTS `siteinfo_details` (
  `detail_id` smallint(5) unsigned NOT NULL,
  `rubric_id` smallint(5) unsigned NOT NULL,
  `position` tinyint(3) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `content` text NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `siteinfo_rubrics`
--

DROP TABLE IF EXISTS `siteinfo_rubrics`;
CREATE TABLE IF NOT EXISTS `siteinfo_rubrics` (
  `rubric_id` smallint(5) unsigned NOT NULL,
  `position` tinyint(3) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `smiley`
--

DROP TABLE IF EXISTS `smiley`;
CREATE TABLE IF NOT EXISTS `smiley` (
  `smiley_id` int(11) unsigned NOT NULL,
  `smiley_name` varchar(50) NOT NULL DEFAULT '',
  `smiley_width` int(11) NOT NULL DEFAULT '0',
  `smiley_height` int(11) NOT NULL DEFAULT '0',
  `short_name` varchar(50) NOT NULL DEFAULT '',
  `smiley_counter` int(11) unsigned NOT NULL DEFAULT '0',
  `short_counter` int(11) unsigned NOT NULL DEFAULT '0',
  `fav_counter` int(11) unsigned NOT NULL DEFAULT '0',
  `mkdate` int(10) unsigned DEFAULT NULL,
  `chdate` int(10) unsigned DEFAULT NULL
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
  `name_m` varchar(255) DEFAULT NULL
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
  `inherit` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=MyISAM;

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
  `chdate` int(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `studygroup_invitations`
--

DROP TABLE IF EXISTS `studygroup_invitations`;
CREATE TABLE IF NOT EXISTS `studygroup_invitations` (
  `sem_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `mkdate` int(20) NOT NULL
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
  `metadate_id` varchar(32) DEFAULT NULL
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `termin_related_groups`
--

DROP TABLE IF EXISTS `termin_related_groups`;
CREATE TABLE IF NOT EXISTS `termin_related_groups` (
  `termin_id` varchar(32) NOT NULL,
  `statusgruppe_id` varchar(45) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `termin_related_persons`
--

DROP TABLE IF EXISTS `termin_related_persons`;
CREATE TABLE IF NOT EXISTS `termin_related_persons` (
  `range_id` varchar(32) NOT NULL,
  `user_id` varchar(32) NOT NULL
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
  `chdate` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `themen_termine`
--

DROP TABLE IF EXISTS `themen_termine`;
CREATE TABLE IF NOT EXISTS `themen_termine` (
  `issue_id` varchar(32) NOT NULL DEFAULT '',
  `termin_id` varchar(32) NOT NULL DEFAULT ''
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
  `chdate` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `userdomains`
--

DROP TABLE IF EXISTS `userdomains`;
CREATE TABLE IF NOT EXISTS `userdomains` (
  `userdomain_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `userfilter`
--

DROP TABLE IF EXISTS `userfilter`;
CREATE TABLE IF NOT EXISTS `userfilter` (
  `filter_id` varchar(32) NOT NULL,
  `mkdate` int(11) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL DEFAULT '0'
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
  `chdate` int(11) NOT NULL DEFAULT '0'
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
  `chdate` int(11) DEFAULT NULL
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
  `comment` text NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_factorlist`
--

DROP TABLE IF EXISTS `user_factorlist`;
CREATE TABLE IF NOT EXISTS `user_factorlist` (
  `list_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `mkdate` int(11) DEFAULT NULL
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
  `email_forward` tinyint(4) NOT NULL DEFAULT '0',
  `smiley_favorite` varchar(255) NOT NULL DEFAULT '',
  `motto` varchar(255) NOT NULL DEFAULT '',
  `lock_rule` varchar(32) NOT NULL DEFAULT ''
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
  `visible` tinyint(3) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `user_online`
--

DROP TABLE IF EXISTS `user_online`;
CREATE TABLE IF NOT EXISTS `user_online` (
  `user_id` char(32) NOT NULL,
  `last_lifesign` int(10) unsigned NOT NULL
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
  `abschluss_id` char(32) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_token`
--

DROP TABLE IF EXISTS `user_token`;
CREATE TABLE IF NOT EXISTS `user_token` (
  `user_id` varchar(32) NOT NULL,
  `token` varchar(32) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_userdomains`
--

DROP TABLE IF EXISTS `user_userdomains`;
CREATE TABLE IF NOT EXISTS `user_userdomains` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `userdomain_id` varchar(32) NOT NULL DEFAULT ''
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
  `mkdate` int(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `user_visibility_settings`
--

DROP TABLE IF EXISTS `user_visibility_settings`;
CREATE TABLE IF NOT EXISTS `user_visibility_settings` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `visibilityid` int(32) NOT NULL,
  `parent_id` int(32) NOT NULL,
  `category` varchar(128) NOT NULL,
  `name` varchar(128) NOT NULL,
  `state` int(2) DEFAULT NULL,
  `plugin` int(11) DEFAULT NULL,
  `identifier` varchar(64) NOT NULL
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
  `namesvisibility` tinyint(1) NOT NULL DEFAULT '0'
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
  `correct` tinyint(1) DEFAULT NULL
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `voteanswers_user`
--

DROP TABLE IF EXISTS `voteanswers_user`;
CREATE TABLE IF NOT EXISTS `voteanswers_user` (
  `answer_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `votedate` int(20) DEFAULT NULL
) ENGINE=MyISAM PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `vote_user`
--

DROP TABLE IF EXISTS `vote_user`;
CREATE TABLE IF NOT EXISTS `vote_user` (
  `vote_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `votedate` int(20) DEFAULT NULL
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
  `id` int(11) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `widget_default`
--

DROP TABLE IF EXISTS `widget_default`;
CREATE TABLE IF NOT EXISTS `widget_default` (
  `pluginid` int(11) NOT NULL,
  `col` tinyint(1) NOT NULL DEFAULT '0',
  `position` tinyint(1) NOT NULL DEFAULT '0',
  `perm` enum('user','autor','tutor','dozent','admin','root') NOT NULL DEFAULT 'autor'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `widget_user`
--

DROP TABLE IF EXISTS `widget_user`;
CREATE TABLE IF NOT EXISTS `widget_user` (
  `id` int(11) NOT NULL,
  `pluginid` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  `range_id` varchar(32) NOT NULL,
  `col` tinyint(1) NOT NULL DEFAULT '0'
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
  `version` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_links`
--

DROP TABLE IF EXISTS `wiki_links`;
CREATE TABLE IF NOT EXISTS `wiki_links` (
  `range_id` char(32) NOT NULL DEFAULT '',
  `from_keyword` char(128) binary NOT NULL DEFAULT '',
  `to_keyword` char(128) binary NOT NULL DEFAULT ''
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
  `chdate` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `abschluss`
--
ALTER TABLE `abschluss`
  ADD PRIMARY KEY (`abschluss_id`);

--
-- Indexes for table `admissionfactor`
--
ALTER TABLE `admissionfactor`
  ADD PRIMARY KEY (`list_id`);

--
-- Indexes for table `admissionrules`
--
ALTER TABLE `admissionrules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ruletype` (`ruletype`);

--
-- Indexes for table `admissionrule_inst`
--
ALTER TABLE `admissionrule_inst`
  ADD PRIMARY KEY (`rule_id`,`institute_id`);

--
-- Indexes for table `admission_condition`
--
ALTER TABLE `admission_condition`
  ADD PRIMARY KEY (`rule_id`,`filter_id`);

--
-- Indexes for table `admission_seminar_user`
--
ALTER TABLE `admission_seminar_user`
  ADD PRIMARY KEY (`user_id`,`seminar_id`),
  ADD KEY `seminar_id` (`seminar_id`,`status`);

--
-- Indexes for table `api_consumers`
--
ALTER TABLE `api_consumers`
  ADD PRIMARY KEY (`consumer_id`);

--
-- Indexes for table `api_consumer_permissions`
--
ALTER TABLE `api_consumer_permissions`
  ADD UNIQUE KEY `route_id` (`route_id`,`consumer_id`,`method`);

--
-- Indexes for table `api_oauth_user_mapping`
--
ALTER TABLE `api_oauth_user_mapping`
  ADD PRIMARY KEY (`oauth_id`);

--
-- Indexes for table `api_user_permissions`
--
ALTER TABLE `api_user_permissions`
  ADD PRIMARY KEY (`user_id`,`consumer_id`);

--
-- Indexes for table `archiv`
--
ALTER TABLE `archiv`
  ADD PRIMARY KEY (`seminar_id`),
  ADD KEY `heimat_inst_id` (`heimat_inst_id`);

--
-- Indexes for table `archiv_user`
--
ALTER TABLE `archiv_user`
  ADD PRIMARY KEY (`seminar_id`,`user_id`),
  ADD KEY `user_id` (`user_id`,`status`);

--
-- Indexes for table `auth_extern`
--
ALTER TABLE `auth_extern`
  ADD PRIMARY KEY (`studip_user_id`,`external_user_system_type`);

--
-- Indexes for table `auth_user_md5`
--
ALTER TABLE `auth_user_md5`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `k_username` (`username`),
  ADD KEY `perms` (`perms`);

--
-- Indexes for table `auto_insert_sem`
--
ALTER TABLE `auto_insert_sem`
  ADD PRIMARY KEY (`seminar_id`,`status`,`domain_id`);

--
-- Indexes for table `auto_insert_user`
--
ALTER TABLE `auto_insert_user`
  ADD PRIMARY KEY (`seminar_id`,`user_id`);

--
-- Indexes for table `aux_lock_rules`
--
ALTER TABLE `aux_lock_rules`
  ADD PRIMARY KEY (`lock_id`);

--
-- Indexes for table `banner_ads`
--
ALTER TABLE `banner_ads`
  ADD PRIMARY KEY (`ad_id`);

--
-- Indexes for table `blubber`
--
ALTER TABLE `blubber`
  ADD PRIMARY KEY (`topic_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `chdate` (`chdate`),
  ADD KEY `mkdate` (`mkdate`),
  ADD KEY `user_id` (`user_id`,`Seminar_id`),
  ADD KEY `root_id` (`root_id`,`mkdate`),
  ADD KEY `Seminar_id` (`Seminar_id`,`context_type`);

--
-- Indexes for table `blubber_events_queue`
--
ALTER TABLE `blubber_events_queue`
  ADD PRIMARY KEY (`event_type`,`item_id`,`mkdate`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `blubber_external_contact`
--
ALTER TABLE `blubber_external_contact`
  ADD PRIMARY KEY (`external_contact_id`),
  ADD KEY `mail_identifier` (`mail_identifier`),
  ADD KEY `contact_type` (`contact_type`);

--
-- Indexes for table `blubber_follower`
--
ALTER TABLE `blubber_follower`
  ADD KEY `studip_user_id` (`studip_user_id`),
  ADD KEY `external_contact_id` (`external_contact_id`);

--
-- Indexes for table `blubber_mentions`
--
ALTER TABLE `blubber_mentions`
  ADD UNIQUE KEY `unique_users_per_topic` (`topic_id`,`user_id`,`external_contact`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `blubber_reshares`
--
ALTER TABLE `blubber_reshares`
  ADD UNIQUE KEY `unique_reshares` (`topic_id`,`user_id`,`external_contact`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `blubber_streams`
--
ALTER TABLE `blubber_streams`
  ADD PRIMARY KEY (`stream_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `blubber_tags`
--
ALTER TABLE `blubber_tags`
  ADD PRIMARY KEY (`topic_id`,`tag`),
  ADD KEY `tag` (`tag`);

--
-- Indexes for table `calendar_event`
--
ALTER TABLE `calendar_event`
  ADD PRIMARY KEY (`range_id`,`event_id`);

--
-- Indexes for table `calendar_user`
--
ALTER TABLE `calendar_user`
  ADD PRIMARY KEY (`owner_id`,`user_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `object_id` (`object_id`);

--
-- Indexes for table `conditionaladmissions`
--
ALTER TABLE `conditionaladmissions`
  ADD PRIMARY KEY (`rule_id`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`config_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `field` (`field`,`range`);

--
-- Indexes for table `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`owner_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `coursememberadmissions`
--
ALTER TABLE `coursememberadmissions`
  ADD PRIMARY KEY (`rule_id`);

--
-- Indexes for table `coursesets`
--
ALTER TABLE `coursesets`
  ADD PRIMARY KEY (`set_id`),
  ADD KEY `set_user` (`user_id`,`set_id`);

--
-- Indexes for table `courseset_factorlist`
--
ALTER TABLE `courseset_factorlist`
  ADD PRIMARY KEY (`set_id`,`factorlist_id`);

--
-- Indexes for table `courseset_institute`
--
ALTER TABLE `courseset_institute`
  ADD PRIMARY KEY (`set_id`,`institute_id`),
  ADD KEY `institute_id` (`institute_id`,`set_id`);

--
-- Indexes for table `courseset_rule`
--
ALTER TABLE `courseset_rule`
  ADD PRIMARY KEY (`set_id`,`rule_id`),
  ADD KEY `type` (`set_id`,`type`);

--
-- Indexes for table `cronjobs_logs`
--
ALTER TABLE `cronjobs_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `cronjobs_schedules`
--
ALTER TABLE `cronjobs_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `cronjobs_tasks`
--
ALTER TABLE `cronjobs_tasks`
  ADD PRIMARY KEY (`task_id`);

--
-- Indexes for table `datafields`
--
ALTER TABLE `datafields`
  ADD PRIMARY KEY (`datafield_id`),
  ADD KEY `object_type` (`object_type`);

--
-- Indexes for table `datafields_entries`
--
ALTER TABLE `datafields_entries`
  ADD PRIMARY KEY (`datafield_id`,`range_id`,`sec_range_id`),
  ADD KEY `range_id` (`range_id`,`datafield_id`),
  ADD KEY `datafield_id_2` (`datafield_id`,`sec_range_id`),
  ADD KEY `datafields_contents` (`datafield_id`,`content`(32));

--
-- Indexes for table `deputies`
--
ALTER TABLE `deputies`
  ADD PRIMARY KEY (`range_id`,`user_id`),
  ADD KEY `user_id` (`user_id`,`range_id`,`edit_about`);

--
-- Indexes for table `doc_filetype`
--
ALTER TABLE `doc_filetype`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doc_filetype_forbidden`
--
ALTER TABLE `doc_filetype_forbidden`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dateityp_verbot_nutzerbereich_2_idx` (`dateityp_id`),
  ADD KEY `fk_dateityp_verbot_nutzerbereich_1_idx` (`usergroup`);

--
-- Indexes for table `doc_usergroup_config`
--
ALTER TABLE `doc_usergroup_config`
  ADD PRIMARY KEY (`id`,`usergroup`);

--
-- Indexes for table `dokumente`
--
ALTER TABLE `dokumente`
  ADD PRIMARY KEY (`dokument_id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `seminar_id` (`seminar_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `chdate` (`chdate`),
  ADD KEY `mkdate` (`mkdate`);

--
-- Indexes for table `eval`
--
ALTER TABLE `eval`
  ADD PRIMARY KEY (`eval_id`);

--
-- Indexes for table `evalanswer`
--
ALTER TABLE `evalanswer`
  ADD PRIMARY KEY (`evalanswer_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `evalanswer_user`
--
ALTER TABLE `evalanswer_user`
  ADD PRIMARY KEY (`evalanswer_id`,`user_id`);

--
-- Indexes for table `evalgroup`
--
ALTER TABLE `evalgroup`
  ADD PRIMARY KEY (`evalgroup_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `evalquestion`
--
ALTER TABLE `evalquestion`
  ADD PRIMARY KEY (`evalquestion_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `eval_group_template`
--
ALTER TABLE `eval_group_template`
  ADD PRIMARY KEY (`evalgroup_id`,`user_id`);

--
-- Indexes for table `eval_range`
--
ALTER TABLE `eval_range`
  ADD PRIMARY KEY (`eval_id`,`range_id`);

--
-- Indexes for table `eval_templates`
--
ALTER TABLE `eval_templates`
  ADD PRIMARY KEY (`template_id`),
  ADD KEY `user_id` (`user_id`,`institution_id`,`name`);

--
-- Indexes for table `eval_templates_eval`
--
ALTER TABLE `eval_templates_eval`
  ADD PRIMARY KEY (`eval_id`),
  ADD KEY `eval_id` (`eval_id`);

--
-- Indexes for table `eval_templates_user`
--
ALTER TABLE `eval_templates_user`
  ADD KEY `eval_id` (`eval_id`);

--
-- Indexes for table `eval_user`
--
ALTER TABLE `eval_user`
  ADD PRIMARY KEY (`eval_id`,`user_id`);

--
-- Indexes for table `event_data`
--
ALTER TABLE `event_data`
  ADD PRIMARY KEY (`event_id`),
  ADD UNIQUE KEY `uid` (`uid`),
  ADD KEY `autor_id` (`author_id`);

--
-- Indexes for table `extern_config`
--
ALTER TABLE `extern_config`
  ADD PRIMARY KEY (`config_id`,`range_id`);

--
-- Indexes for table `ex_termine`
--
ALTER TABLE `ex_termine`
  ADD PRIMARY KEY (`termin_id`),
  ADD KEY `range_id` (`range_id`,`date`),
  ADD KEY `metadate_id` (`metadate_id`,`date`),
  ADD KEY `autor_id` (`autor_id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`file_id`);

--
-- Indexes for table `files_backend_studip`
--
ALTER TABLE `files_backend_studip`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `files_backend_url`
--
ALTER TABLE `files_backend_url`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `files_share`
--
ALTER TABLE `files_share`
  ADD PRIMARY KEY (`files_id`,`entity_id`);

--
-- Indexes for table `file_refs`
--
ALTER TABLE `file_refs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `folder`
--
ALTER TABLE `folder`
  ADD PRIMARY KEY (`folder_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `chdate` (`chdate`);

--
-- Indexes for table `forum_abo_users`
--
ALTER TABLE `forum_abo_users`
  ADD PRIMARY KEY (`topic_id`,`user_id`);

--
-- Indexes for table `forum_categories`
--
ALTER TABLE `forum_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `seminar_id` (`seminar_id`);

--
-- Indexes for table `forum_categories_entries`
--
ALTER TABLE `forum_categories_entries`
  ADD PRIMARY KEY (`category_id`,`topic_id`);

--
-- Indexes for table `forum_entries`
--
ALTER TABLE `forum_entries`
  ADD PRIMARY KEY (`topic_id`),
  ADD KEY `seminar_id` (`seminar_id`,`lft`),
  ADD KEY `seminar_id_2` (`seminar_id`,`rgt`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `forum_entries_issues`
--
ALTER TABLE `forum_entries_issues`
  ADD PRIMARY KEY (`topic_id`,`issue_id`);

--
-- Indexes for table `forum_favorites`
--
ALTER TABLE `forum_favorites`
  ADD PRIMARY KEY (`user_id`,`topic_id`);

--
-- Indexes for table `forum_likes`
--
ALTER TABLE `forum_likes`
  ADD PRIMARY KEY (`topic_id`,`user_id`);

--
-- Indexes for table `forum_visits`
--
ALTER TABLE `forum_visits`
  ADD PRIMARY KEY (`user_id`,`seminar_id`);

--
-- Indexes for table `help_content`
--
ALTER TABLE `help_content`
  ADD PRIMARY KEY (`content_id`);

--
-- Indexes for table `help_tours`
--
ALTER TABLE `help_tours`
  ADD PRIMARY KEY (`tour_id`);

--
-- Indexes for table `help_tour_audiences`
--
ALTER TABLE `help_tour_audiences`
  ADD PRIMARY KEY (`tour_id`,`range_id`,`type`);

--
-- Indexes for table `help_tour_settings`
--
ALTER TABLE `help_tour_settings`
  ADD PRIMARY KEY (`tour_id`);

--
-- Indexes for table `help_tour_steps`
--
ALTER TABLE `help_tour_steps`
  ADD PRIMARY KEY (`tour_id`,`step`);

--
-- Indexes for table `help_tour_user`
--
ALTER TABLE `help_tour_user`
  ADD PRIMARY KEY (`tour_id`,`user_id`);

--
-- Indexes for table `Institute`
--
ALTER TABLE `Institute`
  ADD PRIMARY KEY (`Institut_id`),
  ADD KEY `fakultaets_id` (`fakultaets_id`);

--
-- Indexes for table `kategorien`
--
ALTER TABLE `kategorien`
  ADD PRIMARY KEY (`kategorie_id`),
  ADD KEY `priority` (`priority`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `limitedadmissions`
--
ALTER TABLE `limitedadmissions`
  ADD PRIMARY KEY (`rule_id`);

--
-- Indexes for table `lit_catalog`
--
ALTER TABLE `lit_catalog`
  ADD PRIMARY KEY (`catalog_id`);

--
-- Indexes for table `lit_list`
--
ALTER TABLE `lit_list`
  ADD PRIMARY KEY (`list_id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `priority` (`priority`),
  ADD KEY `visibility` (`visibility`);

--
-- Indexes for table `lit_list_content`
--
ALTER TABLE `lit_list_content`
  ADD PRIMARY KEY (`list_element_id`),
  ADD KEY `list_id` (`list_id`),
  ADD KEY `catalog_id` (`catalog_id`),
  ADD KEY `priority` (`priority`);

--
-- Indexes for table `lockedadmissions`
--
ALTER TABLE `lockedadmissions`
  ADD PRIMARY KEY (`rule_id`);

--
-- Indexes for table `lock_rules`
--
ALTER TABLE `lock_rules`
  ADD PRIMARY KEY (`lock_id`);

--
-- Indexes for table `log_actions`
--
ALTER TABLE `log_actions`
  ADD PRIMARY KEY (`action_id`);

--
-- Indexes for table `log_events`
--
ALTER TABLE `log_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `action_id` (`action_id`);

--
-- Indexes for table `mail_queue_entries`
--
ALTER TABLE `mail_queue_entries`
  ADD PRIMARY KEY (`mail_queue_id`),
  ADD KEY `message_id` (`message_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `media_cache`
--
ALTER TABLE `media_cache`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `autor_id` (`autor_id`);

--
-- Indexes for table `message_tags`
--
ALTER TABLE `message_tags`
  ADD PRIMARY KEY (`message_id`,`user_id`,`tag`);

--
-- Indexes for table `message_user`
--
ALTER TABLE `message_user`
  ADD PRIMARY KEY (`message_id`,`snd_rec`,`user_id`),
  ADD KEY `user_id` (`user_id`,`snd_rec`,`deleted`,`readed`,`mkdate`),
  ADD KEY `user_id_2` (`user_id`,`snd_rec`,`deleted`,`mkdate`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`news_id`),
  ADD KEY `date` (`date`),
  ADD KEY `chdate` (`chdate`);

--
-- Indexes for table `news_range`
--
ALTER TABLE `news_range`
  ADD PRIMARY KEY (`news_id`,`range_id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `news_rss_range`
--
ALTER TABLE `news_rss_range`
  ADD PRIMARY KEY (`range_id`),
  ADD KEY `rss_id` (`rss_id`);

--
-- Indexes for table `oauth_consumer_registry`
--
ALTER TABLE `oauth_consumer_registry`
  ADD PRIMARY KEY (`ocr_id`),
  ADD UNIQUE KEY `ocr_consumer_key` (`ocr_consumer_key`,`ocr_usa_id_ref`,`ocr_server_uri`),
  ADD KEY `ocr_server_uri` (`ocr_server_uri`),
  ADD KEY `ocr_server_uri_host` (`ocr_server_uri_host`,`ocr_server_uri_path`),
  ADD KEY `ocr_usa_id_ref` (`ocr_usa_id_ref`);

--
-- Indexes for table `oauth_consumer_token`
--
ALTER TABLE `oauth_consumer_token`
  ADD PRIMARY KEY (`oct_id`),
  ADD UNIQUE KEY `oct_ocr_id_ref` (`oct_ocr_id_ref`,`oct_token`),
  ADD UNIQUE KEY `oct_usa_id_ref` (`oct_usa_id_ref`,`oct_ocr_id_ref`,`oct_token_type`,`oct_name`),
  ADD KEY `oct_token_ttl` (`oct_token_ttl`);

--
-- Indexes for table `oauth_log`
--
ALTER TABLE `oauth_log`
  ADD PRIMARY KEY (`olg_id`),
  ADD KEY `olg_osr_consumer_key` (`olg_osr_consumer_key`,`olg_id`),
  ADD KEY `olg_ost_token` (`olg_ost_token`,`olg_id`),
  ADD KEY `olg_ocr_consumer_key` (`olg_ocr_consumer_key`,`olg_id`),
  ADD KEY `olg_oct_token` (`olg_oct_token`,`olg_id`),
  ADD KEY `olg_usa_id_ref` (`olg_usa_id_ref`,`olg_id`);

--
-- Indexes for table `oauth_server_nonce`
--
ALTER TABLE `oauth_server_nonce`
  ADD PRIMARY KEY (`osn_id`),
  ADD UNIQUE KEY `osn_consumer_key` (`osn_consumer_key`,`osn_token`,`osn_timestamp`,`osn_nonce`);

--
-- Indexes for table `oauth_server_registry`
--
ALTER TABLE `oauth_server_registry`
  ADD PRIMARY KEY (`osr_id`),
  ADD UNIQUE KEY `osr_consumer_key` (`osr_consumer_key`),
  ADD KEY `osr_usa_id_ref` (`osr_usa_id_ref`);

--
-- Indexes for table `oauth_server_token`
--
ALTER TABLE `oauth_server_token`
  ADD PRIMARY KEY (`ost_id`),
  ADD UNIQUE KEY `ost_token` (`ost_token`),
  ADD KEY `ost_osr_id_ref` (`ost_osr_id_ref`),
  ADD KEY `ost_token_ttl` (`ost_token_ttl`);

--
-- Indexes for table `object_contentmodules`
--
ALTER TABLE `object_contentmodules`
  ADD PRIMARY KEY (`object_id`,`module_id`,`system_type`);

--
-- Indexes for table `object_user_visits`
--
ALTER TABLE `object_user_visits`
  ADD PRIMARY KEY (`object_id`,`user_id`,`type`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `object_views`
--
ALTER TABLE `object_views`
  ADD PRIMARY KEY (`object_id`),
  ADD KEY `views` (`views`);

--
-- Indexes for table `opengraphdata`
--
ALTER TABLE `opengraphdata`
  ADD PRIMARY KEY (`url`);

--
-- Indexes for table `participantrestrictedadmissions`
--
ALTER TABLE `participantrestrictedadmissions`
  ADD PRIMARY KEY (`rule_id`);

--
-- Indexes for table `passwordadmissions`
--
ALTER TABLE `passwordadmissions`
  ADD PRIMARY KEY (`rule_id`);

--
-- Indexes for table `personal_notifications`
--
ALTER TABLE `personal_notifications`
  ADD PRIMARY KEY (`personal_notification_id`);

--
-- Indexes for table `personal_notifications_user`
--
ALTER TABLE `personal_notifications_user`
  ADD PRIMARY KEY (`personal_notification_id`,`user_id`),
  ADD KEY `user_id` (`user_id`,`seen`);

--
-- Indexes for table `plugins`
--
ALTER TABLE `plugins`
  ADD PRIMARY KEY (`pluginid`);

--
-- Indexes for table `plugins_activated`
--
ALTER TABLE `plugins_activated`
  ADD PRIMARY KEY (`pluginid`,`poiid`),
  ADD UNIQUE KEY `poiid` (`poiid`,`pluginid`,`state`);

--
-- Indexes for table `plugins_default_activations`
--
ALTER TABLE `plugins_default_activations`
  ADD PRIMARY KEY (`pluginid`,`institutid`);

--
-- Indexes for table `priorities`
--
ALTER TABLE `priorities`
  ADD PRIMARY KEY (`user_id`,`set_id`,`seminar_id`),
  ADD KEY `user_rule_priority` (`user_id`,`priority`,`set_id`);

--
-- Indexes for table `range_tree`
--
ALTER TABLE `range_tree`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `priority` (`priority`),
  ADD KEY `studip_object_id` (`studip_object_id`);

--
-- Indexes for table `resources_assign`
--
ALTER TABLE `resources_assign`
  ADD PRIMARY KEY (`assign_id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `assign_user_id` (`assign_user_id`);

--
-- Indexes for table `resources_categories`
--
ALTER TABLE `resources_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `is_room` (`is_room`);

--
-- Indexes for table `resources_categories_properties`
--
ALTER TABLE `resources_categories_properties`
  ADD PRIMARY KEY (`category_id`,`property_id`);

--
-- Indexes for table `resources_locks`
--
ALTER TABLE `resources_locks`
  ADD PRIMARY KEY (`lock_id`);

--
-- Indexes for table `resources_objects`
--
ALTER TABLE `resources_objects`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `institut_id` (`institut_id`),
  ADD KEY `root_id` (`root_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `resources_objects_properties`
--
ALTER TABLE `resources_objects_properties`
  ADD PRIMARY KEY (`resource_id`,`property_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `resources_properties`
--
ALTER TABLE `resources_properties`
  ADD PRIMARY KEY (`property_id`);

--
-- Indexes for table `resources_requests`
--
ALTER TABLE `resources_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `termin_id` (`termin_id`),
  ADD KEY `seminar_id` (`seminar_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `closed` (`closed`,`request_id`,`resource_id`),
  ADD KEY `metadate_id` (`metadate_id`);

--
-- Indexes for table `resources_requests_properties`
--
ALTER TABLE `resources_requests_properties`
  ADD PRIMARY KEY (`request_id`,`property_id`);

--
-- Indexes for table `resources_requests_user_status`
--
ALTER TABLE `resources_requests_user_status`
  ADD PRIMARY KEY (`request_id`,`user_id`);

--
-- Indexes for table `resources_temporary_events`
--
ALTER TABLE `resources_temporary_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `resource_id` (`resource_id`,`begin`),
  ADD KEY `assign_object_id` (`assign_id`,`resource_id`);

--
-- Indexes for table `resources_user_resources`
--
ALTER TABLE `resources_user_resources`
  ADD PRIMARY KEY (`user_id`,`resource_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`roleid`);

--
-- Indexes for table `roles_plugins`
--
ALTER TABLE `roles_plugins`
  ADD PRIMARY KEY (`roleid`,`pluginid`);

--
-- Indexes for table `roles_studipperms`
--
ALTER TABLE `roles_studipperms`
  ADD PRIMARY KEY (`roleid`,`permname`);

--
-- Indexes for table `roles_user`
--
ALTER TABLE `roles_user`
  ADD PRIMARY KEY (`roleid`,`userid`,`institut_id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `schedule_seminare`
--
ALTER TABLE `schedule_seminare`
  ADD PRIMARY KEY (`user_id`,`seminar_id`,`metadate_id`);

--
-- Indexes for table `schema_version`
--
ALTER TABLE `schema_version`
  ADD PRIMARY KEY (`domain`);

--
-- Indexes for table `scm`
--
ALTER TABLE `scm`
  ADD PRIMARY KEY (`scm_id`),
  ADD KEY `chdate` (`chdate`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `semester_data`
--
ALTER TABLE `semester_data`
  ADD PRIMARY KEY (`semester_id`);

--
-- Indexes for table `semester_holiday`
--
ALTER TABLE `semester_holiday`
  ADD PRIMARY KEY (`holiday_id`);

--
-- Indexes for table `seminare`
--
ALTER TABLE `seminare`
  ADD PRIMARY KEY (`Seminar_id`),
  ADD KEY `Institut_id` (`Institut_id`),
  ADD KEY `visible` (`visible`),
  ADD KEY `status` (`status`,`Seminar_id`);

--
-- Indexes for table `seminar_courseset`
--
ALTER TABLE `seminar_courseset`
  ADD PRIMARY KEY (`set_id`,`seminar_id`),
  ADD KEY `seminar_id` (`seminar_id`,`set_id`);

--
-- Indexes for table `seminar_cycle_dates`
--
ALTER TABLE `seminar_cycle_dates`
  ADD PRIMARY KEY (`metadate_id`),
  ADD KEY `seminar_id` (`seminar_id`);

--
-- Indexes for table `seminar_inst`
--
ALTER TABLE `seminar_inst`
  ADD PRIMARY KEY (`seminar_id`,`institut_id`),
  ADD KEY `institut_id` (`institut_id`);

--
-- Indexes for table `seminar_sem_tree`
--
ALTER TABLE `seminar_sem_tree`
  ADD PRIMARY KEY (`seminar_id`,`sem_tree_id`),
  ADD KEY `sem_tree_id` (`sem_tree_id`);

--
-- Indexes for table `seminar_user`
--
ALTER TABLE `seminar_user`
  ADD PRIMARY KEY (`Seminar_id`,`user_id`),
  ADD KEY `status` (`status`,`Seminar_id`),
  ADD KEY `user_id` (`user_id`,`Seminar_id`,`status`);

--
-- Indexes for table `seminar_userdomains`
--
ALTER TABLE `seminar_userdomains`
  ADD PRIMARY KEY (`seminar_id`,`userdomain_id`);

--
-- Indexes for table `sem_classes`
--
ALTER TABLE `sem_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `sem_tree`
--
ALTER TABLE `sem_tree`
  ADD PRIMARY KEY (`sem_tree_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `priority` (`priority`),
  ADD KEY `studip_object_id` (`studip_object_id`);

--
-- Indexes for table `sem_types`
--
ALTER TABLE `sem_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `session_data`
--
ALTER TABLE `session_data`
  ADD PRIMARY KEY (`sid`),
  ADD KEY `changed` (`changed`);

--
-- Indexes for table `siteinfo_details`
--
ALTER TABLE `siteinfo_details`
  ADD PRIMARY KEY (`detail_id`);

--
-- Indexes for table `siteinfo_rubrics`
--
ALTER TABLE `siteinfo_rubrics`
  ADD PRIMARY KEY (`rubric_id`);

--
-- Indexes for table `smiley`
--
ALTER TABLE `smiley`
  ADD PRIMARY KEY (`smiley_id`),
  ADD UNIQUE KEY `name` (`smiley_name`),
  ADD KEY `short` (`short_name`);

--
-- Indexes for table `statusgruppen`
--
ALTER TABLE `statusgruppen`
  ADD PRIMARY KEY (`statusgruppe_id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `position` (`position`);

--
-- Indexes for table `statusgruppe_user`
--
ALTER TABLE `statusgruppe_user`
  ADD PRIMARY KEY (`statusgruppe_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `studiengaenge`
--
ALTER TABLE `studiengaenge`
  ADD PRIMARY KEY (`studiengang_id`);

--
-- Indexes for table `studygroup_invitations`
--
ALTER TABLE `studygroup_invitations`
  ADD PRIMARY KEY (`sem_id`,`user_id`);

--
-- Indexes for table `termine`
--
ALTER TABLE `termine`
  ADD PRIMARY KEY (`termin_id`),
  ADD KEY `metadate_id` (`metadate_id`,`date`),
  ADD KEY `range_id` (`range_id`,`date`);

--
-- Indexes for table `termin_related_groups`
--
ALTER TABLE `termin_related_groups`
  ADD UNIQUE KEY `unique` (`termin_id`,`statusgruppe_id`),
  ADD KEY `termin_id` (`termin_id`),
  ADD KEY `statusgruppe_id` (`statusgruppe_id`);

--
-- Indexes for table `termin_related_persons`
--
ALTER TABLE `termin_related_persons`
  ADD PRIMARY KEY (`range_id`,`user_id`);

--
-- Indexes for table `themen`
--
ALTER TABLE `themen`
  ADD PRIMARY KEY (`issue_id`),
  ADD KEY `seminar_id` (`seminar_id`,`priority`);

--
-- Indexes for table `themen_termine`
--
ALTER TABLE `themen_termine`
  ADD PRIMARY KEY (`issue_id`,`termin_id`),
  ADD KEY `termin_id` (`termin_id`,`issue_id`);

--
-- Indexes for table `timedadmissions`
--
ALTER TABLE `timedadmissions`
  ADD PRIMARY KEY (`rule_id`),
  ADD KEY `start_time` (`start_time`),
  ADD KEY `end_time` (`end_time`),
  ADD KEY `start_end` (`start_time`,`end_time`);

--
-- Indexes for table `userdomains`
--
ALTER TABLE `userdomains`
  ADD PRIMARY KEY (`userdomain_id`);

--
-- Indexes for table `userfilter`
--
ALTER TABLE `userfilter`
  ADD PRIMARY KEY (`filter_id`);

--
-- Indexes for table `userfilter_fields`
--
ALTER TABLE `userfilter_fields`
  ADD PRIMARY KEY (`field_id`);

--
-- Indexes for table `userlimits`
--
ALTER TABLE `userlimits`
  ADD PRIMARY KEY (`rule_id`,`user_id`);

--
-- Indexes for table `user_config`
--
ALTER TABLE `user_config`
  ADD PRIMARY KEY (`userconfig_id`),
  ADD KEY `user_id` (`user_id`,`field`,`value`(5));

--
-- Indexes for table `user_factorlist`
--
ALTER TABLE `user_factorlist`
  ADD PRIMARY KEY (`list_id`,`user_id`);

--
-- Indexes for table `user_info`
--
ALTER TABLE `user_info`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `score` (`score`);

--
-- Indexes for table `user_inst`
--
ALTER TABLE `user_inst`
  ADD PRIMARY KEY (`Institut_id`,`user_id`),
  ADD KEY `inst_perms` (`inst_perms`,`Institut_id`),
  ADD KEY `user_id` (`user_id`,`inst_perms`);

--
-- Indexes for table `user_online`
--
ALTER TABLE `user_online`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `last_lifesign` (`last_lifesign`);

--
-- Indexes for table `user_studiengang`
--
ALTER TABLE `user_studiengang`
  ADD PRIMARY KEY (`user_id`,`studiengang_id`,`abschluss_id`),
  ADD KEY `studiengang_id` (`studiengang_id`);

--
-- Indexes for table `user_token`
--
ALTER TABLE `user_token`
  ADD PRIMARY KEY (`user_id`,`token`,`expiration`),
  ADD KEY `index_expiration` (`expiration`),
  ADD KEY `index_token` (`token`),
  ADD KEY `index_user_id` (`user_id`);

--
-- Indexes for table `user_userdomains`
--
ALTER TABLE `user_userdomains`
  ADD PRIMARY KEY (`user_id`,`userdomain_id`);

--
-- Indexes for table `user_visibility`
--
ALTER TABLE `user_visibility`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_visibility_settings`
--
ALTER TABLE `user_visibility_settings`
  ADD PRIMARY KEY (`visibilityid`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `identifier` (`identifier`),
  ADD KEY `userid` (`user_id`);

--
-- Indexes for table `vote`
--
ALTER TABLE `vote`
  ADD PRIMARY KEY (`vote_id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `state` (`state`),
  ADD KEY `startdate` (`startdate`),
  ADD KEY `stopdate` (`stopdate`),
  ADD KEY `resultvisibility` (`resultvisibility`),
  ADD KEY `chdate` (`chdate`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `voteanswers`
--
ALTER TABLE `voteanswers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `vote_id` (`vote_id`),
  ADD KEY `position` (`position`);

--
-- Indexes for table `voteanswers_user`
--
ALTER TABLE `voteanswers_user`
  ADD PRIMARY KEY (`answer_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `vote_user`
--
ALTER TABLE `vote_user`
  ADD PRIMARY KEY (`vote_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `webservice_access_rules`
--
ALTER TABLE `webservice_access_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `widget_default`
--
ALTER TABLE `widget_default`
  ADD PRIMARY KEY (`perm`,`pluginid`);

--
-- Indexes for table `widget_user`
--
ALTER TABLE `widget_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `wiki`
--
ALTER TABLE `wiki`
  ADD PRIMARY KEY (`range_id`,`keyword`,`version`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `chdate` (`chdate`);

--
-- Indexes for table `wiki_links`
--
ALTER TABLE `wiki_links`
  ADD PRIMARY KEY (`range_id`,`to_keyword`,`from_keyword`);

--
-- Indexes for table `wiki_locks`
--
ALTER TABLE `wiki_locks`
  ADD PRIMARY KEY (`range_id`,`user_id`,`keyword`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `chdate` (`chdate`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admissionrules`
--
ALTER TABLE `admissionrules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `api_oauth_user_mapping`
--
ALTER TABLE `api_oauth_user_mapping`
  MODIFY `oauth_id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `doc_filetype`
--
ALTER TABLE `doc_filetype`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `doc_filetype_forbidden`
--
ALTER TABLE `doc_filetype_forbidden`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `doc_usergroup_config`
--
ALTER TABLE `doc_usergroup_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `oauth_consumer_registry`
--
ALTER TABLE `oauth_consumer_registry`
  MODIFY `ocr_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `oauth_consumer_token`
--
ALTER TABLE `oauth_consumer_token`
  MODIFY `oct_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `oauth_log`
--
ALTER TABLE `oauth_log`
  MODIFY `olg_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `oauth_server_nonce`
--
ALTER TABLE `oauth_server_nonce`
  MODIFY `osn_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `oauth_server_registry`
--
ALTER TABLE `oauth_server_registry`
  MODIFY `osr_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `oauth_server_token`
--
ALTER TABLE `oauth_server_token`
  MODIFY `ost_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `personal_notifications`
--
ALTER TABLE `personal_notifications`
  MODIFY `personal_notification_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `plugins`
--
ALTER TABLE `plugins`
  MODIFY `pluginid` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `roleid` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `sem_classes`
--
ALTER TABLE `sem_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `sem_types`
--
ALTER TABLE `sem_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `siteinfo_details`
--
ALTER TABLE `siteinfo_details`
  MODIFY `detail_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `siteinfo_rubrics`
--
ALTER TABLE `siteinfo_rubrics`
  MODIFY `rubric_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `smiley`
--
ALTER TABLE `smiley`
  MODIFY `smiley_id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_visibility_settings`
--
ALTER TABLE `user_visibility_settings`
  MODIFY `visibilityid` int(32) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `webservice_access_rules`
--
ALTER TABLE `webservice_access_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `widget_user`
--
ALTER TABLE `widget_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;