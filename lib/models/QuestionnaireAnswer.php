<?php

class QuestionnaireAnswer extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'questionnaire_answers';
        $config['belongs_to']['question'] = array(
            'class_name' => 'QuestionnaireQuestion'
        );
        $config['serialized_fields']['answerdata'] = "JSONArrayObject";
        parent::configure($config);
    }
}