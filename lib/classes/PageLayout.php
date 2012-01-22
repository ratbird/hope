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
        // include jQuery + UI
        self::addStylesheet('jquery-ui-1.8.14.custom.css', array('media' => 'screen, print'));
        self::addStylesheet('jquery-ui-studip-2.2.css', array('media' => 'screen, print'));
        // include default CSS
        self::addHeadElement('link', array('rel' => 'shortcut icon', 'href' => Assets::image_path('favicon.ico')));
        self::addStylesheet('style.css', array('media' => 'screen, print'));
        self::addStylesheet('header.css', array('media' => 'screen, print'));
        self::addStylesheet('smiley.css', array('media' => 'screen, print'));

        self::setSqueezePackages("base");
    }

    /**
     * Set the page title to the given text.
     */
    public static function setTitle($title)
    {
        self::$title = $title;
    }

    /**
     * Get the current page title (defaults to $UNI_NAME_CLEAN).
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
        self::$tab_navigation = isset($path) ? Navigation::getItem($path) : NULL;
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
     * Add a STYLE element to the HTML HEAD section.
     *
     * @param string $content   element contents
     */
    public static function addStyle($content)
    {
        self::addHeadElement('style', array(), $content);
    }

    /**
     * Add a style sheet LINK element to the HTML HEAD section.
     *
     * @param string $source     style sheet URL or file in assets folder
     * @param array  $attributes additional attributes for LINK element
     */
    public static function addStylesheet($source, $attributes = array())
    {
        $style_attributes = array(
            'rel'   => 'stylesheet',
            'href'  => Assets::stylesheet_path($source));

        self::addHeadElement('link', array_merge($style_attributes, $attributes));
    }

    /**
     * Remove a style sheet LINK element from the HTML HEAD section.
     *
     * @param string $source     style sheet URL or file in assets folder
     * @param array  $attributes additional attributes for LINK element
     */
    public static function removeStylesheet($source, $attributes = array())
    {
        $style_attributes = array(
            'rel'   => 'stylesheet',
            'href'  => Assets::stylesheet_path($source));

        self::removeHeadElement('link', array_merge($style_attributes, $attributes));
    }

    /**
     * Add a JavaScript SCRIPT element to the HTML HEAD section.
     *
     * @param string $source     URL of JS file or file in assets folder
     */
    public static function addScript($source)
    {
        $script_attributes = array(
            'src'   => Assets::javascript_path($source));

        self::addHeadElement('script', $script_attributes, '');
    }

    /**
     * Remove a JavaScript SCRIPT element from the HTML HEAD section.
     *
     * @param string $source     URL of JS file or file in assets folder
     */
    public static function removeScript($source)
    {
        $script_attributes = array(
            'src'   => Assets::javascript_path($source));

        self::removeHeadElement('script', $script_attributes);
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
     * Return all HTML HEAD elements as a string.
     *
     * @return string   HTML fragment
     */
    public static function getHeadElements()
    {
        $result = '';

        if (isset($GLOBALS['_include_stylesheet'])) {
            self::removeStylesheet('style.css');
            self::addStylesheet($GLOBALS['_include_stylesheet'], array('media' => 'screen, print'));
        }

        $head_elements = array_merge(self::includeSqueezePackages(), self::$head_elements);

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
     * Get a dynamically generated ID for the BODY element.
     * The ID is based on the name of the PHP script, with the suffix
     * removed and all non-alphanumeric characters replace with '_'.
     */
    public static function getBodyElementId()
    {
        return preg_replace('/\W/', '_', basename($_SERVER['PHP_SELF'], '.php'));
    }

    /**
     * Registers a MessageBox object for display the next time a layout
     * is rendered. Note: This will only work for pages that use layout
     * templates.
     *
     * @param MessageBox  message object to display
     */
    public static function postMessage(MessageBox $message)
    {
        $_SESSION['messages'][] = $message;
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

        $elements = array();
        foreach (self::getSqueezePackages() as $package) {
            $elements += self::includeSqueezePackage($packager, $package);
        }

        return $elements;
    }

    /**
     * Include a single squeeze package depending on \Studip\ENV as
     * individual script elements or as a single one containing the
     * squeezed source code of all files comprising the package.
     *
     * @return an array containing PageLayout style HTML elements
     */
    private static function includeSqueezePackage($packager, $package)
    {
        $elements = array();
        if (\Studip\ENV === 'development') {
            foreach ($packager->individualURLs($package) as $src) {
                $elements[] = array(
                    'name'       => 'script',
                    'attributes' => compact('src'),
                    'content'    => '');
            }
        } else {
            $src = $packager->packageURL($package);
            $charset = 'utf-8';
                $elements[] = array(
                    'name'       => 'script',
                    'attributes' => compact('src', 'charset'),
                    'content'    => '');
        }
        return $elements;
    }
}
