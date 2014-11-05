<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of 148_tic_4520_sem_tree_display
 *
 * @author intelec
 */
class Tic5137ImproveScore extends Migration {

    function description() {
        return 'Stud.IP now supports ScorePlugins which can define the score calculation';
    }

    function up() {
        DBManager::get()->query("CREATE TABLE `user_score` (
  `user_id` varchar(32) COLLATE latin1_german1_ci NOT NULL DEFAULT '',
  `score` int(11) NOT NULL DEFAULT '0',
  `public` tinyint(4) NOT NULL DEFAULT '0',
  `chdate` int(11) NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `score` (`score`),
  KEY `public` (`public`),
  KEY `chdate` (`chdate`)
)");
        DBManager::get()->query("INSERT INTO user_score SELECT user_id, score, 1 as public, unix_timestamp() as chdate FROM user_info WHERE score != 0");
        DBManager::get()->query("ALTER TABLE user_info DROP COLUMN score");
    }

    function down() {
        DBManager::get()->query("ALTER TABLE user_info ADD COLUMN score INT(11) NOT NULL");
        DBManager::get()->query("ALTER TABLE user_info ADD INDEX score(score)");
        DBManager::get()->query("DROP TABLE user_score");
    }

}
