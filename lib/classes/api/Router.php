<?php
namespace API;
use ReflectionClass, DocBlock, BadMethodCallException;

/**
 * Simple and flexible router. Needs PHP >= 5.3.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @see     Inspired by http://blog.sosedoff.com/2009/07/04/simpe-php-url-routing-controller/
 */
class Router
{
    /**
     * Returns (and if neccessary, initializes) a router object.
     *
     * @param mixed $consumer_id Id of the consumer (defaults to global)
     * @return Router Returns a router object
     */
    public static function getInstance($consumer_id = null)
    {
        $consumer_id = $consumer_id ?: 'global';

        static $cache = array();
        if (!isset($cache[$consumer_id])) {
            $cache[$consumer_id] = new self($consumer_id);
        }
        return $cache[$consumer_id];
    }

    // All supported method need to be defined here
    protected $supported_methods = array('get', 'post', 'put', 'delete');

    // Stores the registered routes by method and uri template
    protected $routes = array();

    // Stores the registered content renderers
    protected $renderers = array();

    // Stores the identified or forced content renderer
    protected $content_renderer = false;

    // Stores the default renderer
    protected $default_renderer = false;

    // Stores the registered conditions
    protected $conditions = array();

    // Stores the registered descriptions
    protected $descriptions = array();

    // Stores registered consumers
    protected $consumers = array();
    
    // Stores the associated permissions
    protected $permissions = false;

    /**
     * Constructs the router.
     */
    protected function __construct($consumer_id)
    {
        $this->permissions = ConsumerPermissions::get($consumer_id);
        $this->registerRenderer(new Renderer\DefaultRenderer);
    }

    /**
     * Sets global conditions (merges with current conditions).
     *
     * @param Array   $conditions   An associative array with parameter name
     *                              as key and regexp to match as value
     * @return Router Returns instance of itself to allow chaining
     */
    public function setConditions($conditions)
    {
        $this->conditions = array_merge($this->conditions, $conditions);
        $this->conditions = array_filter($this->conditions);
        return $this;
    }

    /**
     * Registers a handler for a specific combination of request method
     * and uri template.
     *
     * @param String  $method       Expected requested method
     * @param String  $uri_template Expected uri structure
     * @param Array   $handler      Request handler array:
     *                              array($object, "methodName")
     * @param Array   $conditions   An associative array with parameter name
     *                              as key and regexp to match as value
     *                              (optional)
     * @return Router Returns instance of itself to allow chaining
     * @throws Exception If passed request method is not supported
     */
    public function register($method, $uri_template, $handler, $conditions = array(), $source = 'unknown')
    {
        // Normalize method and test whether it's supported
        $method = strtolower($method);
        if (!in_array($method, $this->supported_methods)) {
            throw new Exception('Method "' . $method . '" is not supported.');
        }

        // Initialize routes storage for this method if neccessary
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = array();
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

        $this->routes[$method][$uri_template] = compact('handler', 'conditions', 'source');

        // Return instance to allow chaining
        return $this;
    }

    /**
     * Unknown method fallback that simplifies registration for supported
     * request methods. For example, see the following example:
     *
     * <code>
     *     // The first operation is a shortcut of the latter
     *     $router->get('/foo', function () { return 'foo'; });
     *     $router->register('get', '/foo', function () { return 'foo'; });
     * </code>
     *
     * @param String $method    Name of the called method
     * @param Array  $arguments Array of arguments passed to the method
     * @return Router Returns instance of itself to allow chaining
     * @throws BadMethodCallException If the method call is invalid in any way
     */
    public function __call($method, $arguments)
    {
        if (!in_array($method, $this->supported_methods)) {
            throw new BadMethodCallException('Call to undefined method "' . $method . '"');
        }
        if (count($arguments) < 2) {
            throw new BadMethodCallException('Method "' . $method . '" expects exactly two parameters.');
        }

        array_unshift($arguments, $method);

        return call_user_func_array(array($this, 'register'), $arguments);
    }

    /**
     * Registers a route map or a class consisting of different route handlers.
     *
     * @see RouteMap.php
     * @param RouteMap $map Instance of a RouteMap
     * @return Router Returns instance of itself to allow chaining
     */
    public function registerRoutes(RouteMap $map)
    {
        // Investigate object, define whether it's located in the core system
        // or a plugin, respect any defined class conditions and iterate
        // through it's methods to find any defined route
        $ref      = new ReflectionClass($map);
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
     * Describe a single route or multiple routes.
     *
     * @param String|Array $uri_template URI template to describe or pass an
     *                                   array to describe multiple routes.
     * @param String|null  $description  Description of the route
     * @param String       $method       Method to describe.
     * @return Router Returns instance of itself to allow chaining
     */
    public function describe($uri_template, $description = null, $method = 'get')
    {
        if (func_num_args() === 1 && is_array($uri_template)) {
            foreach ($uri_template as $template => $description) {
                $this->describe($template, $description);
            }
        } elseif (func_num_args() === 2 && is_array($description)) {
            foreach ($description as $method => $desc) {
                $this->describe($uri_template, $desc, $method);
            }
        } else {
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
     * @param bool $describe Include descriptions
     * @return Array List of routes
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
     * Dispatches an uri across the defined routes.
     *
     * @param mixed  $uri    Uri to dispatch (defaults to path info)
     * @param String $method Request method (defaults to actual method or GET)
     */
    public function dispatch($uri = null, $method = null)
    {
        $uri = $this->normalizeDispatchURI($uri);
        $method = $this->normalizeRequestMethod($method);

        $content_renderer = $this->negotiateContent();

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
     * @todo
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

        // TODO (mlunzena): result ist stärker als body, soll das so?
        if (isset($result)) {
            $handler[0]->response->body = $result;
        }

        if (method_exists($handler[0], 'after')) {
            $handler[0]->after($this, $parameters);
        }

        return $handler[0]->response;
    }

    /**
     * Registers a content renderer.
     *
     * @param ContentRenderer $renderer   Instance of a content renderer
     * @param boolean         $is_default Set this renderer as default?
     * @return Router Returns instance of itself to allow chaining
     */
    public function registerRenderer($renderer, $is_default = false)
    {
        $this->renderers[$renderer->extension()] = $renderer;
        if ($is_default) {
            $this->default_renderer = $renderer;
        }

        return $this;
    }

    protected function normalizeDispatchURI($uri)
    {
        return $uri === null ? $_SERVER['PATH_INFO'] : $uri;
    }

    protected function normalizeRequestMethod($method)
    {
        return strtolower($method ?: $_SERVER['REQUEST_METHOD'] ?: 'get');
    }

    protected function negotiateContent()
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
                    if (FALSE && !$this->permissions->check($uri_template, $method)) {
                        throw new RouterException(403);
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
