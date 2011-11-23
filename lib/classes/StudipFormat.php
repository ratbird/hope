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
    private static $studip_rules = array(

        // heading level 1-4
        'heading' => array(
            'start'    => '^(!{1,4})([^\n]+)\n?',
            'callback' => 'StudipFormat::markupHeading'
        ),

        // horizontal rule
        'hrule' => array(
            'start'    => '^--+$',
            'callback' => 'StudipFormat::markupHorizontalRule'
        ),

        // list and table
        'list' => array(
            'start'    => '(^[=-]+ [^\n]+\n?)+',
            'callback' => 'StudipFormat::markupList'
        ),
        'table' => array(
            'start'    => '(^\|[^\n]*\|[^\n]*\n?)+',
            'callback' => 'StudipFormat::markupTable'
        ),

        // block indent
        'indent' => array(
            'start'    => '(^  [^\n]+\n?)+',
            'callback' => 'StudipFormat::markupIndent'
        ),

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
        'admin_msg' => array(
            'start'    => '\[admin_msg\]',
            'end'      => '\[\/admin_msg\]',
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

        // preformatted text, quote, nop and code
        'pre' => array(
            'start'    => '\[pre\]',
            'end'      => '\[\/pre\]',
            'callback' => 'StudipFormat::markupPreformat'
        ),
        'quote' => array(
            'start'    => '\[quote(=.*?)?\]',
            'end'      => '\[\/quote\]',
            'callback' => 'StudipFormat::markupQuote'
        ),
        'nop' => array(
            'start'    => '\[nop\](.*?)\[\/nop\]',
            'callback' => 'StudipFormat::markupNoFormat'
        ),
        'code' => array(
            'start'    => '\[code\](.*?)\[\/code\]',
            'callback' => 'StudipFormat::markupCode'
        ),
    );

    /**
     * Returns the list of global Stud.IP markup rules as an array.
     * Each entry has the following attributes: 'start', 'end' and
     * 'callback'. The rule name is used as the entry's array key.
     *
     * @return array list of all markup rules
     */
    public static function getStudipMarkups()
    {
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
     * Stud.IP markup for headings
     */
    protected static function markupHeading($markup, $matches)
    {
        $level = max(1, 5 - strlen($matches[1]));
        $text = $markup->format($matches[2]);

        return sprintf('<h%d class="content">%s</h%d>', $level, $text, $level);
    }

    /**
     * Stud.IP markup for horizontal rule
     */
    protected static function markupHorizontalRule($markup, $matches)
    {
        return '<hr class="content">';
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
            '{-' => 'strike',
            '[admin_msg]' => 'i'
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

    /**
     * Stud.IP markup for lists (may be nested)
     */
    protected static function markupList($markup, $matches)
    {
        $rows = explode("\n", rtrim($matches[0]));
        $indent = 0;

        foreach ($rows as $row) {
            list($level, $text) = explode(' ', $row, 2);
            $level = strlen($level);

            if ($indent < $level) {
                for (; $indent < $level; ++$indent) {
                    $type = $row[$indent] == '=' ? 'ol' : 'ul';
                    $result .= sprintf('<%s><li>', $type);
                    $types[] = $type;
                }
            } else {
                for (; $indent > $level; --$indent) {
                    $result .= sprintf('</li></%s>', array_pop($types));
                }

                $result .= '</li><li>';
            }

            $result .= $markup->format($text);
        }

        for (; $indent > 0; --$indent) {
            $result .= sprintf('</li></%s>', array_pop($types));
        }

        return $result;
    }

    /**
     * Stud.IP markup for tables
     */
    protected static function markupTable($markup, $matches)
    {
        $rows = explode("\n", rtrim($matches[0]));
        $result = '<table class="content">';

        foreach ($rows as $row) {
            $cells = explode('|', trim(trim($row), '|'));
            $result .= '<tr>';

            foreach ($cells as $cell) {
                $result .= '<td>';
                $result .= $markup->format(trim($cell));
                $result .= '</td>';
            }

            $result .= '</tr>';
        }

        $result .= '</table>';

        return $result;
    }

    /**
     * Stud.IP markup for indented paragraphs
     */
    protected static function markupIndent($markup, $matches)
    {
        $text = preg_replace('/^  /m', '', $matches[0]);

        return sprintf('<p class="indent">%s</p>', $markup->format($text));
    }

    /**
     * Stud.IP markup for preformatted text
     */
    protected static function markupPreformat($markup, $matches, $contents)
    {
        return sprintf('<pre>%s</pre>', trim($contents));
    }

    /**
     * Stud.IP markup for quoted text
     */
    protected static function markupQuote($markup, $matches, $contents)
    {
        if (strlen($matches[1]) > 1) {
            $title = sprintf(_('%s hat geschrieben:'), $markup->format(substr($matches[1], 1)));
        } else {
            $title = _('Zitat:');
        }

        return sprintf('<blockquote class="quote"><b>%s</b><hr>%s</blockquote>',
                       $title, trim($contents));
    }

    /**
     * Stud.IP markup for unformatted text
     */
    protected static function markupNoFormat($markup, $matches)
    {
        return $markup->quote($matches[1]);
    }

    /**
     * Stud.IP markup for (PHP) source code
     */
    protected static function markupCode($markup, $matches)
    {
        return highlight_string(html_entity_decode(trim($matches[1]), ENT_QUOTES), true);
    }
}
