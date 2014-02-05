<?php
namespace {
    require_once 'vendor/docblock-parser/docblock-parser.php';

    StudipAutoloader::addAutoloadPath($GLOBALS['STUDIP_BASE_PATH'] . DIRECTORY_SEPARATOR . 'vendor/oauth-php/library');

    // Set base url for URLHelper class
    URLHelper::setBaseUrl($GLOBALS['ABSOLUTE_URI_STUDIP']);
}

namespace RESTAPI {
    use Studip, OAuthStore;

    // Define api version
    const VERSION = '2';

    $router = Router::getInstance();

    // Register JSON content renderer
    $router->registerRenderer(new Renderer\JSONRenderer, true);

    // If in development mode, register debug content renderer
    if (defined('Studip\\ENV') && Studip\ENV === 'development') {
        $router->registerRenderer(new Renderer\DebugRenderer);
    }

    OAuthStore::instance('PDO', array(
        'dsn' => 'mysql:host=' . $GLOBALS['DB_STUDIP_HOST']
                   . ';dbname=' . $GLOBALS['DB_STUDIP_DATABASE'],
        'username' => $GLOBALS['DB_STUDIP_USER'],
        'password' => $GLOBALS['DB_STUDIP_PASSWORD']
    ));

    // Register default consumers
    Consumer\Base::addType('http', 'RESTAPI\\Consumer\\HTTP');
    Consumer\Base::addType('studip', 'RESTAPI\\Consumer\\Studip');
    Consumer\Base::addType('oauth', 'RESTAPI\\Consumer\\OAuth');

    // $router->registerConsumer('oauth', new Consumer\OAuth);
    // $router->registerConsumer('basic', new Consumer\HTTP);
    // $router->registerConsumer('studip', new Consumer\Studip);

    // Register default routes
    $routes = words('Contacts Course Discovery Events Files Forum Messages News Schedule Semester Studip User Wiki');

    foreach ($routes as $route) {
        require_once "app/routes/$route.php";
        $class = "\\RESTAPI\\Routes\\$route";
        $router->registerRoutes(new $class);
    }

    // Register plugin routes
    array_walk(
        array_flatten(\PluginEngine::sendMessage('RESTAPIPlugin', 'getRouteMaps')),
        function ($route) use ($router) {
            $router->registerRoutes($route);
        }
    );
}
