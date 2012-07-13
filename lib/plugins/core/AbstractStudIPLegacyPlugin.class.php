<?php
# Lifter007: TODO
# Lifter010: TODO

/*
 * AbstractStudIPLegacyPlugin.class.php - bridge for legacy plugins
 *
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * Abstract class for old style plugins in Stud.IP. It implements
 * method #perform() using the template method design pattern. Just implement
 * the #route or #display method in your actual old style plugin to change
 * the plugin's behaviour.
 *
 * @author      Marcus Lunzenauer <mlunzena@uos.de>
 * @package     pluginengine
 * @subpackage  core
 */

abstract class AbstractStudIPLegacyPlugin extends StudIPPlugin {


    /**
     * deprecated plugin fields, do not use
     *
     * @deprecated
     */
    public $pluginid;
    public $pluginname;
    public $pluginpath;
    public $basepluginpath;
    public $navposition;
    public $dependentonplugin;
    public $navigation;
    public $pluginiconname;
    public $user;


    /**
     * constructor
     */
    function __construct() {
        parent::__construct();

        $this->pluginid          = parent::getPluginId();
        $this->pluginname        = parent::getPluginName();
        $this->pluginpath        = parent::getPluginPath();
        $this->basepluginpath    = $this->getBasepluginpath();
        $this->navposition       = $this->getNavigationPosition();
        $this->dependentonplugin = $this->isDependentOnOtherPlugin();
        $this->user              = new StudIPUser();

        $this->setPluginId($this->pluginid);
        $this->setPluginName($this->pluginname);
        $this->setPluginPath($this->pluginpath);
        $this->setBasepluginpath($this->basepluginpath);
        $this->setNavigationPosition($this->navposition);
        $this->setDependentOnOtherPlugin($this->dependentonplugin);
    }

    /**
     * Aktiviert das Plugin - deprecated, do not use.
     *
     * @deprecated
     */
    function activatePlugin() {
        $this->setActivated(true);
    }

    /**
     * Deaktiviert das Plugin - deprecated, do not use.
     *
     * @deprecated
     */
    function deactivatePlugin() {
        $this->setActivated(false);
    }

    /**
     * Returns the URI to the administration page of this plugin. Override this
     * method, if you want another URI, or return NULL to signal, that there is
     * no such page.
     *
     * @deprecated
     *
     * @return string if this plugin has an administration page return its URI,
     *                return NULL otherwise
     */
    function getAdminLink() {
        return PluginEngine::getURL($this, array(), 'showAdministrationPage');
    }

    /**
     * Returns the plugin's relative path - deprecated, do not use.
     *
     * @deprecated
     */
    function getBasepluginpath() {
        return $this->plugin_info['path'];
    }

    /**
     * Which text should be shown in certain titles?
     *
     * @deprecated
     *
     * @return string title
     */
    function getDisplaytitle() {
        return $this->hasNavigation() ?  $this->navigation->getTitle() : $this->getPluginName();
    }

    /**
     * Returns this plugins's navigation.
     *
     * @deprecated
     */
    function getNavigation() {
        return $this->navigation;
    }

    /**
     * Returns the plugin's navigation position - deprecated, do not use.
     *
     * @deprecated
     */
    function getNavigationPosition() {
        return $this->plugin_info['position'];
    }

    /**
     * Returns the class name of this plugin (in lower case).
     *
     * @deprecated
     */
    function getPluginclassname() {
        return strtolower($this->plugin_info['class']);
    }

    /**
     * Liefert den Pfad zum Icon dieses Plugins zurück.
     *
     * @deprecated
     *
     * @return den Pfad zum Icon
     */
    function getPluginiconname() {
        if ($this->hasNavigation() && $this->navigation->hasIcon()) {
            return $this->getPluginURL().'/'.$this->navigation->getIcon();
        } else if (isset($this->pluginiconname)) {
            return $this->getPluginURL().'/'.$this->pluginiconname;
        } else {
            return Assets::image_path('icons/16/grey/plugin.png');
        }
    }

    /**
     * Return the name of this plugin.
     */
    public function getPluginName() {
        return $this->pluginname;
    }

    /**
     * Returns the current user.
     *
     * @return StudIPUser
     */
    function getUser() {
        return $this->user;
    }

    /**
     * Check whether this plugin has a navigation.
     *
     * @deprecated
     */
    function hasNavigation() {
        return $this->navigation != NULL;
    }

    /**
     * Check if this plugin depends on another plugin - deprecated, do not use.
     *
     * @deprecated
     */
    function isDependentOnOtherPlugin() {
        return $this->plugin_info['depends'] != NULL;
    }

    /**
     * Does nothing - deprecated, do not use.
     *
     * @deprecated
     */
    function setActivated($value) {
    }

    /**
     * Does nothing - deprecated, do not use.
     *
     * @deprecated
     */
    function setBasepluginpath($path) {
    }

    /**
     * Does nothing - deprecated, do not use.
     *
     * @deprecated
     */
    function setDependentOnOtherPlugin($dependentplugin = true) {
    }

    /**
     * Sets the navigation of this plugin.
     *
     * @deprecated
     */
    function setNavigation(StudipPluginNavigation $navigation) {
        $this->navigation = $navigation;

        if ($navigation instanceof PluginNavigation) {
            $navigation->setPlugin($this);
        }

        $active_plugin = PluginEngine::getCurrentPluginId();

        // make sure navigation for current plugin is active
        if (isset($active_plugin) && $active_plugin == $this->getPluginId()) {
            if ($navigation->activeSubNavigation() == NULL) {
                $navigation->setActive(true);
            }
        }
    }

    /**
     * Does nothing - deprecated, do not use.
     *
     * @deprecated
     */
    function setNavigationPosition($pos) {
    }

    /**
     * Setzt den Pfad zum Icon dieses Plugins.
     *
     * @deprecated
     */
    function setPluginiconname($icon) {
        $this->pluginiconname = $icon;
    }

    /**
     * Does nothing - deprecated, do not use.
     *
     * @deprecated
     */
    function setPluginId($id) {
    }

    /**
     * Does nothing - deprecated, do not use.
     *
     * @deprecated
     */
    function setPluginName($name) {
        $this->pluginname = $name;
    }

    /**
     * Does nothing - deprecated, do not use.
     *
     * @deprecated
     */
    function setPluginPath($path) {
    }

    /**
     * Does nothing - deprecated, do not use.
     *
     * @deprecated
     */
    function setUser(StudIPUser $user) {
    }


  /**
   * This is the standard action of this plugin.
   */
  function actionShow($param = NULL) {
    return $this->show($param);
  }


  /**
   * Does nothing - deprecated, do not use.
   *
   * @param $param - set if a subnavigation item was clicked.
   *                 The value is plugin dependent and specified
   *                 by the plugins subnavigation link params.
   *
   * @deprecated
   */
  function show($param = NULL) {
  }


  /**
   * This method dispatches and displays all actions. It uses the template
   * method design pattern, so you may want to implement the methods #route
   * and/or #display to adapt to your needs.
   *
   * @param  string  the part of the dispatch path, that were not consumed yet
   *
   * @return void
   */
  function perform($unconsumed_path) {

    # get cmd
    list($cmd, $this->unconsumed_path) = $this->route($unconsumed_path);

    # it's action time
    try {

      ob_start();

      $this->display_action($cmd);
      ob_end_flush();

    } catch (Exception $e) {

      # disable output buffering
      while (ob_get_level()) {
        ob_end_clean();
      }

      # defer exception handling
      throw $e;
    }
  }


  /**
   * Called by #perform to detect the action to be accomplished.
   *
   * @param  string  the part of the dispatch path, that were not consumed yet
   *
   * @return string  the name of the instance method to be called
   */
  function route($unconsumed_path) {

    $tokens = preg_split('@/@', $unconsumed_path, -1, PREG_SPLIT_NO_EMPTY);
    $action = sizeof($tokens) ? 'action' . array_shift($tokens) : 'actionShow';

    $class_methods = array_map('strtolower', get_class_methods($this));
    if (!in_array(strtolower($action), $class_methods)) {
      throw new Exception(_("Das Plugin verfügt nicht über die gewünschte Operation"));
    }

    return array($action, join('/', $tokens));
  }


  /**
   * This method sets everything up to perform the given action and
   * displays the results or anything you want to.
   *
   * @param  string  the name of the action to accomplish
   *
   * @return void
   */
  function display_action($action) {
    if (!Request::get('CURRENT_PAGE')) {
      Request::set('CURRENT_PAGE',$this->getDisplayTitle());
    }

    include 'lib/include/html_head.inc.php';
    include 'lib/include/header.php';

    $pluginparams = Requeat::quoted('plugin_subnavi_params');

    StudIPTemplateEngine::startContentTable();
    $this->$action($pluginparams);
    StudIPTemplateEngine::endContentTable();

    include 'lib/include/html_end.inc.php';
  }
}
