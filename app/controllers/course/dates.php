<?php

require_once 'app/controllers/authenticated_controller.php';

require_once ('lib/classes/Seminar.class.php');
require_once ('lib/raumzeit/raumzeit_functions.inc.php');

class Course_DatesController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        checkObject();
        checkObjectModule("schedule");
        object_set_visit_module("schedule");
        Navigation::activateItem('/course/schedule/dates');
        PageLayout::addScript("jquery/jquery.tablesorter.js");
        $this->dates = CourseDate::findBySeminar_id($_SESSION['SessionSeminar']);
    }

    public function details_action($termin_id) {
        Navigation::activateItem('/course/schedule/dates');
        $this->date = new CourseDate($termin_id);
        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
            $this->response->add_header('X-Title', $this->date->getTypeName() . ": ".
                $this->date->getFullname()
            );
        }
    }

    public function add_topic_action() {
        if (!Request::get("title")) {
            throw new Exception("Geben Sie einen Titel an.");
        }
        $date = new CourseDate(Request::option("termin_id"));
        $seminar_id = $date['range_id'];
        $title = studip_utf8decode(Request::get("title"));
        $topic = CourseTopic::findByTitle($seminar_id, $title);
        if (!$topic) {
            $topic = new CourseTopic();
            $topic['title'] = $title;
            $topic['seminar_id'] = $seminar_id;
            $topic['author_id'] = $GLOBALS['user']->id;
            $topic['description'] = "";
            $topic->store();
        }
        $date->addTopic($topic);

        $factory = $this->get_template_factory();
        $output = array('topic_id' => $topic->getId());

        $template = $factory->open($this->get_default_template("_topic_li"));
        $template->set_attribute("topic", $topic);
        $output['li'] = $template->render();

        $this->render_json($output);
    }

    public function remove_topic_action() {
        $topic = new CourseTopic(Request::option("issue_id"));
        $date = new CourseDate(Request::option("termin_id"));
        $date->removeTopic($topic);

        $output = array();
        $this->render_json($output);
    }

    public function export_action() {
        if (!$GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        $course = new Course($_SESSION['SessionSeminar']);
        $sem = new Seminar($_SESSION['SessionSeminar']);
        $themen =& $sem->getIssues();

        $termine = getAllSortedSingleDates($sem);

        $dates = array();

        if (is_array($termine) && sizeof($termine) > 0) {
            foreach ($termine as $singledate_id => $singledate) {
                if (!$singledate->isExTermin()) {
                    $tmp_ids = $singledate->getIssueIDs();
                    $title = $description = '';
                    if (is_array($tmp_ids)) {
                        $thema_id = array_pop($tmp_ids);
                        $title = $themen[$thema_id]->getTitle();
                        $description = $themen[$thema_id]->getDescription();
                    }

                    $dates[] = array(
                        'date'  => $singledate->toString(),
                        'title' => $title,
                        'description' => $description,
                        'start' => $singledate->getStartTime(),
                        'related_persons' => $singledate->getRelatedPersons()
                    );
                } elseif ($singledate->getComment()) {
                    $dates[] = array(
                        'date'  => $singledate->toString(),
                        'title' => _('fällt aus') . ' (' . _('Kommentar:') . ' ' . $singledate->getComment() . ')',
                        'description' => '',
                        'start' => $singledate->getStartTime(),
                        'related_persons' => array()
                    );
                }
            }
        }

        $factory = $this->get_template_factory();
        $template = $factory->open($this->get_default_template("export"));

        $template->set_attribute('dates', $dates);
        $content = $template->render();

        $filename = prepareFilename($course['name'] . '-' . _("Ablaufplan")) . '.doc';
        header("Content-type: application/vnd.ms-word");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Cache-Control: private");
        header("Pragma: cache");
        echo mb_encode_numericentity($content, array(0x80, 0xffff, 0, 0xffff), 'cp1252');

        $this->render_nothing();
    }
}
