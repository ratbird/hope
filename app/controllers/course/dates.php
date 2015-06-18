<?php

require_once 'app/controllers/authenticated_controller.php';

require_once ('lib/classes/Seminar.class.php');
require_once ('lib/raumzeit/raumzeit_functions.inc.php');

class Course_DatesController extends AuthenticatedController
{
    protected $allow_nobody = true;

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        checkObject();
        checkObjectModule("schedule");
        $course = Course::findCurrent();
        if ($course) {
            PageLayout::setTitle(sprintf('%s - %s', $course->getFullname(), _("Termine")));
        } else {
            PageLayout::setTitle(_("Termine"));
        }
    }

    public function index_action()
    {
        if (Request::isPost() && Request::option("termin_id") && Request::get("topic_title")) {
            $date = new CourseDate(Request::option("termin_id"));
            $seminar_id = $date['range_id'];
            $title = Request::get("topic_title");
            $topic = CourseTopic::findByTitle($seminar_id, $title);
            if (!$topic) {
                $topic = new CourseTopic();
                $topic['title'] = $title;
                $topic['seminar_id'] = $seminar_id;
                $topic['author_id'] = $GLOBALS['user']->id;
                $topic['description'] = "";
                $topic->store();
            }
            $success = $date->addTopic($topic);
            if ($success) {
                PageLayout::postMessage(MessageBox::success(_("Thema wurde hinzugefügt.")));
            } else {
                PageLayout::postMessage(MessageBox::info(_("Thema war schon mit dem Termin verknüpft.")));
            }
        }
        Navigation::activateItem('/course/schedule/dates');

        object_set_visit_module("schedule");

        PageLayout::addScript("jquery/jquery.tablesorter.js");

        $this->dates = Course::findCurrent()->getDatesWithExdates();
    }

    public function details_action($termin_id)
    {
        Navigation::activateItem('/course/schedule/dates');
        $this->date = new CourseDate($termin_id);
        $this->cancelled_dates_locked = LockRules::Check($this->date->range_id, 'cancelled_dates');
        $this->dates_locked = LockRules::Check($this->date->range_id, 'room_time');
        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
            $this->response->add_header('X-Title', $this->date->getTypeName() . ": ".
                $this->date->getFullname()
            );
        }
    }

    public function new_topic_action()
    {
        Navigation::activateItem('/course/schedule/dates');
        if (Request::isAjax()) {
            PageLayout::setTitle(_("Thema hinzufügen"));
        }
        $this->date = new CourseDate(Request::option("termin_id"));
        $this->course = Course::findCurrent();
    }

    public function add_topic_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException("Kein Zugriff");
        }
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

    /**
     * Moves a topic from one date to another.
     * This action will be called from an ajax request and will return only
     * the neccessary output for a single topic element.
     *
     * @param String $topic_id    The id of the topic
     * @param String $old_date_id The id of the original date of the topic
     * @param String $new_date_id The id of the new date of the topic
     * @throws MethodNotAllowedException if request method is not post
     * @throws AccessDeniedException if the user is not allowed to execute the
     *                               action (at least tutor of the course)
     */
    public function move_topic_action($topic_id, $old_date_id, $new_date_id)
    {
        if (!Request::isPost()) {
            throw new MethodNotAllowedException();
        }

        if (!$GLOBALS['perm']->have_studip_perm('tutor', $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException();
        }

        $this->topic = CourseTopic::find($topic_id);
        $this->date = new CourseDate($new_date_id);

        $this->topic->dates->unsetByPK($old_date_id);
        if (!$this->topic->dates->findOneBy('termin_id', $new_date_id)) {
            $this->topic->dates[] = CourseDate::find($new_date_id);
        }
        $this->topic->store();

        $this->set_content_type('text/html;charset=windows-1252');
        $this->render_template('course/dates/_topic_li.php');
    }

    public function remove_topic_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        $topic = new CourseTopic(Request::option("issue_id"));
        $date = new CourseDate(Request::option("termin_id"));
        $date->removeTopic($topic);

        $output = array();
        $this->render_json($output);
    }

    public function export_action()
    {
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
                        $title = trim(join("\n", array_map(function ($tid) use ($themen) {return $themen[$tid]->getTitle();}, $tmp_ids)));
                        $description = trim(join("\n\n", array_map(function ($tid) use ($themen) {return $themen[$tid]->getDescription();}, $tmp_ids)));
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
