<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* Error.class.php
* 
* 
* 
*
* @author		Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access		public
* @modulegroup	calendar_modules
* @module		Calendar
* @package	calendar_export
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// Error.class.php
// 
// Copyright (C) 2003 Peter Thienel <pthienel@web.de>,
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+


class Error {

	var $status;
	var $message;
	var $file;
	var $line;
	
	function Error ($status, $message, $file = '', $line = '') {
	
		$this->status = $status;
		$this->message = $message;
		$this->file = $file;
		$this->line = $line;
	}
	
	function getStatus () {
		
		return $this->status;
	}
	
	function getMessage () {
		
		return $this->message;
	}
	
	function getFile () {
	
		return $this->file;
	}
	
	function getLine () {
	
		return $this->line;
	}
	
}

		
