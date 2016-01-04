<?php

class QuestionnaireAssignment extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'questionnaire_assignments';
        $config['belongs_to']['questionnaire'] = array(
            'class_name' => 'Questionnaire'
        );
        parent::configure($config);
    }

    static public function findBySeminarAndQuestionnaire($seminar_id, $questionnaire_id)
    {
        return self::findOneBySQL("questionnaire_id = ? AND range_id = ? AND range_type = 'course'", array($questionnaire_id, $seminar_id));
    }
}