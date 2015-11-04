<?php
/**
 * Output plugin assets
 *
 * This will load and output plugin assets. For now, this will be the
 * compiled LESS files of plugins.
 * All served assets will set the appropriate headers so that the browser
 * will cache the assets for a certain amount of time.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 * @since   Stud.IP 3.4
 */

// Set base path
$STUDIP_BASE_PATH = realpath(dirname(__FILE__) . '/..');

// Set include paths
set_include_path(
    $STUDIP_BASE_PATH
    . PATH_SEPARATOR . $STUDIP_BASE_PATH . DIRECTORY_SEPARATOR . 'config'
    . PATH_SEPARATOR . get_include_path()
);

// Setup autoloading
require 'lib/classes/StudipAutoloader.php';
StudipAutoloader::register();
StudipAutoloader::addAutoloadPath($GLOBALS['STUDIP_BASE_PATH'] . '/lib/classes');
StudipAutoloader::addAutoloadPath($GLOBALS['STUDIP_BASE_PATH'] . '/lib/files');
StudipAutoloader::addAutoloadPath($GLOBALS['STUDIP_BASE_PATH'] . '/lib/less', 'LESS');
StudipAutoloader::addAutoloadPath($GLOBALS['STUDIP_BASE_PATH'] . '/lib/models');

StudipFileloader::load('config_local.inc.php', $GLOBALS, compact('STUDIP_BASE_PATH'));
require_once 'lib/functions.php';

// set default pdo connection
DBManager::getInstance()
    ->setConnection('studip',
        'mysql:host=' . $GLOBALS['DB_STUDIP_HOST'] .
        ';dbname=' . $GLOBALS['DB_STUDIP_DATABASE'],
        $GLOBALS['DB_STUDIP_USER'],
        $GLOBALS['DB_STUDIP_PASSWORD']);

// Obtain request information
$uri = ltrim($_SERVER['PATH_INFO'], '/');
list($type, $id) = explode('/', $uri);

// Setup response
$response = new RESTAPI\Response();

// Create response
if (!$type || !$id) {
    // Invalid call
    $response->status = 40;
} elseif ($type !== 'css') {
    // Invalid type
    $response->status = 501;
} elseif (!PluginAsset::exists($id)) {
    // Asset does not exist
    $response->status = 404;
} else {
    // Load asset
    $model = PluginAsset::find($id);
    if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] && $model->chdate <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        // Cached and still valid
        $response->status = 304;
    } else {
        // Output asset
        $asset = new Assets\PluginAsset($model);
        $response->body = $asset->getContent();
        if ($response->body === false) {
            // Could not obtain asset contents
            $response->status = 500;
        } else {
            // Set appropriate header
            $response['Content-Type']        = 'text/css';
            $response['Content-Length']      = $model->size;
            $response['Content-Disposition'] = 'inline;filename="' . $model->filename . '"';

            // Store cache information
            $response['Last-Modified'] = gmdate('D, d M Y H:i:s', $model->chdate) . ' GMT';
            $response['Expires']       = gmdate('D, d M Y H:i:s', $model->chdate + 
PluginAsset::CACHE_DURATION) . ' GMT';
        }
    }
}
$response->output();

