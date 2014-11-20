<?php

require_once 'lib/classes/score.class.php';

class RecalculateScore extends Migration {

    function description() {
        return 'Recalculates the score for all users that have their score published.';
    }

    function up() {

        try {
            $statement = DBManager::get()->prepare("
                ALTER TABLE message ADD INDEX autor_id (autor_id)
            ");
            $statement->execute();
        } catch (PDOException $e) {}

        $statement = DBManager::get()->prepare("
            SELECT user_id FROM user_info WHERE score > 0
        ");
        $statement->execute();
        $score = new Score($GLOBALS['user']->id);
        while ($user_id = $statement->fetch(PDO::FETCH_COLUMN, 0)) {
            $score->GetMyScore($user_id);
        }
    }

    function down() {

    }

}

