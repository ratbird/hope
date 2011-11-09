<?php
/**
 * StudipFormat.php - simple Stud.IP text markup parser
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

require_once 'TextFormat.php';

class StudipFormat extends TextFormat
{
    /**
     * list of global Stud.IP markup rules
     */
    private static $studip_rules;

    /**
     * Returns the list of global Stud.IP markup rules as an array.
     * Each entry has the following attributes: 'start', 'end' and
     * 'callback'. The rule name is used as the entry's array key.
     *
     * @return array list of all markup rules
     */
    public static function getStudipMarkups()
    {
        if (!isset(self::$studip_rules)) {
            self::$studip_rules = array(

                // basic text formatting
                'bold' => array(
                    'start'    => '\*\*',
                    'end'      => '\*\*',
                    'callback' => 'StudipFormat::markupText'
                ),
                'italics' => array(
                    'start'    => '%%',
                    'end'      => '%%',
                    'callback' => 'StudipFormat::markupText'
                ),
                'underline' => array(
                    'start'    => '__',
                    'end'      => '__',
                    'callback' => 'StudipFormat::markupText'
                ),
                'verb' => array(
                    'start'    => '##',
                    'end'      => '##',
                    'callback' => 'StudipFormat::markupText'
                ),
                'big' => array(
                    'start'    => '\+\+',
                    'end'      => '\+\+',
                    'callback' => 'StudipFormat::markupText'
                ),
                'small' => array(
                    'start'    => '--',
                    'end'      => '--',
                    'callback' => 'StudipFormat::markupText'
                ),
                'super' => array(
                    'start'    => '&gt;&gt;',
                    'end'      => '&gt;&gt;',
                    'callback' => 'StudipFormat::markupText'
                ),
                'sub' => array(
                    'start'    => '&lt;&lt;',
                    'end'      => '&lt;&lt;',
                    'callback' => 'StudipFormat::markupText'
                ),
                'strike' => array(
                    'start'    => '\{-',
                    'end'      => '-\}',
                    'callback' => 'StudipFormat::markupText'
                ),

                // basic text formatting (simple form)
                'simple_bold' => array(
                    'start'    => '(?<=\s|^)\*(\S+)\*(?=\s|$)',
                    'callback' => 'StudipFormat::markupTextSimple'
                ),
                'simple_italics' => array(
                    'start'    => '(?<=\s|^)%(\S+)%(?=\s|$)',
                    'callback' => 'StudipFormat::markupTextSimple'
                ),
                'simple_underline' => array(
                    'start'    => '(?<=\s|^)_(\S+)_(?=\s|$)',
                    'callback' => 'StudipFormat::markupTextSimple'
                ),
                'simple_verb' => array(
                    'start'    => '(?<=\s|^)#(\S+)#(?=\s|$)',
                    'callback' => 'StudipFormat::markupTextSimple'
                ),
                'simple_big' => array(
                    'start'    => '(?<=\s|^)\+(\S+)\+(?=\s|$)',
                    'callback' => 'StudipFormat::markupTextSimple'
                ),
                'simple_small' => array(
                    'start'    => '(?<=\s|^)-(\S+)-(?=\s|$)',
                    'callback' => 'StudipFormat::markupTextSimple'
                ),
                'simple_super' => array(
                    'start'    => '(?<=\s|^)&gt;(\S+)&gt;(?=\s|$)',
                    'callback' => 'StudipFormat::markupTextSimple'
                ),
                'simple_sub' => array(
                    'start'    => '(?<=\s|^)&lt;(\S+)&lt;(?=\s|$)',
                    'callback' => 'StudipFormat::markupTextSimple'
                ),
            );
        }

        return self::$studip_rules;
    }

    /**
     * Adds a new markup rule to the global Stud.IP markup set. This can
     * also be used to replace an existing markup rule. The end regular
     * expression is optional (i.e. may be NULL) to indicate that this
     * rule has an empty content model. The callback is called whenever
     * the rule matches and is passed the following arguments:
     *
     * - $markup    the markup parser object
     * - $matches   match results of preg_match for $start
     * - $contents  (parsed) contents of this markup rule
     *
     * @param string $name      name of this rule
     * @param string $start     start regular expression
     * @param string $end       end regular expression (optional)
     * @param callback $callback function generating output of this rule
     */
    public static function addStudipMarkup($name, $start, $end, $callback)
    {
        self::$studip_rules[$name] = compact('start', 'end', 'callback');
    }

    /**
     * Removes a markup rule from the global Stud.IP markup set.
     *
     * @param string $name      name of the rule
     */
    public static function removeStudipMarkup($name)
    {
        unset(self::$studip_rules[$name]);
    }

    /**
     * Initializes a new StudipFormat instance.
     */
    public function __construct()
    {
        parent::__construct(self::getStudipMarkups());
    }

    /**
     * Basic text formatting: bold, italics, underline, big, small etc.
     */
    protected static function markupText($markup, $matches, $contents)
    {
        static $tag = array(
            '**' => 'b',
            '%%' => 'i',
            '__' => 'u',
            '##' => 'tt',
            '++' => 'big',
            '--' => 'small',
            '&gt;&gt;' => 'sup',
            '&lt;&lt;' => 'sub',
            '{-' => 'strike'
        );

        $key = $matches[0];

        return sprintf('<%s>%s</%s>', $tag[$key], $contents, $tag[$key]);
    }

    /**
     * Basic text formatting: bold, italics, underline etc. (simple form)
     */
    protected static function markupTextSimple($markup, $matches)
    {
        static $tag = array(
            '*' => 'b',
            '%' => 'i',
            '_' => 'u',
            '#' => 'tt',
            '+' => 'big',
            '-' => 'small',
            '>' => 'sup',
            '<' => 'sub'
        );

        $key = $matches[0][0];
        $text = str_replace($key, ' ', $matches[1]);

        return sprintf('<%s>%s</%s>', $tag[$key], $markup->quote($text), $tag[$key]);
    }
}
