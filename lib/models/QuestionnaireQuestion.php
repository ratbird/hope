<?php

class QuestionnaireQuestion extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'questionnaire_questions';
        $config['belongs_to']['questionnaire'] = array(
            'class_name' => 'Questionnaire',
            'foreign_key' => 'questionnaire_id'
        );
        $config['has_many']['answers'] = array(
            'class_name' => 'QuestionnaireAnswer'
        );
        $config['has_many']['relations'] = array(
            'class_name' => 'QuestionnaireRelation'
        );
        $config['serialized_fields']['questiondata'] = "JSONArrayObject";
        parent::configure($config);
    }

    public static function findByQuestionnaire_id($questionnaire_id)
    {
        $statement = DBManager::get()->prepare("
            SELECT *
            FROM questionnaire_questions
            WHERE questionnaire_id = ?
            ORDER BY position ASC
        ");
        $statement->execute(array($questionnaire_id));
        $data = $statement->fetchAll();
        $questions = array();
        foreach ($data as $questionnaire_data) {
            $class = $questionnaire_data['questiontype'];
            if (class_exists($class)) {
                $questions[] = $class::buildExisting($questionnaire_data);
            }
        }
        return $questions;
    }

    public function getMyAnswer($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        if (!$user_id || $user_id === "nobody") {
            $answer = new QuestionnaireAnswer();
            $answer['user_id'] = $user_id;
            $answer['question_id'] = $this->getId();
            return $answer;
        }
        $statement = DBManager::get()->prepare("
            SELECT *
            FROM questionnaire_answers
            WHERE question_id = :question_id
                AND user_id = :me
        ");
        $statement->execute(array(
            'question_id' => $this->getId(),
            'me' => $user_id
        ));
        $data = $statement->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            return QuestionnaireAnswer::buildExisting($data);
        } else {
            $answer = new QuestionnaireAnswer();
            $answer['user_id'] = $user_id;
            $answer['question_id'] = $this->getId();
            return $answer;
        }
    }
}