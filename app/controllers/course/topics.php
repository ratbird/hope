<?php

require_once 'app/controllers/authenticated_controller.php';

class Course_TopicsController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        Navigation::activateItem('/course/schedule/topics');
        $this->topics = CourseTopic::findBySeminar_id($_SESSION['SessionSeminar']);
    }


}
