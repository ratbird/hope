<?
set_include_path(get_include_path() . PATH_SEPARATOR . realpath('../../../'));

require 'vendor/trails/trails.php';
require 'vendor/flexi/lib/flexi.php';
require 'lib/classes/Request.class.php';
require 'lib/classes/Assets.class.php';
require 'lib/classes/Button.class.php';
require 'lib/classes/URLHelper.php';
require 'lib/visual.inc.php';

spl_autoload_register(function ($class) {
    require './app/models/' . $class . '.php';
});

$uri = sprintf('http%s://%s%s%s/',
               @$_SERVER['HTTPS'] ? 's' : '',
               $_SERVER['SERVER_NAME'],
               $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '',
               dirname($_SERVER['SCRIPT_NAME']));

URLHelper::setBaseURL($uri);
Assets::set_assets_url($uri . '../../../public/assets/');
SVG_Converter::setOutputDirectory(dirname(__FILE__));