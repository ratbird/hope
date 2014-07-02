<?php

interface AdminCourseAction
{
    public function getAdminActionURL();
    public function useMultimode();
    public function getAdminCourseActionTemplate($course_id, $values = null);
}