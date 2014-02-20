<?php
namespace RESTAPI;
use Request, Config;

/**
 * RouteMaps define and group routes to resources.
 *
 * Instances of RouteMaps are registered with the RESTAPI\Router to
 * participate in the routing business.
 *
 * A RouteMap defines at least one handler method which has to be
 * annotated with one of these annotations correlating to HTTP request
 * methods:
 *
 * @code
 * /**
 *  * An example handler method
 *  *
 *  * @get /foo
 *  * @post /bar/:id
 *  * @put /baz/:id/:other_id
 *  * @delete /
 *  * /
 *  public function anyMethodName($id, $other_id = null) {}
 * @endcode
 *
 * As soon as the Router matches a HTTP request to a handler defined
 * in a RouteMap, it calls RouteMap::init to initialize it and
 * especially the instance field `$this->response` of type
 * RESTAPI\Response. You do not call RouteMap::init on your own.
 *
 * After the router has initialized this RouteMap, the router tries to
 * call a method `before` of this signature:
 *
 * @code
 * public function before(Router $router, Array $handler, Array $parameters);
 * @endcode
 *
 * The parameter `$handler` is a callable (as in function is_callable)
 * consisting of the instance of this RouteMap and the name of a
 * method of this instance. You may change the values of this array to
 * redirect to another handler.
 *
 * The parameter `$parameters` is an associative array whose keys
 * correlate to the placeholders in the matched URI template. The
 * values are the actual values of that placeholders in regard to the
 * HTTP request.
 *
 *
 * After calling RouteMap::before control is transfered to the actual
 * handler method. The values of the placeholders in the URI template
 * of the annotation are send as arguments to the handler.
 *
 * Example: We have got this handler method defined:
 *
 * @code
 * /**
 *  * @get /foo/:id/bar/:other_id
 *  * /
 * public function fooHandler($id, $other_id) {
 * }
 * @endcode
 *
 * The router receives a request like this: `http://[..]/foo/1/bar/2`
 * and matches it to our `fooHandler` which is then called something
 * like that:
 *
 * @code
 * $result = $routeMap->fooHandler(1, 2);
 * @encode
 *
 * In your handler methods you have to process the input and return
 * some output data, which is then rendered in an appropriate way
 * after negotiating the content format in the Router.
 *
 * Thus the return value of your handler method becomes the body of
 * the HTTP response.
 *
 *
 * The RouteMap class defines several methods to ease up your work
 * with the HTTP specifica.
 *
 * The methods RouteMap::status, RouteMap::headers and RouteMap::body
 * correlate to the components of a HTTP response.
 *
 * There are helpers for returning paginated collections, see
 * RouteMap::paginated.
 *
 * If you encounter an error or have to stop further processing, see
 * methods RouteMap::halt, RouteMap::error and RouteMap::notFound.
 *
 * These methods are \a DISRUPTIVE as they immediately stop the control
 * flow in your handler:
 *
 * @code
 * public function fooHandler($id)
 * {
 *   // do something
 *
 *   $this->halt();
 *
 *   // this line will never be reached
 * }
 * @endcode
 *
 * If you want to simply send a redirection response (HTTP status code
 * of 302 or 303), you may find calling RouteMap::redirect helpful.
 *
 * To generate a URL to a handler, use RouteMap::url
 *
 * When you find the need to return the content of a file, please see
 * RouteMap::sendFile which will help you with streaming it to the
 * client. For custom streaming just return a Closure from your
 * handler method.
 *
 * There are several other methods which you may find useful each
 * matching a HTTP header:
 *
 *   - RouteMap::contentType
 *   - RouteMap::etag
 *   - RouteMap::expires
 *   - RouteMap::cacheControl
 *   - RouteMap::lastModified
 *
 * TODO: describe the $this->data stuff.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 * @since   Stud.IP 3.0
 */
abstract class RouteMap
{
    /**
     * Internal property which is used by RouteMap::paginated and
     * contains everything about a paginated collection.
     */
    protected $pagination = false;

    /**
     * The offset into a RouteMap::paginated collection as requested
     * by the client.
     */
    protected $offset;

    /**
     * The limit of a RouteMap::paginated collection as requested
     * by the client.
     */
    protected $limit;

    /**
     * Constructor of the route map. Initializes neccessary offset and limit
     * parameters for pagination.
     */
    public function __construct()
    {
        $this->offset = Request::int('offset', 0);
        $this->limit  = Request::int('limit', Config::get()->ENTRIES_PER_PAGE);
    }

    /**
     * Initializes the route map by binding it to a router and passing in
     * the current route.
     *
     * @param RESTAPI\Router $router Router to bind this route map to
     * @param Array          $route  The matched route out of
     *                               Router::matchRoute; an array with keys
     *                               'handler', 'conditions' and 'source'
     */
    public function init($router, $route)
    {
        $this->router   = $router;
        $this->route    = $route;
        $this->response = new Response();

        if ($mediaType = $this->getRequestMediaType()) {
            $this->data = studip_utf8decode($this->parseRequestBody($mediaType));
        }
    }

    /**
     * Marks this chunk of data as a slice of a larger data set with
     * a sum of "total" entries.
     *
     * @param mixed $data         Chunk of data (should be sliced according
     *                            to current offset and limit parameters).
     * @param int   $total        The total number of data entries in the
     *                            according set.
     * @param array $uri_params   Neccessary parameters when generating uris
     *                            for the current route.
     * @param array $query_params Optional query parameters.
     */
    public function paginated($data, $total, $uri_params = array(), $query_params = array())
    {
        $uri = $this->url($this->route['uri_template']->inject($uri_params), $query_params);

        $this->paginate($uri, $total);
        return $this->collect($data);
    }


    /**
     * Low level method for paginating collections. You better use
     * RouteMap::paginated instead of this.
     *
     * Set the pagination data used by the RouteMap::collect.
     *
     * @param String $uri_format
     * @param int    $total
     * @param mixed  $offset
     * @param mixed  $limit
     *
     * @return RESTAPI\Routemap Returns instance of self to allow chaining
     */
    public function paginate($uri_format, $total, $offset = null, $limit = null)
    {
        $total  = (int)$total;
        $offset = (int)($offset ?: $this->offset ?: 0);
        $limit  = (int)($limit ?: $this->limit);

        $this->pagination = compact('uri_format', 'total', 'offset', 'limit');

        return $this;
    }

    /**
     * Low level method for paginating collections. You better use
     * RouteMap::paginated instead of this.
     *
     * Adjusts the result set to return a collection. A collection consists
     * of the passed data array and the associated pagination information
     * if available.
     *
     * Be aware that the passed data has to be already sliced according to
     * the pagination information.
     *
     * @param Array $data Actual dataset
     * @return Array Collection "object"
     */
    public function collect($data)
    {
        $collection = array(
            'collection' => $data
        );
        if ($this->pagination) {
            extract($this->pagination);

            $offset = $offset - $offset % $limit;
            $max    = ($total % $limit)
                    ? $total - $total % $limit
                    : $total - $limit;

            $pagination = compact('total', 'offset', 'limit');
            if ($total > $limit) {
                $links = array();

                foreach (array(
                             'first' => 0,
                             'previous' => max(0, $offset - $limit),
                             'next' => min($max, $offset + $limit),
                             'last' => $max)
                         as $key => $offset)
                {
                    $links[$key] = \URLHelper::getURL($uri_format, compact('offset', 'limit'));
                }

                $pagination['links'] = $links;
            }
            $collection['pagination'] = $pagination;
        }
        return $collection;
    }

    /************************/
    /* REQUEST BODY METHODS */
    /************************/

    // find the requested media type
    private function getRequestMediaType()
    {
        if ($contentType = $_SERVER['CONTENT_TYPE']) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            return strtolower($contentTypeParts[0]);
        }
    }

    // media-types that we know how to process
    private static $mediaTypes = array(
        'application/json' => 'parseJson',
        'application/x-www-form-urlencoded' => 'parseFormEncoded');

    // cache the request body
    private static $_request_body;

    // reads the HTTP request body
    private function parseRequestBody($mediaType)
    {
        // read it only once
        if (!isset(self::$_request_body)) {
            self::$_request_body = file_get_contents('php://input');
        }

        if (isset(self::$mediaTypes[$mediaType])) {
            $result = call_user_func(array(__CLASS__, self::$mediaTypes[$mediaType]), self::$_request_body);
            if ($result) {
                return $result;
            }
        }
        return self::$_request_body;
    }

    // strategy to decode JSON strings
    private static function parseJson($input)
    {
        return json_decode($input, true);
    }

    // strategy to decode form encoded strings
    private static function parseFormEncoded($input)
    {
        parse_str($input, $result);
        return $result;
    }


    /**
     * Set the HTTP status of the current response.
     *
     * @param integer $status  the HTTP status of the response
     */
    public function status($status)
    {
        $this->response->status = $status;
    }

    /**
     * Set multiple response headers of the current response.
     *
     * @code
     * $routemap->headers(array('X-example' => "yep"));
     * @endcode
     *
     * @param array $headers  the headers to set
     *
     * @return array  the headers of the current response
     */
    public function headers($headers = array())
    {
        if (sizeof($headers)) {
            $this->response->headers = array_merge($this->response->headers, $headers);
        }
        return $this->response->headers;
    }

    /**
     * Set the HTTP body of the current response.
     *
     * @param string $body  the body to send back
     */
    public function body($body)
    {
        $this->response->body = $body;
    }


    // Set the Content-Type of the response body given a media type or
    // file extension.
    //
    // @TODO
    public function contentType($mime_type, $params = array())
    {
        if (!isset($params['charset'])) {
            $params['charset'] = 'windows-1252';
        }

        if (strpos($mime_type, 'charset') !== FALSE) {
            unset($params['charset']);
        }

        if (sizeof($params)) {
            $mime_type .= strpos($mime_type, ';') !== FALSE ? ', ' : ';';
            $ps = array();
            foreach ($params as $k => $v) {
                $ps[] = $k . '=' . $v;
            }
            $mime_type .= join(', ', $ps);
        }

        $this->response['Content-Type'] = $mime_type;
    }

    // Halt processing and return the error status provided.
    public function error($status, $body = null)
    {
        $this->halt($status, array(), $body);
    }


    // Set the response entity tag (HTTP 'ETag' header) and halt if
    // conditional GET matches. The value argument is an identifier
    // that uniquely identifies the current version of the
    // resource. The strong_etag argument indicates whether the etag
    // should be used as a strong (default) or weak cache
    // validator. When the current request includes an 'If-None-Match'
    // header with a matching etag, execution is immediately
    // halted. If the request method is safe (GET, HEAD, ...) , a '304
    // Not Modified' response is sent.
    public function etag($value, $strong_etag = true, $new_resource = null)
    {
        // Before touching this code, please double check RFC 2616
        // 14.24 and 14.26.

        if (!isset($new_resource)) {
            $new_resource = Request::isPost();
        }

        $value = '"' . $value . '"';
        if (!$strong_etag) {
            $value = 'W/' . $value;
        }
        $this->response['ETag'] = $value;

        if ($this->response->isSuccess() || $this->response->status === 304) {
            if ($this->etagMatches($_SERVER['HTTP_IF_NONE_MATCH'], $new_resource)) {
                $this->halt($this->isRequestSafe() ? 304 : 412);
            }
            if (isset($_SERVER['HTTP_IF_MATCH'])
                && !$this->etagMatches($$_SERVER['HTTP_IF_MATCH'], $new_resource)) {
                $this->halt(412);
            }
        }
    }

    // Helper method checking if a ETag value list includes the current ETag.
    private function etagMatches($list, $new_resource)
    {
        if ($list === '*') {
            return !$new_resource;
        }

        return in_array($this->response['ETag'],
                        preg_split('/\s*,\s*/', $list));
    }

    // Helper method checking if the request is safe
    private function isRequestSafe()
    {
        $method = Request::method();
        return $method === 'GET' or $method === 'HEAD' or $method === 'OPTIONS' or $method === 'TRACE';
    }

    // Set the Expires header and Cache-Control/max-age directive. Amount
    // can be an integer number of seconds in the future indicating
    // when the response should be considered "stale". The $cache_control
    // argument is passed to #cacheControl
    public function expires($amount, $cache_control = array())
    {
        $time = time() + $amount;
        $max_age = $amount;

        $cache_control[] = "max-age=$max_age";
        $this->cacheControl($cache_control);

        $this->response['Expires'] = $this->httpDate($time);
    }

    public function cacheControl($values)
    {
        if (is_array($values) && sizeof($values)) {
            $this->response['Cache-Control'] = join(', ', $values);
        }
    }

    // To immediately stop a request within a filter or route use:
    // $this->halt()
    //
    // You can also specify the status when halting:
    // $this->halt(410)
    //
    // Or the body:
    // $this->halt('this will be the body')
    //
    // Or both:
    // $this->halt(401, 'go away!')
    //
    // With headers:
    // $this->halt(402, array('Content-Type' => 'text/plain'), 'revenge')
    //
    // used in #lastModified, #redirect etc.
    public function halt(/* [status], [headers], [body] */)
    {
        $args   = func_get_args();
        $result = array();

        $constraints = array(
            'status'  => 'is_int',
            'headers' => 'is_array',
            'body'    => function ($i) { return isset($i); } // #existy
        );
        foreach ($constraints as $state => $constraint) {
            if ($constraint(current($args))) {
                call_user_func(array($this, $state), array_shift($args));
            }
        }

        throw new RouterHalt($this->response);
    }

    // Set the last modified time of the resource (HTTP Last-Modified
    // header) and halt if conditional GET matches. The time argument
    // is a Time, DateTime, or other object that responds to to_time.
    // When the current request includes an 'If-Modified-Since' header
    // that is equal or later than the time specified, execution is
    // immediately halted with a '304 Not Modified' response.
    public function lastModified($time)
    {

        $this->response['Last-Modified'] = $this->httpDate($time);

        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return;
        }

        if ($this->response->status === 200
            && isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            // compare based on seconds since epoch
            $since = $this->httpdate($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($since >= (int) $time) {
                $this->halt(304);
            }
        }

        if (($this->response->isSuccess() || $this->response->status === 412)
            && isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE'])) {

            // compare based on seconds since epoch
            $since = $this->httpdate($_SERVER['HTTP_IF_UNMODIFIED_SINCE']);

            if ($since < (int) $time) {
                $this->halt(412);
            }
        }
    }

    private function httpDate($timestamp)
    {
        return gmdate('D, d M Y H:i:s \G\M\T', (int) $timestamp);
    }

    // Halt processing and return a 404 Not Found.
    public function notFound($body = null)
    {
        $this->halt(404, $body);
    }

    // Halt processing and redirect to the URI provided.
    public function redirect($url, $args = null)
    {
        $this->status($_SERVER["SERVER_PROTOCOL"] === 'HTTP/1.1' && !Request::isGet() ? 303 : 302);
        $this->response['Location'] = $this->url($url);

        $args = array_slice(func_get_args(), 1);
        call_user_func_array(array($this, 'halt'), $args);
    }



    public function sendFile($_path, $opts=array())
    {
        $path = realpath($_path);

        if (isset($opts['type'])) {
            $this->contentType($opts['type']);
        } else if (!isset($this->response['Content-Type'])) {
            $this->contentType(mime_content_type($path));
        }

        if ($opts['disposition'] === 'attachment' || isset($opts['filename'])) {
            $this->response['Content-Disposition'] = 'attachment';
            $filename = $opts['filename'] ?: $path;
            $this->response['Content-Disposition'] .= sprintf('; filename="%s"', basename($filename));
        }

        elseif ($opts['disposition'] === 'inline') {
            $this->response['Content-Disposition'] = 'inline';
        }

        // TODO add HTTP 'Range' support

        $size = filesize($path);
        $this->response['Content-Length'] = $size;

        $this->halt(200, $this->response->headers, function () use ($path) { readfile($path); });
    }


    // Generates the absolute URI for a given path
    public function url($addr, $url_params = null)
    {
        $addr = ltrim($addr, '/');
        return \URLHelper::getURL("api.php/$addr", $url_params, true);
    }

    // Generates the absolute URI for a given path
    public function urlf($addr_f, $format_params, $url_params = null)
    {
        return $this->url(vsprintf($addr_f, $format_params), $url_params);
    }
}
