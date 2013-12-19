<?php
namespace API\Consumer;

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @todo    documentation
 */
class Studip extends Base
{
    public function detect()
    {
        return isset($GLOBALS['auth'])
               && $GLOBALS['auth']->is_authenticated()
               && $GLOBALS['user']->id !== 'nobody';
    }

    public function authenticate()
    {
        return $GLOBALS['user']->id;
    }
}
