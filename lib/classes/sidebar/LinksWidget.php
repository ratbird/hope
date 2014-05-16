<?php
/**
 * A special sidebar widget for link list.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @since   3.1
 */
class LinksWidget extends ListWidget
{
    /**
     * Constructs the widget by defining a special template.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->addCSSClass('widget-links');
    }

    /**
     * Adds a link to the widget
     *
     * @param String $label  Label/content of the link
     * @param String $url    URL/Location of the link
     * @param String $icon   Icon for the link, defaults to blank.gif
     * @param bool   $active Pass true if the link is currently active,
     *                       defaults to false
     */
    public function &addLink($label, $url, $icon = null, $attributes = array())
    {
        $element = new LinkElement($label, $url, $icon, $attributes);
        $this->addElement($element, 'link-' . md5(uniqid('link', true)));
        return $element;
    }
}