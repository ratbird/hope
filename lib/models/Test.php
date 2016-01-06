<?php

require_once 'lib/classes/QuestionType.interface.php';

class Test extends QuestionnaireQuestion implements QuestionType
{
    static public function getIcon($active = false, $add = false)
    {
        return "icons/".($active ? "blue" : "black")."/20/".($add ? "add/" : "")."test";
    }

    static public function getName()
    {
        return _("Test");
    }

    public function getEditingTemplate()
    {
        $tf = new Flexi_TemplateFactory(__DIR__."/../../app/views");
        $template = $tf->open("questionnaire/question_types/test/test_edit.php");
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
        $template = $tf->open("questionnaire/question_types/test/test_evaluation.php");
        $template->set_attribute('vote', $this);
        $template->set_attribute('answers', $answers);
        return $template;
    }

    public function getResultArray()
    {
        $output = array();
        $correct = array();

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
                if (!isset($correct[$user_id])) {
                    $correct[$user_id] = 1;
                }
                if (in_array($key + 1, (array) $answerdata['answers'])) {
                    $answer_option[$user_id] = 1;
                    $correct[$user_id] = ($correct[$user_id] && in_array($key + 1, (array) $questiondata['correctanswer'])) ? 1: 0;
                } else {
                    $answer_option[$user_id] = 0;
                    $correct[$user_id] = ($correct[$user_id] && !in_array($key + 1, (array) $questiondata['correctanswer'])) ? 1: 0;
                }
            }
            $output[$this['questiondata']['question']." - ".$option] = $answer_option;
        }

        $output[$this['questiondata']['question']." - "._("richtig?")] = $correct;
        return $output;
    }

    public function onEnding()
    {

    }

    public function correctAnswered($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        $correct_answered = true;
        $results_users = array();
        $data = $this['questiondata']->getArrayCopy();
        foreach ($data['options'] as $option) {
            $results_users[] = array();
        }
        foreach ($this->answers as $answer) {
            if ($data['multiplechoice']) {
                foreach ($answer['answerdata']['answers'] as $a) {
                    $results_users[(int) $a - 1][] = $answer['user_id'];
                }
            } else {
                $results_users[(int) $answer['answerdata']['answers'] - 1][] = $answer['user_id'];
            }
        }
        foreach ($this['questiondata']['options'] as $key => $option) {
            if (in_array($key + 1, $data['correctanswer'])) {
                if (!in_array($GLOBALS['user']->id, $results_users[$key])) {
                    $correct_answered = false;
                    break;
                }
            } else {
                if (in_array($GLOBALS['user']->id, $results_users[$key])) {
                    $correct_answered = false;
                    break;
                }
            }
        }
        return $correct_answered;
    }
}