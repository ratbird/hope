<?php
/**
 * TrailsDispatcher.php
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

/**
 * The Dispatcher is used to map an incoming HTTP request to a Controller
 * producing a response which is then rendered. To initialize an instance of
 * class TrailsDispatcher you have to give three configuration settings:
 *
 * trails_root - the absolute file path to a directory containing the
 * applications controllers, views etc.
 * trails_uri - the URI to which routes to mapped Controller/Actions
 * are appended
 * default_controller - the route to a controller, that is used if no
 * controller is given, that is the route is equal to '/'
 *
 * After instantiation of a dispatcher you have to call method #dispatch with
 * the request uri to be mapped to a controller/action pair.
 */
class TrailsDispatcher
{
    # TODO (mlunzena) Konfiguration muss anders geschehen
    /**
     * This is the absolute file path to the trails application directory.
     *
     * @access public
     * @var    string
     */
    public $trails_root;
    /**
     * This is the URI to which routes to controller/actions are appended.
     *
     * @access public
     * @var    string
     */
    public $trails_uri;
    /**
     * This variable contains the route to the default controller.
     *
     * @access public
     * @var    string
     */
    public $default_controller;

    /**
     * Constructor.
     *
     * @param  string  absolute file path to a directory containing the
     * applications controllers, views etc.
     * @param  string  the URI to which routes to mapped Controller/Actions
     * are appended
     * @param  string  the route to a controller, that is used if no
     * controller is given, that is the route is equal to '/'
     *
     * @return void
     */
    function __construct($trails_root, $trails_uri, $default_controller)
    {
        $this->trails_root = $trails_root;
        $this->trails_uri = $trails_uri;
        $this->default_controller = $default_controller;
    }

    /**
     * Maps a string to a response which is then rendered.
     *
     * @param string The requested URI.
     *
     * @return void
     */
    function dispatch($uri)
    {
        # E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE|E_RECOVERABLE_ERROR = 5888
        $old_handler = set_error_handler(array($this, 'errorHandler'), 5888);
        ob_start();
        $level = ob_get_level();
        $this->mapUriToResponse(
        $this->cleanRequestUri((string) $uri))->output();
        while (ob_get_level() >= $level) {
            ob_end_flush();
        }
        if (isset($old_handler)) {
            set_error_handler($old_handler);
        }
    }

    /**
     * Maps an URI to a response by figuring out first what controller to
     * instantiate, then delegating the unconsumed part of the URI to the
     * controller who returns an appropriate response object or throws a
     * TrailsException.
     *
     * @param  string  the URI string
     *
     * @return mixed   a response object
     */
    function mapUriToResponse($uri)
    {
        try {
            if ('' === $uri) {
                if (! $this->fileExists($this->default_controller . '.php')) {
                    throw new TrailsMissingFile("Default controller '{$this->default_controller}' not found'");
                }
                $controller_path = $this->default_controller;
                $unconsumed = $uri;
            } else {
                list ($controller_path, $unconsumed) = $this->parse($uri);
            }
            $controller = $this->loadController($controller_path);
            $response = $controller->perform($unconsumed);
        } catch (Exception $e) {
            $response = isset($controller) ? $controller->rescue($e) : $this->trails_error($e);
        }
        return $response;
    }

    /**
     * TODO: Fehlermeldung an Stud.IP anpassen
     *
     * @param $exception
     */
    function trails_error($exception)
    {
        ob_clean();
        # show details for local requests
        $detailed = @$_SERVER['REMOTE_ADDR'] === '127.0.0.1';
        $body = sprintf('<html><head><title>Trails Error</title></head>'
              . '<body><h1>%s</h1><pre>%s</pre></body></html>',
              htmlentities($exception->__toString()),
        $detailed ? htmlentities(
        $exception->getTraceAsString()) : '');
        if ($exception instanceof TrailsException) {
            $response = new TrailsResponse($body, $exception->headers, $exception->getCode(), $exception->getMessage());
        } else {
            $response = new TrailsResponse($body, array(), 500, $exception->getMessage());
        }
        return $response;
    }

    /**
     * Clean up URI string by removing the query part and leading slashes.
     *
     * @param  string  an URI string
     *
     * @return string  the cleaned string
     */
    function cleanRequestUri($uri)
    {
        if (FALSE !== ($pos = strpos($uri, '?'))) {
            $uri = substr($uri, 0, $pos);
        }
        return ltrim($uri, '/');
    }

    /**
     * <MethodDescription>
     *
     * @param  type       <description>
     * @param  type       <description>
     *
     * @return type       <description>
     */
    function parse($unconsumed, $controller = NULL)
    {
        list ($head, $tail) = $this->splitOnFirstSlash($unconsumed);
        if (!preg_match('/^\w+$/', $head)) {
            throw new TrailsRoutingError("No route matches '$head'");
        }
        $controller = (isset($controller) ? $controller . '/' : '') . $head;
        if ($this->fileExists($controller . '.php')) {
            return array($controller, $tail);
        } elseif ($this->fileExists($controller)) {
            return $this->parse($tail, $controller);
        }
        throw new TrailsRoutingError("No route matches '$head'");
    }

    function splitOnFirstSlash($str)
    {
        preg_match(":([^/]*)(/+)?(.*):", $str, $matches);
        return array($matches[1], $matches[3]);
    }

    function fileExists($path)
    {
        return file_exists("{$this->trails_root}/controllers/$path");
    }

    /**
     * Loads the controller file for a given controller path and return an
     * instance of that controller. If an error occures, an exception will be
     * thrown.
     *
     * @param  string            the relative controller path
     *
     * @return TrailsController  an instance of that controller
     */
    function loadController($controller)
    {
        require_once "{$this->trails_root}/controllers/{$controller}.php";
        $class = TrailsInflector::camelize($controller) . 'Controller';
        if (! class_exists($class)) {
            throw new TrailsUnknownController("Controller missing: '$class'");
        }
        return new $class($this);
    }

    /**
     * This method transforms E_USER_* and E_RECOVERABLE_ERROR to
     * TrailsExceptions.
     *
     * @param  integer    the level of the error raised
     * @param  string     the error message
     * @param  string     the filename that the error was raised in
     * @param  integer    the line number the error was raised at
     * @param  array      an array of every variable that existed in the scope the
     * error was triggered in
     *
     * @throws TrailsException
     *
     * @return void
     */
    function errorHandler($errno, $string, $file, $line, $context)
    {
        throw new TrailsException(500, $string);
    }
}