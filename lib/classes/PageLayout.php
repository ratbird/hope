<?php
# Lifter010: TODO
/**
 * PageLayout.php - configure the global page layout of Stud.IP
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'lib/classes/squeeze/squeeze.php';

use \Studip\Squeeze\Configuration;
use \Studip\Squeeze\Packager;

/**
 * The PageLayout class provides utility functions to control the
 * global page layout of Stud.IP. This includes the page title, the
 * included CSS style sheets and JavaScript files. It replaces the
 * "traditional" way of manipulating the page header via special
 * global variables (like $CURRENT_PAGE and $_include_stylesheet).
 *
 * Each Stud.IP page should at least set the page title and help
 * keyword (if a help page exists).
 */
class PageLayout
{
    /**
     * current page title (defaults to $UNI_NAME_CLEAN)
     */
    private static $title;

    /**
     * current help keyword (defaults to 'Basis.Allgemeines')
     */
    private static $help_keyword;

    /**
     * base item path for tab view (defaults to active item in top nav)
     */
    private static $tab_navigation_path = false;

    /**
     * base item for tab view (defaults to active item in top nav)
     */
    private static $tab_navigation = false;

    /**
     * array of HTML HEAD elements (initialized with default set)
     */
    private static $head_elements = array();

    /**
     * extra HTML text included in the BODY element (initially empty)
     */
    private static $body_elements = '';

    /**
     * id of the body tag
     */
    private static $body_element_id = false;

    /**
     * determines whether the navigation header is displayed or not
     */
    private static $display_header = true;

    /**
     * names of the squeeze packages to include
     */
    private static $squeeze_packages = array();

    /**
     * Initialize default page layout. This should only be called once
     * from phplib_local.inc.php. Don't use this otherwise.
     */
    public static function initialize()
    {
        // set favicon
        self::addHeadElement('link', array('rel' => 'apple-touch-icon', 'href' => Assets::image_path('touch-icon-ipad3.png'), 'size' => '144x144'));
        self::addHeadElement('link', array('rel' => 'apple-touch-icon', 'href' => Assets::image_path('touch-icon-iphone4.png'), 'size' => '114x114'));
        self::addHeadElement('link', array('rel' => 'apple-touch-icon', 'href' => Assets::image_path('touch-icon-ipad.png'), 'size' => '72x72'));
        self::addHeadElement('link', array('rel' => 'apple-touch-icon', 'href' => Assets::image_path('touch-icon-iphone.png')));
        self::addHeadElement('link', array('rel' => 'shortcut icon', 'href' => Assets::image_path('favicon.png')));

        // set initial width for mobile devices
        self::addHeadElement('meta', array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1.0'));

        // include ie-specific CSS
        self::addComment('[if IE]>' . Assets::stylesheet('ie.css', array('media' => 'screen,print')) . '<![endif]');

        self::addHeadElement('link', array(
            'rel'   => 'help',
            'href'  => format_help_url('Basis.VerschiedenesFormat'),
            'class' => 'text-format',
            'title' => _('Hilfe zur Textformatierung')
        ));

        self::setSqueezePackages("base");
        if (Config::get()->WYSIWYG) {
            self::addSqueezePackage("wysiwyg");
        }
        self::addScript("mathjax/MathJax.js?config=TeX-AMS_HTML,default");
    }

    /**
     * Set the page title to the given text.
     * @param string $title Page title
     */
    public static function setTitle($title)
    {
        self::$title = $title;
    }

    /**
     * Returns whether a title has been set
     * @return bool
     */
    public static function hasTitle()
    {
        return isset(self::$title);
    }

    /**
     * Get the current page title (defaults to $UNI_NAME_CLEAN).
     * @return string
     */
    public static function getTitle()
    {
        return isset(self::$title) ? self::$title :
                (isset($GLOBALS['_html_head_title']) ? $GLOBALS['_html_head_title'] :
                    (isset($GLOBALS['CURRENT_PAGE']) ? $GLOBALS['CURRENT_PAGE'] :
                        $GLOBALS['UNI_NAME_CLEAN']));
    }

    /**
     * Set the help keyword to the given string.
     */
    public static function setHelpKeyword($help_keyword)
    {
        self::$help_keyword = $help_keyword;
    }

    /**
     * Get the current help keyword (defaults to 'Basis.Allgemeines').
     */
    public static function getHelpKeyword()
    {
        return isset(self::$help_keyword) ? self::$help_keyword : 'Basis.Allgemeines';
    }

    /**
     * Select which tabs (if any) should be displayed on the page. The
     * argument specifies a navigation item in the tree whose children
     * will form the first level of tabs. If $path is NULL, no tabs are
     * displayed. The default setting is to use the active element in
     * the top navigation.
     *
     * @param string $path       path of navigation item for tabs or NULL
     */
    public static function setTabNavigation($path)
    {
        self::$tab_navigation_path = $path;
        self::$tab_navigation      = isset($path) ? Navigation::getItem($path) : NULL;
    }

    /**
     * Returns the base navigation object (not its path) for the tabs.
     * May return NULL if tab display is disabled.
     */
    public static function getTabNavigation()
    {
        if (self::$tab_navigation === false) {
            self::$tab_navigation = Navigation::getItem('/')->activeSubNavigation();
        }

        return self::$tab_navigation;
    }

    /**
     * Returns the base navigation path for the tabs.
     * May return NULL if tab display is disabled.
     */
    public static function getTabNavigationPath()
    {
        if (self::$tab_navigation_path === false) {
            foreach (Navigation::getItem('/')->getSubNavigation() as $subpath => $navigation) {
                if ($navigation->isActive()) {
                    self::$tab_navigation_path = $subpath;
                }
            }
        }
        return self::$tab_navigation_path;
    }

    /**
     * Add a STYLE element to the HTML HEAD section.
     *
     * @param string $content   element contents
     * @param string $media     media types
     */
    public static function addStyle($content, $media = '')
    {
        $attr = array();
        if($media) {
            $attr = array('media' => $media);
        }
        self::addHeadElement('style', $attr, $content);
    }

    /**
     * Add a style sheet LINK element to the HTML HEAD section.
     *
     * @param string $source     style sheet URL or file in assets folder
     * @param array  $attributes additional attributes for LINK element
     */
    public static function addStylesheet($source, $attributes = array())
    {
        $attributes['rel']  = 'stylesheet';
        $attributes['href'] = Assets::stylesheet_path($source);

        self::addHeadElement('link', $attributes);
    }

    /**
     * Remove a style sheet LINK element from the HTML HEAD section.
     *
     * @param string $source     style sheet URL or file in assets folder
     * @param array  $attributes additional attributes for LINK element
     */
    public static function removeStylesheet($source, $attributes = array())
    {
        $attributes['rel']  = 'stylesheet';
        $attributes['href'] = Assets::stylesheet_path($source);

        self::removeHeadElement('link', $attributes);
    }

    /**
     * Add a JavaScript SCRIPT element to the HTML HEAD section.
     *
     * @param string $source     URL of JS file or file in assets folder
     * @param array $attributes  Additional parameters for the script tag
     */
    public static function addScript($source, $attributes = array())
    {
        $attributes['src'] = Assets::javascript_path($source);

        self::addHeadElement('script', $attributes, '');
    }

    /**
     * Remove a JavaScript SCRIPT element from the HTML HEAD section.
     *
     * @param string $source     URL of JS file or file in assets folder
     * @param array $attributes  Additional parameters for the script tag
     */
    public static function removeScript($source, $attributes = array())
    {
        $attributes['src'] = Assets::javascript_path($source);

        self::removeHeadElement('script', $attributes);
    }

    /**
     * Add an extra HTML element to the HTML HEAD section. This can be
     * used to include RSS/ATOM feed links, META tags or other stuff.
     * If $content is NULL, no closing tag is generated. If the element
     * needs a closing tag (like SCRIPT) but should not have contents,
     * pass the empty string as the third parameter.
     *
     * @param string $name       element name (e.g. 'meta')
     * @param array  $attributes additional attributes for the element
     * @param string $content    element contents, if any
     */
    public static function addHeadElement($name, $attributes = array(), $content = NULL)
    {
        self::$head_elements[] = compact('name', 'attributes', 'content');
    }

    /**
     * Remove HTML elements from the HTML HEAD section. This method will
     * remove all elements matching the given name and all the attributes.
     *
     * For example, to remove all META elements:
     * PageLayout::removeHeadElement('meta');
     *
     * Remove all style sheet LINK elements:
     * PageLayout::removeHeadElement('link', array('rel' => 'stylesheet'));
     *
     * Remove a particular style sheet LINK by href:
     * PageLayout::removeHeadElement('link', array('href' => '...'));
     */
    public static function removeHeadElement($name, $attributes = array())
    {
        $result = array();

        foreach (self::$head_elements as $element) {
            $remove = false;

            // match element name
            if ($name === $element['name']) {
                $remove = true;

                // match element attributes
                foreach ($attributes as $key => $value) {
                    if (!isset($element['attributes'][$key]) ||
                        $element['attributes'][$key] !== $value) {
                        $remove = false;
                        break;
                    }
                }
            }

            if (!$remove) {
                $result[] = $element;
            }
        }

        self::$head_elements = $result;
    }

    /**
     * Insert a (conditional) comment in the header. To preserve execution
     * order, this method utilizes addHeadElement() in a more or less hackish
     * way.
     *
     * @param string $content    comment content
     */
    public static function addComment($content)
    {
        self::addHeadElement(sprintf('!--%s--', $content));
    }

    /**
     * Remove a (conditional) comment from the header.
     *
     * @param string $content    comment content
     */
    public static function removeComment($content)
    {
        self::removeHeadElement(sprintf('!--%s--', $content));
    }

    /**
     * Return all HTML HEAD elements as a string.
     *
     * @return string   HTML fragment
     */
    public static function getHeadElements()
    {
        $result = '';

        $package_elements = self::includeSqueezePackages();

        if (isset($GLOBALS['_include_stylesheet'])) {
            unset($package_elements['base-style.css']);
            self::addStylesheet($GLOBALS['_include_stylesheet'], array('media' => 'screen, print'));
        }

        $head_elements = array_merge($package_elements, self::$head_elements);

        foreach ($head_elements as $element) {
            $result .= '<' . $element['name'];

            foreach ($element['attributes'] as $key => $value) {
                $result .= sprintf(' %s="%s"', $key, htmlReady($value));
            }

            $result .= ">\n";

            if (isset($element['content'])) {
                $result .= $element['content'];
                $result .= '</' . $element['name'] . ">\n";
            }
        }

        if (isset($GLOBALS['_include_extra_stylesheet'])) {
            $result .= Assets::stylesheet($GLOBALS['_include_extra_stylesheet']);
        }

        if (isset($GLOBALS['_include_additional_header'])) {
            $result .= $GLOBALS['_include_additional_header'];
        }

        return $result;
    }

    /**
     * Add an extra HTML fragment at the start of the HTML BODY section.
     *
     * @param string $html  HTML fragment to include in BODY
     */
    public static function addBodyElements($html)
    {
        self::$body_elements .= $html;
    }

    /**
     * Return all HTML BODY fragments as a string.
     *
     * @return string   HTML fragment
     */
    public static function getBodyElements()
    {
        $result = self::$body_elements;

        if (isset($GLOBALS['_include_additional_html'])) {
            $result .= $GLOBALS['_include_additional_html'];
        }

        return $result;
    }

    /**
     * Disable output of the navigation header for this page.
     */
    public static function disableHeader()
    {
        self::$display_header = false;
    }

    /**
     * Return whether output of the navigation header is enabled.
     */
    public static function isHeaderEnabled()
    {
        return self::$display_header && !$GLOBALS['_NOHEADER'];
    }

    /**
     * Sets the id of the html body element.
     * The given id is stripped of all non alpha-numeric characters
     * (except for -).
     *
     * @param String $id Id of the body element
     */
    public static function setBodyElementId($id)
    {
        self::$body_element_id = preg_replace('/[^\w-]/', '_', $id);
    }

    /**
     * Gets the id of the body element.
     * If non was set, it is dynamically generated base on the name of
     * the current PHP script, with the suffix removed and all
     * non-alphanumeric characters replaced with '_'.
     *
     * @return String containing the body element id
     */
    public static function getBodyElementId()
    {
        // Return specific or dynamically generated body element id
        return self::$body_element_id
            ?: preg_replace('/\W/', '_', basename($_SERVER['PHP_SELF'], '.php'));
    }

    /**
     * Registers a MessageBox object for display the next time a layout
     * is rendered. Note: This will only work for pages that use layout
     * templates.
     *
     * @param MessageBox  message object to display
     */
    public static function postMessage(MessageBox $message, $id = null)
    {
        if ($id === null ) {
            $_SESSION['messages'][] = $message;
        } else {
            $_SESSION['messages'][$id] = $message;
        }
    }

    /**
     * Clears all messages pending for display.
     */
    public static function clearMessages()
    {
        unset($_SESSION['messages']);
    }

    /**
     * Returns the list of pending messages and clears the list.
     *
     * @return array    list of MessageBox objects
     */
    public static function getMessages()
    {
        $messages = array();

        if (isset($_SESSION['messages'])) {
            $messages = $_SESSION['messages'];
            self::clearMessages();
        }

        return $messages;
    }

    /**
     * Return the names of the squeeze packages to use.
     *
     * Per default the squeeze package "base" is included.
     *
     * @return array  an array containing the names of the packages
     */
    public static function getSqueezePackages()
    {
        return self::$squeeze_packages;
    }

    /**
     * Set the names of the squeeze packages to use
     *
     * @code
     * # use as many arguments as you want
     * PageLayout::setSqueezePackages("base");
     * PageLayout::setSqueezePackages("base", "admin");
     * PageLayout::setSqueezePackages("base", "admin", "upload");
     * # PageLayout::setSqueezePackages(...);
     * @endcode
     *
     * @param ...
     *    a variable-length argument list containing the names of the packages
     */
    public static function setSqueezePackages($package/*, ...*/)
    {
        self::$squeeze_packages = func_get_args();
    }

    /**
     * Add a squeeze package to the list of squeeze packages to use
     *
     * @code
     * PageLayout::addSqueezePackage("admin");
     * @endcode
     *
     * @param string $package  the name of the package
     */
    public static function addSqueezePackage($package)
    {
        self::$squeeze_packages[] = $package;
    }

    /**
     * Depending on \Studip\ENV, either includes individual script
     * elements for each JS file in every package, or a single script
     * element containing the squeezed source code for every package.
     */
    private static function includeSqueezePackages()
    {
        global $STUDIP_BASE_PATH;

        $config_path   = "$STUDIP_BASE_PATH/config/assets.yml";
        $configuration = Configuration::load($config_path);
        $packager      = new Packager($configuration);
        $javascripts   = \Studip\Squeeze\includePackages($packager, self::getSqueezePackages());

        $css = array();
        foreach (self::getSqueezePackages() as $package) {
            if (isset($configuration['css'][$package])) {
                foreach ($configuration['css'][$package] as $filename => $media) {
                    $attributes = array(
                        'rel' => 'stylesheet',
                        'href' => \Studip\Squeeze\shouldPackage()
                             ? $configuration['package_url'] . '/' . $package . '-' . $filename
                             : Assets::stylesheet_path($filename),
                        'media' => $media
                    );
                    $css[$package . '-' . $filename] = array(
                        'name'       => 'link',
                        'attributes' => $attributes
                    );
                }
            }
        }

        return array_merge($css, $javascripts);
    }
}
