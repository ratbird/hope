<?php

class Step291Questionnaires extends Migration
{

    function description() {
        return 'Turns all votes and tests into questionnaires.';
    }

    function up()
    {
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `questionnaires` (
                `questionnaire_id` varchar(32) NOT NULL,
                `title` varchar(128)  NOT NULL,
                `description` text NULL,
                `user_id` varchar(32)  NOT NULL,
                `startdate` BIGINT(20) NULL,
                `stopdate` BIGINT(20) NULL,
                `visible` TINYINT(1) DEFAULT '0' NOT NULL,
                `anonymous` TINYINT(1) DEFAULT '0' NOT NULL,
                `resultvisibility` ENUM('always', 'never', 'afterending') DEFAULT 'always' NOT NULL,
                `editanswers` TINYINT(1) DEFAULT '1' NOT NULL,
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`questionnaire_id`),
                KEY `user_id` (`user_id`)
            )
        ");
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `questionnaire_questions` (
                `question_id` varchar(32) NOT NULL,
                `questionnaire_id` varchar(32) NOT NULL,
                `questiontype` varchar(64) NOT NULL,
                `questiondata` text NOT NULL,
                `position` INT NOT NULL,
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`question_id`),
                KEY `questionnaire_id` (`questionnaire_id`)
            )
        ");
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `questionnaire_assignments` (
                `assignment_id` varchar(32) NOT NULL,
                `questionnaire_id` varchar(32) NOT NULL,
                `range_id` varchar(32) NOT NULL,
                `range_type` varchar(64) NOT NULL,
                `user_id` varchar(32) NOT NULL,
                `chdate` bigint(20) NOT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`assignment_id`),
                KEY `questionnaire_id` (`questionnaire_id`),
                KEY `range_id_range_type` (`range_id`,`range_type`),
                KEY `user_id` (`user_id`)
            )
        ");
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `questionnaire_answers` (
                `answer_id` varchar(32) NOT NULL,
                `question_id` varchar(32)  NOT NULL,
                `user_id` varchar(32) NULL,
                `answerdata` text NOT NULL,
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`answer_id`),
                KEY `question_id` (`question_id`),
                KEY `user_id` (`user_id`)
            )
        ");
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `questionnaire_anonymous_answers` (
                `anonymous_answer_id` varchar(32) NOT NULL,
                `questionnaire_id` varchar(32) NOT NULL,
                `user_id` varchar(32) NOT NULL,
                `chdate` bigint(20) NOT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`anonymous_answer_id`),
                KEY `questionnaire_id` (`questionnaire_id`),
                UNIQUE KEY `questionnaire_id_user_id` (`questionnaire_id`,`user_id`),
                KEY `user_id` (`user_id`)
            )
        ");

        //now import old data into new tables:
        $resultvisibility_mapping = array(
            'ever' => 'always',
            'delivery' => 'always',
            'end' => 'afterending',
            'never' => 'never'
        );
        $all_votes = DBManager::get()->prepare("
            SELECT * FROM vote
        ");
        $all_votes->execute();
        while ($vote = $all_votes->fetch(PDO::FETCH_ASSOC)) {
            //Fragebogen erstellen
            $questionnaire = new Questionnaire($vote['vote_id']);
            $questionnaire['title'] = $vote['title'];
            $questionnaire->setId($vote['vote_id']);
            $questionnaire['user_id'] = $vote['author_id'];
            $questionnaire['startdate'] = $vote['startdate'];
            $questionnaire['stopdate'] = $vote['stopdate'] ?: (in_array($vote['state'], array("stopvis", "stopinvis")) ? time() : null);
            $questionnaire['visible'] = in_array($vote['state'], array("active", "stopvis")) ? 1 : 0; // stopvis new active stopinvis
            $questionnaire['anonymous'] = $vote['anonymous'];
            $questionnaire['resultvisibility'] = $resultvisibility_mapping[$vote['resultvisibility']];
            $questionnaire['editanswers'] = $vote['changeable'];
            $questionnaire['chdate'] = $vote['chdate'];
            $questionnaire['mkdate'] = $vote['mkdate'];

            $questionnaire->store();

            //Tests und Umfragen anlegen
            if ($vote['type'] === "vote") {
                $question = new Vote();
                $question['questiontype'] = "Vote";
            } else {
                $question = new Test();
                $question['questiontype'] = "Test";
            }
            $questiondata = array();
            $question['questionnaire_id'] = $questionnaire->getId();
            $question['chdate'] = $vote['chdate'];
            $question['mkdate'] = $vote['mkdate'];
            $questiondata['multiplechoice'] = $vote['multiplechoice'];
            $questiondata['question'] = $vote['question'];
            $question['position'] = 1;

            //Antwortm�glichkeiten vorsehen:
            $options_statement = DBManager::get()->prepare("
                SELECT *
                FROM voteanswers
                WHERE vote_id = ?
                ORDER BY position ASC
            ");
            $options_statement->execute(array($vote['vote_id']));
            $options = $options_statement->fetchAll(PDO::FETCH_ASSOC);
            $mapping = array();
            $counter = array();
            foreach ($options as $key => $option) {
                $questiondata['options'][] = $option['answer'];
                $mapping[$option['answer_id']] = $key + 1;
                $counter[$option['answer_id']] = $option['counter'];
                if (($vote['type'] === "test") && $option['correct']) {
                    $questiondata['correctanswer'][] = $key + 1;
                }
            }
            $question['questiondata'] = $questiondata;
            $question->store();

            //Bestehende Antworten migrieren
            if ($questionnaire['anonymous']) {
                foreach ($counter as $answer_id => $count) {
                    for ($i = 0; $i < $count; $i++) {
                        $answer = new QuestionnaireAnswer();
                        $answer['user_id'] = null;
                        $answer['chdate'] = 1; //damit man nicht aus dem chdate auf die user_id schlie�en kann
                        $answer['mkdate'] = 1; //mkdate genauso
                        $answer['question_id'] = $question->getId();
                        $answerdata = array();
                        $answers = array($answer_id);
                        foreach ($answers as $key => $answer_data) {
                            $answers[$key] = $mapping[$answer_data];
                        }
                        sort($answers);
                        $answerdata['answers'] = $answers;
                        if (!$questiondata['multiplechoice']) {
                            $answerdata['answers'] = $answerdata['answers'][0];
                        }
                        $answer['answerdata'] = $answerdata;
                        $answer->store();
                    }
                }

                $statement = DBManager::get()->prepare("
                    SELECT *
                    FROM vote_user
                    WHERE vote_id = :vote_id
                ");
                $statement->execute(array(
                    'vote_id' => $vote['vote_id']
                ));
                foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $anonymous_vote) {
                    $anonymous_answer = new QuestionnaireAnonymousAnswer();
                    $anonymous_answer['questionnaire_id'] = $questionnaire->getId();
                    $anonymous_answer['user_id'] = $anonymous_vote['user_id'];
                    $anonymous_answer['chdate'] = $anonymous_vote['votedate'];
                    $anonymous_answer['mkdate'] = $anonymous_vote['votedate'];
                    $anonymous_answer->store();
                }

            } else {
                $statement = DBManager::get()->prepare("
                    SELECT GROUP_CONCAT(answer_id SEPARATOR ' ') AS answers, user_id, MAX(votedate) AS votedate
                    FROM voteanswers_user
                    WHERE answer_id IN (?)
                    GROUP BY user_id
                ");
                $statement->execute(array(array_keys($mapping)));
                foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $answer_data) {
                    $answer = new QuestionnaireAnswer();
                    $answer['user_id'] = $answer_data['user_id'];
                    $answer['chdate'] = $answer_data['votedate'];
                    $answer['mkdate'] = $answer_data['votedate'];
                    $answer['question_id'] = $question->getId();
                    $answerdata = array();
                    $answers = explode(" ", $answer_data['answers']);
                    foreach ($answers as $key => $answer_data) {
                        $answers[$key] = $mapping[$answer_data];
                    }
                    sort($answers);
                    $answerdata['answers'] = $answers;
                    if (!$questiondata['multiplechoice']) {
                        $answerdata['answers'] = $answerdata['answers'][0];
                    }
                    $answer['answerdata'] = $answerdata;
                    $answer->store();
                }
            }


            //Und noch einh�ngen das ganze:
            $binding = new QuestionnaireAssignment();
            $binding['questionnaire_id'] = $questionnaire->getId();
            $binding['range_id'] = $vote['range_id'] !== "studip" ? $vote['range_id'] : "start";
            $binding['range_type'] = $vote['range_id'] === "studip"
                ? "static"
                : ($vote['range_id'] === $vote['author_id']
                    ? "user"
                    : (Institute::find($vote['range_id']) ? "institute" : "course")
                );
            $binding['user_id'] = $vote['author_id'];
            $binding['chdate'] = $questionnaire['chdate'];
            $binding['mkdate'] = $questionnaire['mkdate'];

            $binding->store();
        }

        //and finally clean up:

        //DBManager::get()->exec("DROP TABLE IF EXISTS `vote`");
        //DBManager::get()->exec("DROP TABLE IF EXISTS `voteanswers`");
        //DBManager::get()->exec("DROP TABLE IF EXISTS `voteanswers_user`");
        //DBManager::get()->exec("DROP TABLE IF EXISTS `vote_user`");
    }

    function down()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `questionnaire_anonymous_answers`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `questionnaire_assignments`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `questionnaire_questions`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `questionnaire_answers`");
        DBManager::get()->exec("DROP TABLE IF EXISTS `questionnaires`");
    }

}
