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
}
