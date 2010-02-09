<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * studip_lecture_tree.php - base class for lecture tree
 *
 * Copyright (C) 2006 - Marco Diedrich (mdiedric@uos.de)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class StudipLectureTreeHelper
{
	function StudipLectureTreeHelper()
	{
		
	}

	function get_seminars_by_sem_tree_id($sem_tree_id, $term_id)
	{
		$db = new DB_Seminar();
		$sem_data_obj = new SemesterData();
		$semester = $sem_data_obj->getSemesterData($term_id);
		$db->query($query = "SELECT s.Name, s.Seminar_id FROM seminar_sem_tree st
			INNER JOIN seminare s
			ON (st.seminar_id = s.Seminar_id)
			WHERE st.sem_tree_id = '{$sem_tree_id}'
			AND (
				s.start_time <= ".$semester['beginn']." AND s.start_time + s.duration_time >= ".$semester['beginn']."
				OR s.start_time <= ".$semester['ende']." AND s.start_time + s.duration_time >= ".$semester['ende']."
				OR s.start_time <= ".$semester['beginn']." AND duration_time = -1
			) ");
		//var_dump($query);
		$result = array();

		while($db->next_record())
		{
			$result [] = array ( 	"seminar_id" => $db->f("Seminar_id"),
														"name" => $db->f("Name"));
		}

		return $result;
	}

	function get_sem_path($sem_tree_id)
	{
		$stack = (array) $sem_tree_id;
		$info = StudipLectureTreeHelper::get_info_for_sem_tree_id($sem_tree_id);

		$name_parts = array();

		while(($current = array_pop($stack)))
		{
			$info = StudipLectureTreeHelper::get_info_for_sem_tree_id($current);
			array_push($stack, $info['parent_id']);
			$name_parts = array_merge((array) $info['name'], $name_parts);
			$last = $current;
		}
		return implode (" > ", $name_parts);
	}

	function get_info_for_sem_tree_id($sem_tree_id)
	{
		$db = new DB_Seminar();

		$db->query("SELECT 	st.sem_tree_id, 
												st.parent_id,
								IF (st.name IS NULL OR st.name = '', i.Name, st.name) as name
								FROM sem_tree st
								LEFT JOIN Institute i
								ON (st.studip_object_id = i.Institut_id)
								WHERE st.sem_tree_id = '{$sem_tree_id}'
								GROUP BY st.sem_tree_id");

		$db->next_record();

		return array(	"id" => $db->f("sem_tree_id"),
									"parent_id" => $db->f("parent_id"),
									"name" => $db->f("name"));
	
	}

	function get_subtree($sem_tree_id)
	{
		$db = new DB_Seminar();

		$stack = $collected = array($sem_tree_id);

		while ($current = array_pop($stack))
		{
			$local_tree = StudipLectureTreeHelper::get_local_tree($current);
			$collected = array_merge($collected, $local_tree);
			$stack = array_merge($local_tree, $stack); // depth first
		}

		return $collected;
	}

	/*

	function get_subtree_seminar_count($sem_tree_id)
	{
		$db = new DB_Seminar();

		$subtree_entries = StudipLectureTreeHelper::get_subtree($sem_tree_id);

		$count = 0;

		foreach($subtree_entries as $entry)
		{
			$db->query("SELECT COUNT(sst.seminar_id) as seminar_count
									FROM seminar_sem_tree sst
									INNER JOIN seminare s 
									ON sst.seminar_id = s.Seminar_id
									WHERE sst.sem_tree_id = '{$entry}'
									AND s.visible = 1");

			$db->next_record();

			$count += $db->f("seminar_count");
		}

		return $count;
	}
	 */

	function get_subtree_seminar_count($sem_tree_id, $only_visible = true)
	{
		$db = new DB_Seminar();

		$subtree_entries = StudipLectureTreeHelper::get_subtree($sem_tree_id);

		$count = 0;

		$quoted_entries = array();

		foreach($subtree_entries as $entry)
		{
			$quoted_entries [] = "'".$entry."'";
		}

$start = microtime_float();
		$db->query($query = "SELECT 
			COUNT(sst.seminar_id) as seminar_count
			FROM seminar_sem_tree sst
			INNER JOIN seminare s 
			ON sst.seminar_id = s.Seminar_id
			WHERE sst.sem_tree_id IN (" . implode(",", $quoted_entries) . ") " .
			($only_visible ? ' AND s.visible = 1 ' : ''));

$diff = $start - microtime_float();
var_dump("execution time: $diff");
		$db->next_record();

		$count = $db->f("seminar_count");

		return $count;
	}
	 
	function get_local_tree($sem_tree_id)
	{
		$db = new DB_Seminar();

		$db->query("SELECT * FROM sem_tree
								WHERE parent_id='{$sem_tree_id}' ORDER BY priority");

		$collected = array();
		while ($db->next_record())
		{
			$collected [] = $db->f("sem_tree_id");
		}

		return $collected;
	}

}
 
require_once('studip_seminar.php');

$sem_tree = new StudipLectureTreeHelper();

/*
#$subtree = $sem_tree->get_subtree('940046b9fb784bd621105c76203116f7');  // veranstaltungen
#var_dump($subtree);
$local_tree = $sem_tree->get_local_tree('940046b9fb784bd621105c76203116f7');
	var_dump($sem_tree->get_subtree_seminar_count('940046b9fb784bd621105c76203116f7'));
//$local_tree = $sem_tree->get_local_tree('773ce26cfb745504271d51e4bf3b70fc');
foreach($local_tree as $sub_entry)
{
	//var_dump($sem_tree->get_sem_path($sub_entry));
	var_dump($sem_tree->get_subtree_seminar_count($sub_entry));
}
 */
/*
$subtree = $sem_tree->get_subtree('a393fe239882e5f360ea15b04559d748');
$subtree = $sem_tree->get_subtree('b74e2bdb1df307f7ade5091e4ec98e18'); 
$subtree = $sem_tree->get_subtree('f7ba17b5f0ece0d5e72a6b6cbac9d382');
#
foreach($subtree as $id)
{
	#var_dump($sem_tree->get_info_for_sem_tree_id($id));
	var_dump($sem_tree->get_sem_path($id));
	var_dump($id);
	$seminars = $sem_tree->get_seminars_by_sem_tree_id($id, '5153b9aa7ccc12a5aaec1e2cf9b91624');
	var_dump($seminars);
}
 */
