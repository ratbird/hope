<?php
namespace RESTAPI\Consumer;

/**
 * Stud.IP Session Consumer for the rest api
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @since   Stud.IP 3.0
 */
class Studip extends Base
{
    /**
     * Detects a user via the Stud.IP session. If a session is present and
     * valid, the auth and user object have already been set up by stud.ip
     * functions, so we just need to check if these are present.
     *
     * @return mixed Instance of self if authentication was detected, false
     *               otherwise
     */
    public static function detect()
    {
        if (isset($GLOBALS['auth'])
            && $GLOBALS['auth']->is_authenticated()
            && $GLOBALS['user']->id !== 'nobody')
        {
            return new self(null, $GLOBALS['user']->id);
        }
        return false;
    }
}
