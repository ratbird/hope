<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* CalendarExportFile.class.php
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
// CalendarExportFile.class.php
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
 
require_once($RELATIVE_PATH_CALENDAR.'/lib/sync/CalendarExport.class.php');
require_once('lib/datei.inc.php');

class CalendarExportFile extends CalendarExport {
    
    var $writer;
    var $file_name = "studip";
    var $tmp_file_name;
    var $path;
    var $file;
    
    function CalendarExportFile (&$writer, $path = "", $file_name = "") {
        global $TMP_PATH;
        
        parent::CalendarExport($writer);
        
        if ($file_name == "") {
            $this->tmp_file_name = $this->makeUniqueFilename();
            $this->file_name .= "." . $writer->getDefaultFileNameSuffix();
        }
        else {
            $this->file_name = $file_name;
            $this->tmp_file_name = $file_name;
        }
        
        if ($path == "")
            $this->path = "$TMP_PATH/export/";
        
        $this->_writer = $writer;
    }
    
    function exportFromDatabase ($range_id, $start = 0, $end = 2114377200,
            $event_types = "ALL", $except = NULL) {
            
        $this->_createFile();
        parent::exportFromDatabase($range_id, $start, $end, $event_types, $except);
        $this->_closeFile();
    }
    
    function exportFromObjects (&$events) {
        
        $this->_createFile();
        parent::exportFromObjects($events);
        $this->_closeFile();
    }
    
    function sendFile () {
        global $CANONICAL_RELATIVE_PATH_STUDIP, $_calendar_error;
        
        if (file_exists($this->path . $this->tmp_file_name)) {
            header('Location: ' . GetDownloadLink($this->tmp_file_name, $this->file_name, 2, 'force'));
        }
        else {
            $_calendar_error->throwError(ERROR_FATAL,
                    _("Die Export-Datei konnte nicht erstellt werden!"), __FILE__, __LINE__);
        }
    }
    
    function makeUniqueFileName () {
    
        return md5(uniqid(rand() . "Stud.IP Calendar"));
    }
    
    function getExport () {
        // Datei als String zurueckgeben
    }
    
    function getFileName () {
    
        return $this->file_name;
    }
    
    function getTempFileName () {
    
        return $this->tmp_file_name;
    }
    
    function _createFile () {
        global $_calendar_error;
        
        if (!(is_dir($this->path))) {
            if (!mkdir($this->path)) {
                $_calendar_error->throwError(ERROR_FATAL,
                        _("Das Export-Verzeichnis konnte nicht angelegt werden!"), __FILE__, __LINE__);
            }
            else {
                if (!chmod($this->path, 0777)) {
                    $_calendar_error->throwError(ERROR_FATAL,
                        _("Die Zugriffsrechte auf das Export-Verzeichnis konnten nicht ge&auml;ndert werden!")
                        , __FILE__, __LINE__);
                }
            }
        }
        if (file_exists($this->path . $this->tmp_file_name)) {
            if (!unlink($this->path . $this->tmp_file_name)) {
                $_calendar_error->throwError(ERROR_FATAL,
                        _("Eine bestehende Export-Datei konnte nicht gel&ouml;scht werden!"), __FILE__, __LINE__);
            }
        }
        $this->file = fopen($this->path . $this->tmp_file_name, "wb");
        if (!$this->file) {
            $_calendar_error->throwError(ERROR_FATAL,
                        _("Die Export-Datei konnte nicht erstellt werden!"), __FILE__, __LINE__);
        }
    }
    
    function _export ($string) {
    
        fwrite($this->file, $string);
    }
    
    function _closeFile () {
    
        fclose($this->file);
    }
    
}
?>
