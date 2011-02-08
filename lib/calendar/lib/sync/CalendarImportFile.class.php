<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* CalendarImportFile.class.php
* 
* 
* 
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  calendar_modules
* @module       calendar_import
* @package  Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// CalendarImportFile.class.php
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

global $RELATIVE_PATH_CALENDAR;

require_once("$RELATIVE_PATH_CALENDAR/lib/sync/CalendarImport.class.php");

class CalendarImportFile extends CalendarImport {
    
    var $_parser;
    var $file;
    var $path;
    
    /**
    *
    */
    function CalendarImportFile (&$parser, $file, $path = '') {
        
        parent::CalendarImport();
        $this->_parser =& $parser;
        $this->file = $file;
        $this->path = $path;
    }
    
    /**
    *
    */
  function getContent () {
        global $_calendar_error;
    
        $data = '';
        if (!$file = @fopen($this->file['tmp_name'], 'rb')) {
            $_calendar_error->throwError(ERROR_FATAL,
                    _("Die Import-Datei konnte nicht geöffnet werden!"));
            return FALSE;
        }
        
        if ($file) {
            while (!feof($file))
                $data .= fread($file, 1024);
            fclose($file);
        }

        return $data;
    }

    
    /**
    *
    */
    function getFileName () {
    
        return $this->file['name'];
    }
    
    /**
    *
    */
    function getFileType () {
    
        return $this->_parser->getType();
    }
    
    /**
    *
    */
    function getFileSize () {
        
        if (file_exists($this->file['tmp_name']))
            return filesize($this->file['tmp_name']);
        
        return FALSE;
    }
    
    /**
    *
    */
    function checkFile () {
    
        return TRUE;
    }
    
    /**
    *
    */
    function setParser (&$parser) {
    
        $this->_parser = $parser;
    }
    
    /**
    *
    */
    function getCount () {
    
        return $this->_parser->getCount($this->getContent());
    }
    
    /**
    *
    */
    function importIntoDatabase ($ignore = 'IGNORE_ERRORS') {
        global $_calendar_error;
        
        if ($this->checkFile()) {
            if ($this->_parser->parseIntoDatabase($this->getContent(), $ignore))
                return TRUE;
            
            $_calendar_error->throwError(ERROR_CRITICAL,
                    _("Die Datei konnte nicht gelesen werden!"));
            return FALSE;
        }
        
        return FALSE;
    }
    
    /**
    *
    */
    function importIntoObjects ($ignore = 'IGNORE_ERRORS') {
        global $_calendar_error;
    
        if ($this->checkFile()) {
            if ($this->_parser->parseIntoObjects($this->getContent(), $ignore))
                return TRUE;
            
            $_calendar_error->throwError(ERROR_CRITICAL,
                    _("Die Datei konnte nicht gelesen werden!"));
            return FALSE;
        }
        
        return $errors;
    }
    
    function getObjects () {
        
        return $object =& $this->_parser->getObjects();
    }
    
    /**
    *
    */
    function deleteFile () {
        global $_calendar_error;
        
        if (!unlink($this->file['tmp_name'])) {
            $_calendar_error->throwError(ERROR_FATAL,
                    _("Die Datei konnte nicht gel&ouml;scht werden!"));
            return FALSE;
        }
        
        return TRUE;
    }
    
    /**
    *
    */
    function _getFileExtension () {
    
        $i = strrpos($this->file['name'], '.');
        if (!$i)
            return '';

        $l = strlen($this->file['name']) - $i;
        $ext = substr($this->file['name'], $i + 1, $l);

        return $ext;
    }
    
}
?>
