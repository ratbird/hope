<?php
# Lifter007: TODO
# Lifter003: TODO

/*
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/functions.php';
require_once 'lib/classes/SemesterData.class.php';
require_once 'app/models/autocomplete_course.php';
require_once 'app/controllers/authenticated_controller.php';

class Autocomplete_CourseController extends AuthenticatedController {

  function index_action() {

    $this->search_term = strtr(self::get_param('value'), array('%' => '\%'));
    $this->options = array();

    $semester = self::get_param('semester');
    if (is_numeric($semester)) {
      $this->options['semester'] = (int) $semester;
    }

    $what = self::get_param('what');
    if (in_array($what, array_keys(StudipSemSearchHelper::GetQuickSearchFields()))) {
      $this->options['what'] = $what;
    }

    $category = self::get_param('category');
    if (is_numeric($category)) {
      $this->options['category'] = (int) $category;
    }

    $scope = self::get_param('scope');
    if (!empty($scope)) {
      $this->options['scope'] = $scope;
    }

    $range = self::get_param('range');
    if (!empty($range)) {
      $this->options['range'] = $range;
    }

    $this->courses = autocomplete_course_get_courses($this->search_term,
                                                     $this->options);
    if(!$this->options['semester']) $this->semesters = autocomplete_course_get_semesters();
  }

  private static function get_param($key) {
    return studip_utf8decode(Request::get($key));
  }

  function before_filter(&$action, &$args) {
    parent::before_filter($action, $args);

    $this->response->add_header('Content-Type', 'text/html; charset=windows-1252');
    $this->set_layout(NULL);
  }
}
