<?php
# Lifter007: TODO
# Lifter010: TODO
/*
 * StudIPPlugin.class.php - generic plugin base class
 *
 * Copyright (c) 2009 - Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

abstract class StudIPPlugin {

    /**
     * plugin meta data
     */
    protected $plugin_info;

    /**
     * plugin constructor
     * TODO bindtextdomain()
     */
    public function __construct() {
        $plugin_manager = PluginManager::getInstance();
        $this->plugin_info = $plugin_manager->getPluginInfo(get_class($this));
    }

    /**
     * Return the ID of this plugin.
     */
    public function getPluginId() {
        return $this->plugin_info['id'];
    }

    /**
     * Return the name of this plugin.
     */
    public function getPluginName() {
        return $this->plugin_info['name'];
    }

    /**
     * Return the filesystem path to this plugin.
     */
    public function getPluginPath() {
        return 'plugins_packages/' . $this->plugin_info['path'];
    }

    /**
     * Return the URL of this plugin. Can be used to refer to resources
     * (images, style sheets, etc.) inside the installed plugin package.
     */
    public function getPluginURL() {
        return $GLOBALS['ABSOLUTE_URI_STUDIP'] . $this->getPluginPath();
    }

    /**
     * Return metadata stored in the manifest of this plugin.
     */
    public function getMetadata() {
        $plugin_manager = PluginManager::getInstance();
        return $plugin_manager->getPluginManifest($this->getPluginPath());
    }

    /**
     * Checks if the plugin is a core-plugin. Returns true if this is the case.
     *
     * @return boolean
     */
    public function isCorePlugin()
    {
       return $this->plugin_info['core'];
    }

    /**
     * Get the activation status of this plugin in the given context.
     * This also checks the plugin default activations.
     *
     * @param $context   context range id (optional)
     * @param $type      type of activation (optional), can be set to 'user'
     *                   in order to point to a homepage plugin
     */
    public function isActivated($context = null, $type = 'sem') {
        global $user;

        $plugin_id = $this->getPluginId();
        $plugin_manager = PluginManager::getInstance();

        /*
         * Context can be a Seminar ID or the current user ID if not set.
         * Identification is done via the "username" parameter.
         */
        if (!isset($context)) {
            if ($type == 'user') {
                $context = get_userid(Request::username('username', $user->username));
            } else {
                $context = $_SESSION['SessionSeminar'];
            }
        }

        if ($type == 'user') {
            $activated = $plugin_manager->isPluginActivatedForUser($plugin_id, $context);
        } else {
            $activated = $plugin_manager->isPluginActivated($plugin_id, $context);
        }

        return $activated;
    }

    /**
     * Return a warning message to be printed before deactivation of
     * this plugin in the given context.
     *
     * @param $context   context range id
     */
    public function deactivationWarning($context) {
        return NULL;
    }

    /**
     * This method dispatches all actions.
     *
     * @param string   part of the dispatch path that was not consumed
     *
     * @return void
     */
    public function perform($unconsumed_path) {
        $args = explode('/', $unconsumed_path);
        $action = $args[0] !== '' ? array_shift($args).'_action' : 'show_action';

        if (!method_exists($this, $action)) {
            $trails_root = $this->getPluginPath();
            $dispatcher = new Trails_Dispatcher($trails_root, "plugins.php", 'index');
            $dispatcher->current_plugin = $this;
            try {
                $dispatcher->dispatch($unconsumed_path);
            } catch (Trails_UnknownAction $exception) {
                if (count($args) > 0) {
                    throw $exception;
                } else {
                    throw new Exception(_('unbekannte Plugin-Aktion: ') . $unconsumed_path);
                }
            }
        } else {
            call_user_func_array(array($this, $action), $args);
        }
    }

    /**
     * Callback function called after enabling a plugin.
     * The plugin's ID is transmitted for convenience.
     *
     * @param $pluginId string The ID of the plugin just enabled.
     */
    public static function onEnable($pluginId)
    {
        # implement this in your own plugin
    }

    /**
     * Callback function called after disabling a plugin.
     * The plugin's ID is transmitted for convenience.
     *
     * @param $pluginId string The ID of the plugin just disabled.
     */
    public static function onDisable($pluginId)
    {
        # implement this in your own plugin
    }

    /**
     * Includes given stylesheet in page, compiles less if neccessary
     *
     * @param String $filename Name of the stylesheet (css or less) to include
     *                         (relative to plugin directory)
     */
    protected function addStylesheet($filename)
    {
        if (substr($filename, -5) === '.less') {
            $less_file = $GLOBALS['ABSOLUTE_PATH_STUDIP']
                       . $this->getPluginPath() . '/'
                       . $filename;
            $css_file  = $GLOBALS['ABSOLUTE_PATH_STUDIP']
                       . $this->getPluginPath() . '/'
                       . substr($filename, 0, -5) . '.css';

            if (!file_exists($css_file) || (filemtime($css_file) < filemtime($less_file))) {
                $less  = '';
                foreach (file($GLOBALS['ABSOLUTE_PATH_STUDIP'] . 'assets/stylesheets/mixins.less') as $mixin) {
                    if (!preg_match('/@import "(.*)";/', $mixin, $match)) {
                        continue;
                    }
                    $less .= file_get_contents($GLOBALS['ABSOLUTE_PATH_STUDIP'] . 'assets/stylesheets/' . $match[1]) . "\n";
                }
                $less .= sprintf('@image-path: "%s";', Assets::url('images')) . "\n";
                $less .= '@icon-path: "@{image-path}/icons/16";' . "\n";
                $less .= file_get_contents($less_file);

                require_once 'vendor/mishal-iless/lib/ILess/Autoloader.php';
                ILess_Autoloader::register();
                $parser = new ILess_Parser();
                $parser->setVariables(array(
                    'image-path' => '"' . substr(Assets::image_path('placeholder.png'), 0, -15) . '"',
                ));
                $parser->parseString($less);
                $css = $parser->getCSS();
                file_put_contents($css_file, $css);
            }
            $filename  = substr($filename, 0, -5) . '.css';
        }
        PageLayout::addStylesheet($this->getPluginURL() . '/' . $filename);
    }
}
