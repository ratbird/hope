<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * studip_seminar.php - Seminar API for Stud.IP webservice
 *
 * Copyright (C) 2006 - Marco Diedrich (mdiedric@uos.de)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once("lib/classes/Seminar.class.php");
require_once("lib/webservices/api/studip_user.php");
require_once("lib/webservices/api/studip_institute.php");
require_once("lib/webservices/api/studip_session.php");

class StudipSeminarHelper
{
	function StudipSeminarHelper()
	{
		$this->status_conversion = array(	'dozent' 	=> 'lecturer',
				'tutor'   => 'tutor',
				'autor'  	=> 'author',
				'user'		=> 'user');
	}
	function get_title($seminar_id)
	{
		$seminar =& new Seminar($seminar_id);
		return $seminar->getName();
	}

	function validate_seminar_permission($ticket, $seminar_id, $permission)
	{
		$username = StudipSessionHelper::get_session_username($ticket);

		if (in_array($username, StudipSeminarHelper::get_participants($seminar_id, $permission))
				|| in_array($username, StudipSeminarHelper::get_admins_for_seminar($seminar_id)))
		{
			return $username;
		} else
		{
			return FALSE;
		}
	}

	function get_user_status($username, $seminar_id)
	{
		$db =& new DB_Seminar();
		$query= "	SELECT su.status, au.username 
							FROM seminar_user su
							INNER JOIN auth_user_md5 au
							ON (au.user_id = su.user_id) 
							WHERE su.Seminar_id = '$seminar_id'
							AND au.username = '$username';";

		$db->query($query);
		if ($db->next_record())
		{
			return $db->f('status');
		}

		$admin_list = StudipSeminarHelper::get_admins_for_seminar($seminar_id);

		if (in_array($username, $admin_list))
		{
			return 'admin';
		}

		return FALSE;
	}

	function &get_participants($seminar_id, $status='all')
	{
		$db =& new DB_Seminar();
		$query= "	SELECT su.status, au.username 
							FROM seminar_user su
							INNER JOIN auth_user_md5 au
							ON (au.user_id = su.user_id) 
							WHERE su.Seminar_id = '$seminar_id'";

		if ("all" != $status)
		{
			$query .= " AND su.status = '$status'";
		} 

		$db->query($query);
		$userlist = array();
		while ($db->next_record())
		{
			$userlist [] = $db->f("username");
		}
		
		return $userlist;
	}

	function &get_main_institute($seminar_id)
	{
		$db =& new DB_Seminar();
		
		$db->query("SELECT s.Institut_id FROM seminare s
								WHERE s.Seminar_id = '$seminar_id'");

		if ($db->next_record())
		{
			return $db->f("Institut_id");
		}

		return null;
	}

	function &get_additional_institutes($seminar_id)
	{
		$db =& new DB_Seminar();
		
		$db->query("SELECT si.institut_id FROM seminar_inst si
								WHERE si.seminar_id = '$seminar_id'");
		
		$institute_list = array();

		while ($db->next_record())
		{
			$institute_list [] = $db->f("institut_id");
		}

		return $institute_list;
	}

	function &get_all_institutes($seminar_id)
	{
		$institute_list = array_unique(array_merge(array(StudipSeminarHelper::get_main_institute($seminar_id)), StudipSeminarHelper::get_additional_institutes($seminar_id)));
		return $institute_list;
	}

	function get_admins_for_seminar($seminar_id)
	{
		$db =& new DB_Seminar();
		$all_institutes = StudipSeminarHelper::get_all_institutes($seminar_id);
		$admins = array();
		
		foreach($all_institutes as $institute)
		{
			$admins = array_merge($admins, StudipInstituteHelper::get_admins_upward_recursive($institute));
		}
		
		$admins = array_merge($admins, Studip_User::find_by_status('root'));

		return $admins;
	}

	function get_seminar_groups($seminar_id)
	{
		$db =& new DB_Seminar();

		$db->query("SELECT st.name FROM statusgruppen st
								INNER JOIN seminare s 
								ON (st.range_id = s.Seminar_id)
								WHERE s.Seminar_id = '$seminar_id'; ");

		$group_list = array();

		while ($db->next_record())
		{
			$group_list [] = $db->f("name");
		}
		return $group_list;
	}

	function get_seminar_group_members($seminar_id, $group_name)
	{
		$db =& new DB_Seminar();

		$db->query($query = " SELECT au.username FROM statusgruppen st
													INNER JOIN seminare s 
													ON (st.range_id = s.Seminar_id)
													INNER JOIN statusgruppe_user su
													ON (st.statusgruppe_id = su.statusgruppe_id)
													INNER JOIN auth_user_md5 au
													ON (su.user_id = au.user_id)
													WHERE s.Seminar_id = '$seminar_id'
													AND st.name='$group_name'; ");

		$user_list = array();

		while ($db->next_record())
		{
			$user_list[] =& Studip_User::find_by_user_name($db->f("username"));
		}
		return $user_list;
		
	}
	
}
