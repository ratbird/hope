<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * CalendarExportFile.class.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */

global $RELATIVE_PATH_CALENDAR;

require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarExport.class.php');
require_once('lib/datei.inc.php');

class CalendarExportFile extends CalendarExport
{

    var $file_name = 'studip';
    var $tmp_file_name;
    var $path;

    function CalendarExportFile(&$writer, $path = "", $file_name = "")
    {
        global $TMP_PATH;

        parent::CalendarExport($writer);

        if ($file_name == "") {
            $this->tmp_file_name = $this->makeUniqueFilename();
            $this->file_name .= "." . $writer->getDefaultFileNameSuffix();
        } else {
            $this->file_name = $file_name;
            $this->tmp_file_name = $file_name;
        }

        if ($path == "")
            $this->path = "$TMP_PATH/export/";

        $this->_writer = $writer;
    }

    function exportFromDatabase($range_id = '', $start = 0, $end = Calendar::CALENDAR_END, $event_types = 'ALL_EVENTS', $sem_ids = NULL, $except = NULL)
    {

        $this->_createFile();
        parent::exportFromDatabase($range_id, $start, $end, $event_types, $sem_ids, $except);
        $this->_closeFile();
    }

    function exportFromObjects(&$events)
    {

        $this->_createFile();
        parent::exportFromObjects($events);
        $this->_closeFile();
    }

    function sendFile()
    {
        global $CANONICAL_RELATIVE_PATH_STUDIP, $_calendar_error;

        if (file_exists($this->path . $this->tmp_file_name)) {
            header('Location: ' . GetDownloadLink($this->tmp_file_name, $this->file_name, 2, 'force'));
        } else {
            $_calendar_error->throwError(ErrorHandler::ERROR_FATAL, _("Die Export-Datei konnte nicht erstellt werden!"), __FILE__, __LINE__);
        }
    }

    function makeUniqueFileName()
    {

        return md5(uniqid(rand() . "Stud.IP Calendar"));
    }

    // returns file handle
    function getExport()
    {

        return $this->export;
    }

    function getFileName()
    {

        return $this->file_name;
    }

    function getTempFileName()
    {

        return $this->tmp_file_name;
    }

    function _createFile()
    {
        global $_calendar_error;

        if (!(is_dir($this->path))) {
            if (!mkdir($this->path)) {
                $_calendar_error->throwError(ErrorHandler::ERROR_FATAL, _("Das Export-Verzeichnis konnte nicht angelegt werden!"), __FILE__, __LINE__);
            } else {
                if (!chmod($this->path, 0777)) {
                    $_calendar_error->throwError(ErrorHandler::ERROR_FATAL, _("Die Zugriffsrechte auf das Export-Verzeichnis konnten nicht ge&auml;ndert werden!")
                            , __FILE__, __LINE__);
                }
            }
        }
        if (file_exists($this->path . $this->tmp_file_name)) {
            if (!unlink($this->path . $this->tmp_file_name)) {
                $_calendar_error->throwError(ErrorHandler::ERROR_FATAL, _("Eine bestehende Export-Datei konnte nicht gel&ouml;scht werden!"), __FILE__, __LINE__);
            }
        }
        $this->export = fopen($this->path . $this->tmp_file_name, "wb");
        if (!$this->export) {
            $_calendar_error->throwError(ErrorHandler::ERROR_FATAL, _("Die Export-Datei konnte nicht erstellt werden!"), __FILE__, __LINE__);
        }
    }

    function _export($exp)
    {

        fwrite($this->export, $exp);
    }

    function _closeFile()
    {

        fclose($this->export);
    }

}
