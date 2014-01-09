<?php
namespace RESTAPI\Consumer;

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @since   Stud.IP 3.0
 */
class Studip extends Base
{
    public static function detect()
    {
        if (isset($GLOBALS['auth']) && $GLOBALS['auth']->is_authenticated() && $GLOBALS['user']->id !== 'nobody') {
            return new self(null, $GLOBALS['user']->id);
        }
    }
}
