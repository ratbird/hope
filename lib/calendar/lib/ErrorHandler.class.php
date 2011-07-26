<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * ErrorHandler.class.php
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

require_once($RELATIVE_PATH_CALENDAR . '/lib/Error.class.php');

define('ERROR_NORMAL', 1);
define('ERROR_MESSAGE', 2);
define('ERROR_WARNING', 4);
define('ERROR_CRITICAL', 8);
define('ERROR_FATAL', 16);

function init_error_handler($handler_name)
{
    global $$handler_name;

    static $instantiated = array();

    if (!isset($instantiated[$handler_name])) {
        $$handler_name = new ErrorHandler();
        $instantiated[$handler_name] = true;
    }
}

class ErrorHandler
{

    var $errors;
    var $status;

    function ErrorHandler()
    {

        $this->errors = array();
        $this->status = ERROR_NORMAL;
        $this->_is_instantiated = true;
    }

    function getStatus($status = NULL)
    {

        if ($status === NULL)
            return $this->status;

        return $status & $this->status;
    }

    function getMaxStatus($status)
    {

        if ($status <= $this->status)
            return true;

        return false;
    }

    function getMinStatus($status)
    {

        if ($status >= $this->status)
            return true;

        return false;
    }

    function getErrors($status = NULL)
    {

        if ($status === NULL)
            return $this->errors;

        return $errors[$status];
    }

    function getAllErrors()
    {

        $status = array(ERROR_FATAL, ERROR_CRITICAL, ERROR_WARNING,
            ERROR_MESSAGE, ERROR_NORMAL);
        $errors = array();
        foreach ($status as $stat) {
            if (is_array($this->errors[$stat])) {
                $errors = array_merge($errors, $this->errors[$stat]);
            }
        }
        return $errors;
    }

    function nextError($status)
    {

        if (is_array($this->errors[$status]) &&
                list($key, $error) = each($this->errors[$status])) {
            return $error;
        }

        if (is_array($this->errors[$status]))
            reset($this->errors[$status]);
        return false;
    }

    function throwError($status, $message, $file = '', $line = '')
    {

        $this->errors[$status][] = new Error($status, $message, $file, $line);
        $this->status |= $status;
        reset($this->errors[$status]);
        if ($status == ERROR_FATAL) {
            echo '<b>';
            while ($error = $this->nextError(ERROR_FATAL)) {
                echo '<br />' . $error->getMessage();
            }
            echo '</b><br />';
            page_close();
            exit;
        }
    }

    function throwSingleError($index, $status, $message, $file = '', $line = '')
    {
        static $index_list = array();

        if ($index_list[$index] != 1) {
            $this->throwError($status, $message, $file, $line);
            $index_list[$index] = 1;
        }
    }

}
