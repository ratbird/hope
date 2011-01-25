<?php

/**
 * CSRFProtection.php - protect from request forgery
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      mlunzena@uos.de
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

/**
 * TODO
 */
class CSRFProtection
{
    const TOKEN = 'security_token';

    static function verifySecurityToken()
    {
        if (!self::verifyRequest()) {
            throw new InvalidSecurityTokenException();
        }
    }

    static function verifyRequest()
    {
        return
            !self::protectionEnabled() ||
            self::isGETRequest() ||
            self::isXHR() ||
            self::checkSecurityToken();
    }

    static function protectionEnabled()
    {
        return true;
    }

    static function isGETRequest()
    {
        return strcasecmp($_SERVER['REQUEST_METHOD'], 'GET') === 0;
    }

    static function isXHR()
    {
        return $_SERVER['HTTP_X_REQUESTED_WITH'] &&
            strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') === 0;
    }

    static function checkSecurityToken()
    {
        return isset($_POST[self::TOKEN]) && self::token() === $_POST[self::TOKEN];
    }

    static function token()
    {
        // w/o a session, throw an exception
        if (session_id() === '') {
            throw new SessionRequiredException();
        }

        // create a token, if there is none
        if (!isset($_SESSION[self::TOKEN])) {
            $_SESSION[self::TOKEN] = base64_encode(self::randomBytes(32));
        }

        return $_SESSION[self::TOKEN];
    }

    static function insertToken()
    {
        return sprintf('<input type="hidden" name="%s" value="%s">',
                       self::TOKEN,
                       self::token()
        );
    }


    /**
     * Returns a string of highly randomized bytes (over the full 8-bit range).
     *
     * This function is better than simply calling mt_rand() or any other
     * built-in PHP function because it can return a long string of bytes
     * (compared to < 4 bytes normally from mt_rand()) and uses the best
     * available pseudo-random source.
     *
     * This function was copied from Drupal's includes/bootstrap.inc.
     *
     * @param integer $count The number of characters (bytes) to return in the string.
     */
    static function randomBytes($count)
    {
        static $random_state, $bytes;

        // Initialize on the first call. The contents of $_SERVER includes a mix of
        // user-specific and system information that varies a little with each page.
        if (!isset($random_state)) {
            $random_state = print_r($_SERVER, TRUE);
            if (function_exists('getmypid')) {
                // Further initialize with the somewhat random PHP process ID.
                $random_state .= getmypid();
            }
            $bytes = '';
        }
        if (strlen($bytes) < $count) {
            // /dev/urandom is available on many *nix systems and is considered the
            // best commonly available pseudo-random source.
            if ($fh = @fopen('/dev/urandom', 'rb')) {
                // PHP only performs buffered reads, so in reality it will always read
                // at least 4096 bytes. Thus, it costs nothing extra to read and store
                // that much so as to speed any additional invocations.
                $bytes .= fread($fh, max(4096, $count));
                fclose($fh);
            }
            // If /dev/urandom is not available or returns no bytes, this loop will
            // generate a good set of pseudo-random bytes on any system.
            // Note that it may be important that our $random_state is passed
            // through hash() prior to being rolled into $output, that the two hash()
            // invocations are different, and that the extra input into the first one -
            // the microtime() - is prepended rather than appended. This is to avoid
            // directly leaking $random_state via the $output stream, which could
            // allow for trivial prediction of further "random" numbers.
            while (strlen($bytes) < $count) {
                $random_state = hash('sha256', microtime() . mt_rand() . $random_state);
                $bytes .= hash('sha256', mt_rand() . $random_state, TRUE);
            }
        }
        $output = substr($bytes, 0, $count);
        $bytes = substr($bytes, $count);
        return $output;
    }
}

/**
 * TODO
 */
class InvalidSecurityTokenException extends AccessDeniedException
{
    function __construct() {
        parent::__construct(_("Ungültiges oder fehlendes Sicherheits-Token."));
    }
}

/**
 * TODO
 */
class SessionRequiredException extends Exception
{
    function __construct() {
        parent::__construct(_("Fehlende Session."));
    }
}
