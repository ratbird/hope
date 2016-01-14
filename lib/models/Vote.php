<?php

require_once 'lib/classes/QuestionType.interface.php';

class Vote extends QuestionnaireQuestion implements QuestionType
{
    static public function getIcon($active = false, $add = false)
    {
        return Icon::create(($add ?  "add/" : "")."vote", $active ? "clickable" : "info");
    }

    static public function getName()
    {
        return _("Frage");
    }

    public function getEditingTemplate()
    {
        $tf = new Flexi_TemplateFactory(realpath(__DIR__."/../../app/views"));
        $template = $tf->open("questionnaire/question_types/vote/vote_edit.php");
        $template->set_attribute('vote', $this);
        return $template;
    }

    public function createDataFromRequest()
    {
        $questions = Request::getArray("questions");
        $question_data = $questions[$this->getId()];

        //now remove empty trailing options
        $i = count($question_data['questiondata']['options']) - 1;
        while ($i >= 0 && !trim($question_data['questiondata']['options'][$i])) {
            unset($question_data['questiondata']['options'][$i]);
            $i--;
        }

        $this->setData($question_data);
    }

    public function getDisplayTemplate()
    {
        $tf = new Flexi_TemplateFactory(realpath(__DIR__."/../../app/views"));
        $template = $tf->open("questionnaire/question_types/vote/vote_answer.php");
        $template->set_attribute('vote', $this);
        return $template;
    }

    public function createAnswer()
    {
        $answer = $this->getMyAnswer();
        $answers = Request::getArray("answers");
        $answer_data = $answers[$this->getId()];
        $answer->setData($answer_data);
        return $answer;
    }

    public function getResultTemplate($only_user_ids = null)
    {
        $answers = $this->answers;
        if ($only_user_ids !== null) {
            foreach ($answers as $key => $answer) {
                if (!in_array($answer['user_id'], $only_user_ids)) {
                    unset($answers[$key]);
                }
            }
        }
        $tf = new Flexi_TemplateFactory(realpath(__DIR__."/../../app/views"));
        $template = $tf->open("questionnaire/question_types/vote/vote_evaluation.php");
        $template->set_attribute('vote', $this);
        $template->set_attribute('answers', $answers);
        return $template;
    }

    public function getResultArray()
    {
        $output = array();

        $questiondata = $this['questiondata']->getArrayCopy();

        foreach ($questiondata['options'] as $key => $option) {
            $answer_option = array();
            $count_nobodys = 0;
            foreach ($this->answers as $answer) {
                $answerdata = $answer['answerdata']->getArrayCopy();

                if ($answer['user_id']) {
                    $user_id = $answer['user_id'];
                } else {
                    $count_nobodys++;
                    $user_id = _("unbekannt")." ".$count_nobodys;
                }
                if (in_array($key + 1, $answerdata['answers'])) {
                    $answer_option[$user_id] = 1;
                } else {
                    $answer_option[$user_id] = 0;
                }
            }
            $output[$this['questiondata']['question']." - ".$option] = $answer_option;
        }
        return $output;
    }

    public function onEnding()
    {

    }
}