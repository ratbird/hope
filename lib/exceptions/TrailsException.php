<?php
/*
 * TrailsException.php
 *
 * Copyright (c) 2010 Marcus Lunzenauer
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author      Marcus Lunzenauer <mlunzena@uos.de>
 * @copyright   2007 (c) Authors
 * @category    Stud.IP
 * @package     trails
 */

class TrailsException extends Exception
{
    public $headers;

    /**
     * @param  int     the status code to be set in the response
     * @param  string  a human readable presentation of the status code
     * @param  array   a hash of additional headers to be set in the response
     *
     * @return void
     */
    function __construct($status = 500, $reason = NULL, $headers = array())
    {
        if ($reason === NULL) {
            $reason = TrailsResponse::get_reason($status);
        }
        parent::__construct($reason, $status);
        $this->headers = $headers;
    }

    function __toString()
    {
        return "{$this->code} {$this->message}";
    }
}

class TrailsDoubleRenderError extends TrailsException
{

    function __construct()
    {
        $message = "Render and/or redirect were called multiple times in this action. "
                 . "Please note that you may only call render OR redirect, and "
                 . "at most once per action.";
        parent::__construct(500, $message);
    }
}

class TrailsMissingFile extends TrailsException
{

    function __construct($message)
    {
        parent::__construct(500, $message);
    }
}

class TrailsRoutingError extends TrailsException
{

    function __construct($message)
    {
        parent::__construct(400, $message);
    }
}

class TrailsUnknownAction extends TrailsException
{

    function __construct($message)
    {
        parent::__construct(404, $message);
    }
}

class TrailsUnknownController extends TrailsException
{

    function __construct($message)
    {
        parent::__construct(404, $message);
    }
}

class TrailsSessionRequiredException extends TrailsException
{

    function __construct()
    {
        $message = "Tried to access a non existing session.";
        parent::__construct(500, $message);
    }
}
