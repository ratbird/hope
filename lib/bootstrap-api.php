<?php
namespace {
    require_once 'vendor/docblock-parser/docblock-parser.php';

    // Add /lib to autoloader, a bit overkill but this way, we 
    StudipAutoloader::addAutoloadPath($GLOBALS['STUDIP_BASE_PATH'] . DIRECTORY_SEPARATOR . 'app/routes');
    StudipAutoloader::addAutoloadPath($GLOBALS['STUDIP_BASE_PATH'] . DIRECTORY_SEPARATOR . 'vendor/oauth-php/library');

    // Set base url for URLHelper class
    URLHelper::setBaseUrl($GLOBALS['ABSOLUTE_URI_STUDIP']);
}

namespace API {
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
    Consumer\Base::addType('http', 'API\\Consumer\\HTTP');
    Consumer\Base::addType('studip', 'API\\Consumer\\Studip');
    Consumer\Base::addType('oauth', 'API\\Consumer\\OAuth');

#    $router->registerConsumer('oauth', new Consumer\OAuth);
#    $router->registerConsumer('basic', new Consumer\HTTP);
#    $router->registerConsumer('studip', new Consumer\Studip);

    // Register default routes
    $router->registerRoutes(new ContactsRoute);
    $router->registerRoutes(new CourseRoute);
    $router->registerRoutes(new DiscoveryRoute);
    $router->registerRoutes(new EventsRoute);
    $router->registerRoutes(new FilesRoute);
    $router->registerRoutes(new ForumRoute);
    $router->registerRoutes(new MessagesRoute);
    $router->registerRoutes(new NewsRoute);
    $router->registerRoutes(new ScheduleRoute);
    $router->registerRoutes(new SemesterRoute);
    $router->registerRoutes(new StudipRoute);
    $router->registerRoutes(new UserRoute);
    $router->registerRoutes(new WikiRoute);
}
