<?php

class Questionnaire extends SimpleORMap
{

    public $answerable;

    protected static function configure($config = array())
    {
        $config['db_table'] = 'questionnaires';
        $config['has_many']['questions'] = array(
            'class_name' => 'QuestionnaireQuestion',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['has_many']['assignments'] = array(
            'class_name' => 'QuestionnaireAssignment',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        parent::configure($config);
    }

    public function countAnswers()
    {
        $statement = DBManager::get()->prepare("
            SELECT COUNT(*)
            FROM questionnaire_answers
                INNER JOIN questionnaire_questions ON (questionnaire_answers.question_id = questionnaire_questions.question_id)
            WHERE questionnaire_id = :questionnaire_id
        ");
        $statement->execute(array(
            'questionnaire_id' => $this->getId()
        ));
        $answers_total = $statement->fetch(PDO::FETCH_COLUMN, 0);

        return count($this->questions) ? $answers_total / count($this->questions) : 1;
    }

    public function isAnswered($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        if (!$user_id || ($user_id === "nobody")) {
            return false;
        }
        $statement = DBManager::get()->prepare("
            SELECT 1
            FROM questionnaire_answers
                INNER JOIN questionnaire_questions ON (questionnaire_answers.question_id = questionnaire_questions.question_id)
            WHERE user_id = :user_id
                AND questionnaire_id = :questionnaire_id
            UNION SELECT 1
            FROM questionnaire_anonymous_answers
            WHERE user_id = :user_id
                AND questionnaire_id = :questionnaire_id
        ");
        $statement->execute(array(
            'user_id' => $user_id,
            'questionnaire_id' => $this->getId()
        ));
        return (bool) $statement->fetch(PDO::FETCH_COLUMN, 0);
    }

    public function latestAnswerTimestamp()
    {
        $statement = DBManager::get()->prepare("
            SELECT questionnaire_answers.chdate
            FROM questionnaire_answers
                INNER JOIN questionnaire_questions ON (questionnaire_answers.question_id = questionnaire_questions.question_id)
            WHERE questionnaire_questions.questionnaire_id = ?
            ORDER BY questionnaire_answers.chdate DESC
            LIMIT 1
        ");
        $statement->execute(array($this->getId()));
        return $statement->fetch(PDO::FETCH_COLUMN, 0);
    }

    public function isViewable()
    {
        if ($this->isEditable()) {
            return true;
        }
        foreach ($this->assignments as $assignment) {
            if ($assignment['range_id'] === "public") {
                return true;
            } elseif ($assignment['range_id'] === "start" && $GLOBALS['perm']->have_perm("user")) {
                return true;
            } elseif ($assignment['range_type'] === "user" && $GLOBALS['perm']->have_perm("user")) {
                return true;
            } elseif($GLOBALS['perm']->have_studip_perm("user", $assignment['range_id'])) {
                return true;
            }
        }
        return false;
    }

    public function isAnswerable()
    {
        if (!$this->isViewable()) {
            return false;
        }
        if ($this->isEditable()) {
            return true;
        }
        $answerable = true;
        NotificationCenter::postNotification("QuestionnaireWillAllowToAnswer", $this);
        return $answerable;
    }

    public function isEditable()
    {
        if (($this['user_id'] === $GLOBALS['user']->id) || $GLOBALS['perm']->have_perm("root")) {
            return true;
        } else {
            foreach ($this->assignments as $assignment) {
                if ($assignment['range_type'] === "institute" && $GLOBALS['perm']->have_studip_perm("admin", $assignment['range_id'])) {
                    return true;
                } elseif($assignment['range_type'] === "course" && $GLOBALS['perm']->have_studip_perm("tutor", $assignment['range_id'])) {
                    return true;
                }
            }
        }
        return false;
    }

    public function start()
    {
        if (!$this['startdate']) {
            $this['startdate'] = time();
        }
        $this['visible'] = 1;
        $this->store();
    }

    public function stop()
    {
        $this['visible'] = 0;
        if (!$this['stopdate']) {
            $this['stopdate'] = time();
        }
        $this->store();
        foreach ($this->questions as $question) {
            $question->onEnding();
        }
    }

    public function isStarted()
    {
        return $this['visible'] && $this['startdate'] && ($this['startdate'] <= time());
    }

    public function isStopped()
    {
        return !$this['visible'] && $this['stopdate'] && ($this['stopdate'] <= time());
    }

    public function resultsVisible()
    {
        return $this['resultvisibility'] === "always"
            || $this->isEditable()
            || ($this['resultvisibility'] === "afterending" && $this->isStopped());
    }

    public function toJSONRepresentation()
    {
        $json = $this->toRawArray();

    }
}