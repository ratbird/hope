<?php

require_once 'app/controllers/authenticated_controller.php';

class Course_DatesController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        Navigation::activateItem('/course/schedule/dates');
        $this->dates = CourseDate::findBySeminar_id($_SESSION['SessionSeminar']);
    }

    public function details_action($termin_id) {
        Navigation::activateItem('/course/schedule/dates');
        $this->date = new CourseDate($termin_id);
        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
            $this->response->add_header('X-Title', $GLOBALS['TERMIN_TYP'][$this->date['date_typ']]['name'].": ".
                ((floor($this->date['date'] / 86400) !== floor($this->date['end_time'] / 86400))
                    ? date("d.m.Y, H:i", $this->date['date'])." - ".date("d.m.Y, H:i", $this->date['end_time'])
                    : date("d.m.Y, H:i", $this->date['date'])." - ".date("H:i", $this->date['end_time']))
            );
        }
    }

    public function add_topic_action() {
        if (!Request::get("title")) {
            throw new Exception("Geben Sie einen Titel an.");
        }
        $date = new CourseDate(Request::option("termin_id"));
        $seminar_id = $date['range_id'];
        $topic = CourseTopic::findByTitle($seminar_id, Request::get("title"));
        if (!$topic) {
            $topic = new CourseTopic();
            $topic['title'] = Request::get("title");
            $topic['seminar_id'] = $seminar_id;
            $topic['author_id'] = $GLOBALS['user']->id;
            $topic['description'] = "";
            $priority = 0;
            $found = false;
            foreach (CourseDate::findBySeminar_id($seminar_id) as $date2) {
                if ($date2->getId() === Request::option("termin_id")) {
                    $priority++;
                    $found = true;
                }
                foreach ($date2->topics as $topic2) {
                    if (!$found) {
                        $priority = max($priority, $topic2['priority']);
                    } elseif($topic2['priority'] >= $priority) {
                        $topic2['priority'] = $topic2['priority'] + 1;
                        $topic2->store();
                    }
                }
            }
            $topic['priority'] = $priority;
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
}
