<?php
namespace API\Consumer;
use StudipAuthAbstract, API\RouterException;

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @todo    documentation
 */
class HTTP extends Base
{
    public static function detect()
    {
        if (false && isset($_SERVER['HTTP_AUTHORIZATION'])
            || isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
        {
            $user_id = false;
            
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                list($username, $password) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            } elseif (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                $username = $_SERVER['PHP_AUTH_USER'];
                $password = $_SERVER['PHP_AUTH_PW'];
            }

            $check = StudipAuthAbstract::CheckAuthentication($username, $password);
            if (!$check['uid'] || $check['uid'] == 'nobody') {
                throw new RouterException(401, 'HTTP Authentication failed');
            }

            return new self(null, $check['uid']);
        }
    }
}
