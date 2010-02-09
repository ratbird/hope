<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * studip_session.php - base class for session/authorization infos
 *
 * Copyright (C) 2006 - Marco Diedrich (mdiedric@uos.de)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('lib/webservices/api/studip_seminar.php');
require_once('lib/classes/Token.class.php');
require_once('lib/functions.php');

class StudipSessionHelper 
{

	function is_session_valid($session_id)
	{
		return Token::is_valid($session_id) != null;
	}

	function get_session_user_id($session_id)
	{
		return Token::is_valid($session_id);
	}

	function get_session_username($session_id)
	{
		$user_id = Token::is_valid($session_id);
		if (! empty($user_id))
		{
			return get_username($user_id);
		} else
		{
			return null;
		}
	}

}


