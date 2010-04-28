<?
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

require_once 'lib/classes/StudipSemSearchHelper.class.php';

function autocomplete_course_get_semesters() {
  $semdata = new SemesterData();
  $semesters = $semdata->getAllSemesterData();

  $result = array();
  foreach ($semesters as $semester) {
    $result[$semester['beginn']] = $semester['name'];
  }
  return $result;
}

function autocomplete_course_get_courses($search_term, $options) {

  $search_helper = new StudipSemSearchHelper();
  $search_helper->setParams(
    array(
      'quick_search' => $search_term,
      'qs_choose' => $options['what'] ? $options['what'] : 'all',
      'sem' => isset($options['semester']) ? $options['semester'] : 'all',
      'category' => $options['category'],
      'scope_choose' => $options['scope'],
      'range_choose' => $options['range']),
    !(is_object($GLOBALS['perm'])
      && $GLOBALS['perm']->have_perm(
        Config::GetInstance()->getValue('SEM_VISIBILITY_PERM'))));
  $search_helper->doSearch();
  $result = $search_helper->getSearchResultAsArray();

  if (empty($result)) {
    return array();
  }

  return autocomplete_course_get_courses_by_id(array_slice($result, 0, 10));
}

function autocomplete_course_get_courses_by_id($ids) {
   $db = DBManager::get();

   return $db->query(
     'SELECT '.
     'seminare.seminar_id, '.
     'seminare.Name, '.
     'seminare.VeranstaltungsNummer, '.
     'seminare.Beschreibung, '.
     'seminare.start_time, '.
     "GROUP_CONCAT(CONCAT(auth_user_md5.Vorname, ' ', auth_user_md5.Nachname) ".
     "ORDER BY auth_user_md5.Nachname SEPARATOR ', ') AS lecturer ".
     'FROM seminare '.
     'LEFT JOIN seminar_user '.
     'ON (seminare.seminar_id = seminar_user.seminar_id) '.
     'LEFT JOIN auth_user_md5 '.
     "ON (seminar_user.user_id = auth_user_md5.user_id) ".
     "WHERE seminare.seminar_id IN ('".join("','", $ids)."') ".
     "AND seminar_user.status = 'dozent' ".
     'GROUP BY seminare.seminar_id')
     ->fetchAll();
}

