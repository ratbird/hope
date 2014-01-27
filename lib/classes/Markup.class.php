<?php namespace Studip;
/**
 * Markup.class.php - Handling of Stud.IP- and HTML-markup.
 **
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @category    Stud.IP
 * @copyright   (c) 2014 Stud.IP e.V.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @since       File available since Release 3.0
 * @author      Robert Costa <rcosta@uos.de>
 */
require_once 'vendor/HTMLPurifier/HTMLPurifier.auto.php';

class Markup
{
    /**
     * Apply markup rules and clean the text up.
     *
     * @param TextFormat $markup  Markup rules applied on marked-up text.
     * @param string     $text    Marked-up text on which rules are applied.
     * @param boolean    $trim    Trim text before applying markup rules, if TRUE.
     *
     * @return string  HTML code computed from marked-up text.
     */
    static function apply($markup, $text, $trim){
        if (Markup::isHtml($text)){
            return Markup::markupAndPurify($markup, $text, $trim);
        }
        return Markup::markupHtmlReady($markup, $text, $trim);
    }
    
    /**
     * Return True for HTML code and False for plain text.
     *
     * A fairly simple heuristic is used: Every text that begins with '<'
     * and ends with '>' is considered to be HTML code. Leading and trailing 
     * whitespace characters are ignored.
     *
     * @param string $text  HTML code or plain text.
     *
     * @return boolean  TRUE for HTML code, FALSE for plain text.
     */
    static function isHtml($text){
        // TODO compare trimming-and-comparing runtime to using regexp
        $trimmed = trim($text);
        return $trimmed[0] === '<' && substr($trimmed, -1) === '>';
    }
    
    /**
     * Run text through HTML purifier after applying markup rules.
     *
     * @param TextFormat $markup  Markup rules applied on marked-up text.
     * @param string     $text    Marked-up text on which rules are applied.
     * @param boolean    $trim    Trim text before applying markup rules, if TRUE.
     *
     * @return string  HTML code computed from marked-up text.
     */
    static function markupAndPurify($markup, $text, $trim){
        $text = Markup::unixEOL($text);
        if ($trim) {
            $text = trim($text);
        }
        return Markup::purify(Markup::markup($markup, $text));
    }

    /**
     * Apply markup rules after running text through HTML ready.
     *
     * @param TextFormat $markup  Markup rules applied on marked-up text.
     * @param string     $text    Marked-up text on which rules are applied.
     * @param boolean    $trim    Trim text before applying markup rules, if TRUE.
     *
     * @return string  HTML code computed from marked-up text.
     */
    static function markupHtmlReady($markup, $text, $trim){
        return Markup::markup(
            $markup, Markup::htmlReady(Markup::unixEOL($text), $trim));
    }
    
    /**
     * Convert line break to Unix format.
     *
     * @param string $text  Text with possibly mixed line breaks (Win, Mac, Unix).
     *
     * @return string  Text with Unix line breaks only.
     */
    static function unixEOL($text){
        return preg_replace("/\r\n?/", "\n", $text);
    }
    
    /**
     * Apply markup rules on plain text.
     *
     * @param TextFormat $markup  Markup rules applied on marked-up text.
     * @param string     $text    Marked-up text on which rules are applied.
     *
     * @return string  HTML code computed from marked-up text.
     */
    static function markup($markup, $text){
        $text = $markup->format($text);
        $text = symbol(smile($text, false));
        return str_replace("\n", '<br>', $text);
    }

    /**
     * Call HTMLPurifier to create safe HTML.
     *
     * @param   string $dirty_html  Unsafe or 'uncleaned' HTML code.
     * @return  string              Clean and safe HTML code.
     */
    function purify($dirty_html){
        // remember created purifier so it doesn't have to be created again
        static $purifier = NULL;
        if ($purifier === NULL) {
            $purifier = Markup::createPurifier();
        }
        return studip_utf8decode(
            $purifier->purify(studip_utf8encode($dirty_html)));
    }

    /**
     * Create HTML purifier instance with Stud.IP-specific configuration.
     * @return HTMLPurifier A new instance of the HTML purifier.
     */
    function createPurifier() {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.RemoveInvalidImg', true);
        $config->set('Attr.AllowedFrameTargets', array('_blank'));
        $config->set('Attr.AllowedRel', array('nofollow'));

        // avoid <img src="evil_CSRF_stuff">
        $def = $config->getHTMLDefinition(true);
        $img = $def->addBlankElement('img');
        $img->attr_transform_post[] = new AttrTransform_Image_Source();

        return new \HTMLPurifier($config);
    }

    /**
     * Convert special characters to HTML entities, and clean up.
     *
     * @param  string  $text  This text's special chars will be converted.
     * @param  boolean $trim  Trim text before applying markup rules, if TRUE.
     * @param  boolean $br    Replace newlines by <br>, if TRUE.
     * @param  boolean $double_encode  Encode existing HTML entities, if TRUE.
     * @return string         The converted string.
     */
    function htmlReady($text, $trim=TRUE, $br=FALSE, $double_encode=FALSE) {
        $text = htmlspecialchars($text, ENT_QUOTES, 'cp1252', $double_encode);
        if ($trim) {
            $text = trim($text);
        }
        if ($br) { // fix newlines
            $text = nl2br($text, false);
        }
        return $text;
    }
}

/**
 * Remove invalid <img src> attributes.
 */
class AttrTransform_Image_Source extends \HTMLPurifier_AttrTransform
{
    /**
     * Implements abstract method of base class.
     */
    function transform($attr, $config, $context) {
        $attr['src'] = Utils::getMediaUrl($attr['src']);
        return $attr;
    }
}
