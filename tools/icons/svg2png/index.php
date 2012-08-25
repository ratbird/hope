<?
// Uses jQuery miniColors colorpicker by Cory LaViska <https://github.com/claviska/jquery-miniColors/>
require './bootstrap.php';

$uri = sprintf('http%s://%s%s%s',
               @$_SERVER['HTTPS'] ? 's' : '',
               $_SERVER['SERVER_NAME'],
               $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '',
               $_SERVER['SCRIPT_NAME']);
$dispatcher = new Trails_Dispatcher('./app', $uri, 'svg2png');
$dispatcher->dispatch($_SERVER['PATH_INFO'] ?: '/');
