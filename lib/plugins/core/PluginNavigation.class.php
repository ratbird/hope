<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/**
 *
 *  @author Dennis Reil, <dennis.reil@offis.de>
 *  @package pluginengine
 *
 */

class PluginNavigation extends StudipPluginNavigation {

    /**
     * The cmd of this Navigation object.
     *
     * @var string
     */
    protected $cmd = 'show';

    /**
     * The plugin of this Navigation object.
     *
     * @var object
     */
    protected $plugin;

    /**
     * Returns the cmd of this Navigation object.
     *
     * @deprecated
     *
     * @return string  the cmd
     */
    function getCommand() {
        return $this->cmd;
    }

    /**
     * Sets the cmd of this Navigation's object.
     *
     * @deprecated
     *
     * @param  string  the cmd
     */
    function setCommand($cmd) {
        $this->cmd = $cmd;
    }

    /**
     * Returns the plugin of this Navigation's object.
     *
     * @deprecated
     *
     * @return string  the plugin
     */
    function getPlugin() {
        return $this->plugin;
    }

    /**
     * Sets the plugin of this Navigation's object.
     *
     * @deprecated
     *
     * @param  string  the plugin
     */
    function setPlugin(StudIPPlugin $plugin) {
        $this->plugin = $plugin;

        foreach ($this->getSubNavigation() as $nav) {
            $nav->setPlugin($plugin);
        }
    }

    /**
     * Add the given item to the subnavigation of this object.
     *
     * @deprecated
     */
    function addSubNavigation($name, PluginNavigation $navigation)
    {
        parent::addSubNavigation($name, $navigation);

        if (isset($this->plugin)) {
            $navigation->setPlugin($this->plugin);
        }
    }

    /**
     * Return the current URL associated with this navigation item.
     *
     * @deprecated
     */
    function getURL() {
        return PluginEngine::getURL($this->plugin, $this->params, $this->cmd);
    }

    /**
     * Add a new parameter for the link generation. If the key is already
     * in use, its value is replaced with the new one.
     *
     * @deprecated
     */
    function addLinkParam($key, $value) {
        $this->params[$key] = $value;
    }

    /**
     * @deprecated
     */
    function setLinkParam($link) {
        $this->addLinkParam('plugin_subnavi_params', $link);
    }

    /**
     * @deprecated
     */
    function setActive($value = NULL) {
        if (isset($value)) {
            parent::setActive($value);
        }
    }
}
?>
