<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * CalendarImportFile.class.php
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

require_once('lib/calendar/CalendarImport.class.php');
require_once('lib/calendar/CalendarExportException.class.php');

class CalendarImportFile extends CalendarImport
{

    private $file;
    private $path;

    /**
     *
     */
    function CalendarImportFile(&$parser, $file, $path = '')
    {
        parent::CalendarImport($parser);
        $this->file = $file;
        $this->path = $path;
    }

    /**
     *
     */
    function getContent()
    {
        $data = '';
        if (!$file = @fopen($this->file['tmp_name'], 'rb')) {
            throw new CalendarExportException(_("Die Import-Datei konnte nicht geöffnet werden!"));
            return false;
        }
        if ($file) {
            while (!feof($file)) {
                $data .= fread($file, 1024);
            }
            fclose($file);
        }
        return $data;
    }

    /**
     *
     */
    function getFileName()
    {
        return $this->file['name'];
    }

    /**
     *
     */
    function getFileType()
    {
        return $this->_parser->getType();
    }

    /**
     *
     */
    function getFileSize()
    {
        if (file_exists($this->file['tmp_name'])) {
            return filesize($this->file['tmp_name']);
        }
        return false;
    }

    /**
     *
     */
    function checkFile()
    {
        return true;
    }

    /**
     *
     */
    function importIntoDatabase($range_id, $ignore = CalendarImport::IGNORE_ERRORS)
    {
        if ($this->checkFile()) {
            parent::importIntoDatabase($range_id, $ignore);
            return true;
        }
        throw new CalendarExportException(_('Die Datei konnte nicht gelesen werden!'));
        return false;
    }

    /**
     *
     */
    function importIntoObjects($ignore = CalendarImport::IGNORE_ERRORS)
    {
        global $_calendar_error;

        if ($this->checkFile()) {
            parent::importIntoObjects($ignore);
            return true;
        }
        throw new CalendarExportException(_('Die Datei konnte nicht gelesen werden!'));
    }

    /**
     *
     */
    function deleteFile()
    {
        if (!unlink($this->file['tmp_name'])) {
            throw new CalendarExportException(_("Die Datei konnte nicht gelöscht werden!"));
            return false;
        }
        return true;
    }

    /**
     *
     */
    function _getFileExtension()
    {
        $i = strrpos($this->file['name'], '.');
        if (!$i) {
            return '';
        }
        $l = strlen($this->file['name']) - $i;
        $ext = substr($this->file['name'], $i + 1, $l);
        return $ext;
    }

}
