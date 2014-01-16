<?php
/** @namespace RESTAPI
 *
 * Im Namensraum RESTAPI sind alle Klassen und Funktionen versammelt,
 * die f�r die RESTful Web Services von Stud.IP ben�tigt werden.
 */
namespace RESTAPI;
use DocBlock, BadMethodCallException;

/**
 * Die Aufgabe des Routers ist das Anlegen und Auswerten eines
 * Mappings von sogenannten Routen (Tupel aus HTTP-Methode und Pfad)
 * auf Code.
 *
 * Dazu werden zun�chst Routen mittels der Funktion
 * Router::registerRoutes registriert.
 *
 * Wenn dann ein HTTP-Request eingeht, kann mithilfe von
 * Router::dispatch und HTTP-Methode bzw. Pfad der zugeh�rige Code
 * gefunden und ausgef�hrt werden. Der Router bildet aus dem
 * R�ckgabewert des Codes ein Response-Objekt, das er als Ergebnis
 * zur�ck meldet.
 *
 * @code
 * $router = Router::getInstance();
 *
 * // register a sample Route
 * $router->registerRoutes(new ExampleRoute);
 *
 * // dispatch to therein defined Routes
 * $response = $router->dispatch('/example', 'GET');
 *
 * // render response
 * $response->output();
 *
 * @endcode
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 * @see     Inspired by http://blog.sosedoff.com/2009/07/04/simpe-php-url-routing-controller/
 * @since   Stud.IP 3.0
 */
class Router
{
    // instances are cached here
    protected static $instances = array();

    /**
     * Returns (and if neccessary, initializes) a (cached) router object for an
     * optional consumer id.
     *
     * @param mixed $consumer_id ID of the consumer (defaults to 'global')
     *
     * @return Router returns the Router instance associated to the
     *                consumer ID (or to the 'global' ID)
     */
    public static function getInstance($consumer_id = null)
    {
        $consumer_id = $consumer_id ?: 'global';

        if (!isset(self::$instances[$consumer_id])) {
            self::$instances[$consumer_id] = new self($consumer_id);
        }
        return self::$instances[$consumer_id];
    }

    // All supported method need to be defined here
    protected $supported_methods = array('get', 'post', 'put', 'delete');

    // registered routes by method and uri template
    protected $routes = array();

    // registered content renderers
    protected $renderers = array();

    // identified or forced content renderer
    protected $content_renderer = false;

    // default renderer
    protected $default_renderer = false;

    // registered conditions
    protected $conditions = array();

    // registered descriptions
    protected $descriptions = array();

    // registered consumers
    protected $consumers = array();

    // associated permissions
    protected $permissions = false;

    /**
     * Constructs the router.
     *
     * @param mixed $consumer_id  the ID of the consumer this router
     *                             should associate to
     */
    protected function __construct($consumer_id)
    {
        $this->permissions = ConsumerPermissions::get($consumer_id);
        $this->registerRenderer(new Renderer\DefaultRenderer);
    }

    /**
     * Registers a handler for a specific combination of request method
     * and uri template.
     *
     * @param String  $request_method   expected HTTP request method
     * @param String  $uri_template     expected URI template, for
     *                                  example: \code "/user/:user_id/events" \endcode
     * @param Array   $handler          request handler array:
     *                                  \code array($object, "methodName") \endcode
     * @param Array   $conditions       (optional) an associative
     *                                  array using the name of
     *                                  parameters as keys and regexps
     *                                  as value
     * @param string  $source           (optional) this denotes the
     *                                  origin of a route. Usually
     *                                  either 'core' or 'plugin', but
     *                                  defaults to 'unknown'.
     *
     * @return Router  returns itself to allow chaining
     * @throws Exception  if passed HTTP request method is not supported
     */
    public function register($request_method, $uri_template, $handler, $conditions = array(), $source = 'unknown')
    {
        // Normalize method and test whether it's supported
        $request_method = strtolower($request_method);
        if (!in_array($request_method, $this->supported_methods)) {
            throw new \Exception('Method "' . $request_method . '" is not supported.');
        }

        // Initialize routes storage for this method if neccessary
        if (!isset($this->routes[$request_method])) {
            $this->routes[$request_method] = array();
        }

        // Normalize uri template (always starts with a slash)
        if ($uri_template[0] !== '/') {
            $uri_template = '/' . $uri_template;
        }

        // Sanitize conditions
        foreach ($conditions as $var => $pattern) {
            if ($pattern[0] !== $pattern[strlen($pattern) - 1] || ctype_alnum($pattern[0])) {
                $conditions[$var] = '/' . $pattern . '/';
            }
        }

        $this->routes[$request_method][$uri_template] = compact('handler', 'conditions', 'source');

        // Return instance to allow chaining
        return $this;
    }

    /**
     * Registers the routes defined in a RouteMap instance using
     * docblock annotations (like @get) of its methods.
     *
     * \code
     * $router = \RESTAPI\Router::getInstance();
     *
     * $router->registerRoutes(new ExampleRouteMap());
     * \endcode
     *
     * @param RouteMap $map  the RouteMap instance to register
     *
     * @return Router returns itself to allow chaining
     */
    public function registerRoutes(RouteMap $map)
    {
        // Investigate object, define whether it's located in the core system
        // or a plugin, respect any defined class conditions and iterate
        // through it's methods to find any defined route
        $ref      = new \ReflectionClass($map);
        $filename = $ref->getFilename();
        $source   = strpos($filename, 'plugins_packages') !== false
                  ? 'plugin'
                  : 'core';

        $docblock = DocBlock::ofClass($map);
        $class_conditions = $this->extractConditions($docblock);

        foreach ($ref->getMethods() as $ref_method) {
            // Parse docblock
            $docblock = DocBlock::ofMethod($ref_method->class, $ref_method->name);

            // No docblock tags? Not an api route!
            if (!$docblock->tags) {
                continue;
            }

            // Any specific condition to consider?
            $conditions = $this->extractConditions($docblock, $class_conditions);

            // Iterate through all possible methods in order to identify
            // any according docblock tags
            foreach ($this->supported_methods as $http_method) {
                if (!isset($docblock->tags[$http_method])) {
                    continue;
                }

                // Route all defined method and uri template combinations to
                // the according methods of the object.
                foreach ($docblock->tags[$http_method] as $uri_template) {
                    $handler = array($map, $ref_method->name);

                    // Register (and describe) route
                    $this->register($http_method, $uri_template, $handler, $conditions, $source);
                    if ($docblock->desc) {
                        $this->describe($uri_template, $docblock->desc, $http_method);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Describe one or more routes.
     *
     * \code
     * $router = \RESTAPI\Router::getInstance();
     *
     * // describe a single route
     * $router->describe('/foo', 'returns everything about foo', 'get');
     *
     * // describe several routes that use the same path
     * $router->describe('/foo', array(
     *     'get'    => 'returns everything about foo',
     *     'put'    => 'updates all of foo',
     *     'delete' => 'empty up foo'
     * ));
     *
     * // describe several routes
     * $router->describe(array(
     *     '/foo' => array(
     *                   'get'    => 'returns everything about foo',
     *                   'put'    => 'updates all of foo',
     *                   'delete' => 'empty up foo'),
     *     '/bar' => array(...),
     * ));
     * \endcode
     *
     * @param String|Array $uri_template  URI template to describe or pass an
     *                                    array to describe multiple routes.
     * @param String|null  $description   description of the route
     * @param String       $method        method to describe.
     *
     * @return Router  returns instance of itself to allow chaining
     */
    public function describe($uri_template, $description = null, $method = 'get')
    {
        // describe multiple routes at once
        if (func_num_args() === 1 && is_array($uri_template)) {
            foreach ($uri_template as $template => $description) {
                $this->describe($template, $description);
            }
        }

        // describe routes that use the same URI template
        elseif (func_num_args() === 2 && is_array($description)) {
            foreach ($description as $method => $desc) {
                $this->describe($uri_template, $desc, $method);
            }
        }

        // describe a single route
        else {
            if (!isset($this->descriptions[$uri_template])) {
                $this->descriptions[$uri_template] = array();
            }
            if (isset($this->routes[$method][$uri_template])) {
                $this->descriptions[$uri_template][$method] = $description;
            } else {
                // Try to find route with different method
                foreach ($this->routes as $m => $templates) {
                    if (isset($templates[$uri_template])) {
                        $this->descriptions[$uri_template][$m] = $description;
                        break;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Get list of registered routes - optionally with their descriptions.
     *
     * @param bool $describe      (optional) include descriptions,
     *                              defaults to `false`
     * @param bool $check_access  (optional) only show methods this router's
     *                              consumer is authorized to,
     *                              defaults to `true`
     *
     * @return Array list of registered routes
     */
    public function getRoutes($describe = false, $check_access = true)
    {
        $result = array();
        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $uri => $route) {
                if ($check_access && !$this->permissions->check($uri, $method)) {
                    continue;
                }
                if (!isset($result[$uri])) {
                    $result[$uri] = array();
                }
                if ($describe) {
                    $result[$uri][$method] = array(
                        'description' => $this->descriptions[$uri][$method] ?: null,
                        'source'      => $route['source'] ?: 'unknown',
                    );
                } else {
                    $result[$uri][] = $method;
                }
            }
        }
        ksort($result);
        if ($describe) {
            $result = array_map(function ($item) {
                ksort($item);
                return $item;
            }, $result);
        }
        return $result;
    }

    /**
     * Dispatches an URI across the defined routes and produces a
     * Response object which may then be send back (using #output).
     *
     * @param mixed  $uri     URI to dispatch (defaults to `$_SERVER['PATH_INFO']`)
     * @param String $method  Request method (defaults to the method
     *                        of the actual HTTP request or "GET")
     *
     * @return Response  a Response object containing status, headers
     *                   and body
     * @throws RouterException  may throw such an exception if there
     *                          is no matching route (404) or if there
     *                          is one, but the consumer is not
     *                          authorized to it (403)
     */
    public function dispatch($uri = null, $method = null)
    {
        $uri = $this->normalizeDispatchURI($uri);
        $method = $this->normalizeRequestMethod($method);

        $content_renderer = $this->negotiateContent($uri);

        list($route, $parameters) = $this->matchRoute($uri, $method, $content_renderer);
        if (!$route) {
            throw new RouterException(404);
        }

        try {
            $response = $this->execute($route, $parameters);
        } catch(RouterHalt $halt) {
            $response = $halt->response;
        }

        $response->finish($content_renderer);

        return $response;
    }

    /**
     * Takes a route and the parameters out of the requested path and
     * executes the handler of the route.
     *
     * @param Array $route      the matched route out of
     *                          Router::matchRoute; an array with keys
     *                          'handler', 'conditions' and 'source'
     * @param Array $parameters the matched parameters out of
     *                          Router::matchRoute; something like:
     *                          `array('user_id' => '23a21d...e78f')`
     *
     * @return Response  the resulting Response object which is then
     *                   polished in Router::dispatch
     */
    protected function execute($route, $parameters)
    {
        $handler = $route['handler'];

        if (!is_object($handler[0])) {
            throw new RuntimeException("Handler is not a method.");
        }

        $handler[0]->init($this, $route);

        if (method_exists($handler[0], 'before')) {
            $handler[0]->before($this, $handler, $parameters);
        }

        $result = call_user_func_array($handler, $parameters);

        if (method_exists($result, 'toArray')) {
            $result = $result->toArray();
        }

        // $result is stronger than $response->body
        if (isset($result)) {
            $handler[0]->body($result);
        }

        if (method_exists($handler[0], 'after')) {
            $handler[0]->after($this, $parameters);
        }

        return $handler[0]->response;
    }

    /**
     * Registers a content renderer.
     *
     * @param ContentRenderer $renderer    instance of a content renderer
     * @param boolean         $is_default  (optional) set this
     *                                     renderer as default?;
     *                                     defaults to `false`
     *
     * @return Router returns itself to allow chaining
     */
    public function registerRenderer($renderer, $is_default = false)
    {
        $this->renderers[$renderer->extension()] = $renderer;
        if ($is_default) {
            $this->default_renderer = $renderer;
        }

        return $this;
    }

    private function normalizeDispatchURI($uri)
    {
        return $uri === null ? $_SERVER['PATH_INFO'] : $uri;
    }

    private function normalizeRequestMethod($method)
    {
        return strtolower($method ?: $_SERVER['REQUEST_METHOD'] ?: 'get');
    }

    /**
     * Negotiate content using the registered content renderers. The
     * first ContentRenderer that returns `true` when calling
     * ContentRenderer::shouldRespondTo gets the job.
     *
     * @param String $uri  the URI to which the content renderers may respond
     *
     * @return ContentRenderer  either a ContentRenderer that responds
     *                          to the URI or the default
     *                          ContentRenderer of this router.
     */
    protected function negotiateContent($uri)
    {
        $content_renderer = null;
        foreach ($this->renderers as $renderer) {
            if ($renderer->shouldRespondTo($uri)) {
                $content_renderer = $renderer;
                break;
            }
        }
        if (!$content_renderer) {
            $content_renderer = $this->default_renderer ?: reset($this->renderers);
        }
        return $content_renderer;
    }

    /**
     * Tries to match a route given a URI and a HTTP request method.
     *
     * @param String $uri     the URI to match
     * @param String $method  the HTTP request method to match
     * @param ContentRenderer $content_renderer the used
     *                                          ContentRenderer which
     *                                          is needed to remove
     *                                          a file extension
     *
     * @return Array  an array containing the matched route and the
     *                found parameters
     */
    protected function matchRoute($uri, $method, $content_renderer)
    {
        $matched    = null;
        $parameters = array();
        if (isset($this->routes[$method])) {
            if ($content_renderer->extension() && strpos($uri, $content_renderer->extension()) !== false) {
                $uri = substr($uri, 0, -strlen($content_renderer->extension()));
            }

            foreach ($this->routes[$method] as $uri_template => $route) {

                if (!isset($route['uri_template'])) {
                    $route['uri_template'] = new UriTemplate($uri_template, $route['conditions']);
                }

                if ($route['uri_template']->match($uri, $prmtrs)) {
                    if (!$this->permissions->check($uri_template, $method)) {
                        throw new RouterException(403, "Route not activated");
                    }
                    $matched = $route;
                    $parameters = $prmtrs;
                    break;
                }
            }
        }
        return array($matched, $parameters);
    }

    /**
     * Extracts defined conditions from a given docblock.
     *
     * @param DocBlock $docblock   DocBlock to examine
     * @param Array    $conditions Optional array of already defined
     *                             conditions to extend
     * @return Array of all extracted conditions with the variable name
     *         as key and pattern to match as value
     */
    protected function extractConditions($docblock, $conditions = array())
    {
        if (!empty($docblock->tags['condition'])) {
            foreach ($docblock->tags['condition'] as $condition) {
                list($var, $pattern) = explode(' ', $condition, 2);
                $conditions[$var] = $pattern;
            }
        }

        return $conditions;
    }
}
