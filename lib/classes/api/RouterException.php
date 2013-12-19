<?php
/**
 * Router exception.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @todo
 */

namespace API;
use \Exception;

class RouterException extends Exception
{
    protected static $error_messages = array(
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal Server Error',
        501 => 'Not implemented',
    );
    
    public function __construct($code = 500, $message = '', $previous = null)
    {
        $message = $message ?: self::$error_messages[$code] ?: '';
        parent::__construct($message, $code, $previous);
    }
}
