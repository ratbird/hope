<?php
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
namespace Studip;

require_once 'vendor/HTMLPurifier/HTMLPurifier.auto.php';
require_once 'htmlpurifier/HTMLPurifier_Injector_ClassifyLinks.php';
require_once 'htmlpurifier/HTMLPurifier_Injector_Unlinkify.php';

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
    public static function apply($markup, $text, $trim)
    {
        if (self::isHtml($text)){
            return self::markupPurified($markup, $text, $trim);
        }
        return self::markupHtmlReady($markup, $text, $trim);
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
    public static function isHtml($text)
    {
        // TODO compare trimming-and-comparing runtime to using regexp
        $trimmed = trim($text);
        return $trimmed[0] === '<' && substr($trimmed, -1) === '>';
    }

    /**
     * Run text through HTML purifier and afterwards apply markup rules.
     *
     * @param TextFormat $markup  Markup rules applied on marked-up text.
     * @param string     $text    Marked-up text on which rules are applied.
     * @param boolean    $trim    Trim text before applying markup rules, if TRUE.
     *
     * @return string  HTML code computed from marked-up text.
     */
    private static function markupPurified($markup, $text, $trim)
    {
        $text = self::unixEOL($text);
        if ($trim) {
            $text = trim($text);
        }
        return self::markupText($markup, self::purify($text));
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
    private static function markupHtmlReady($markup, $text, $trim)
    {
        return str_replace("\n", '<br>', self::markupText(
            $markup, self::htmlReady(self::unixEOL($text), $trim)));
    }

    /**
     * Convert line break to Unix format.
     *
     * @param string $text  Text with possibly mixed line breaks (Win, Mac, Unix).
     *
     * @return string  Text with Unix line breaks only.
     */
    private static function unixEOL($text)
    {
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
    private static function markupText($markup, $text)
    {
        return symbol(smile($markup->format($text), false));
    }

    /**
     * Call HTMLPurifier to create safe HTML.
     *
     * @param   string $dirty_html  Unsafe or 'uncleaned' HTML code.
     * @return  string              Clean and safe HTML code.
     */
    public static function purify($dirty_html)
    {
        // remember created purifier so it doesn't have to be created again
        static $purifier = NULL;
        if ($purifier === NULL) {
            $purifier = self::createPurifier();
        }
        return studip_utf8decode(
            $purifier->purify(studip_utf8encode($dirty_html)));
    }

    /**
     * Create HTML purifier instance with Stud.IP-specific configuration.
     * @return HTMLPurifier A new instance of the HTML purifier.
     */
    private static function createPurifier()
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.RemoveInvalidImg', true);
        $config->set('Attr.AllowedFrameTargets', array('_blank'));
        $config->set('Attr.AllowedRel', array('nofollow'));
        $config->set('AutoFormat.Custom', array('ClassifyLinks'));

        // avoid <img src="evil_CSRF_stuff">
        $def = $config->getHTMLDefinition(true);
        $img = $def->addBlankElement('img');
        $img->attr_transform_post[]
            = new MarkupPrivate\Purifier\AttrTransform_Image_Source();

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
    public static function htmlReady(
        $text, $trim = true, $br = false, $double_encode = true
    ) {
        $text = htmlspecialchars($text, ENT_QUOTES, 'cp1252', $double_encode);
        $text = preg_replace('/&amp;#([1-9]{1,1}[0-9]{2,});/', '&#$1;', $text);
        if ($trim) {
            $text = trim($text);
        }
        if ($br) { // fix newlines
            $text = nl2br($text, false);
        }
        return $text;
    }

    public static function removeHTML($html) {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'ISO-8859-1');
        $config->set('HTML.Allowed', 'a[href],img[src]');
        $config->set('AutoFormat.Custom', array('Unlinkify'));

        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($html);
    }
}

/**
 * Members of Studip\MarkupPrivate must not be used outside of this file!!
 */

namespace Studip\MarkupPrivate\Purifier;

use Studip\MarkupPrivate\MediaProxy;

/**
 * Remove invalid <img src> attributes.
 */
class AttrTransform_Image_Source extends \HTMLPurifier_AttrTransform
{
    /**
     * Implements abstract method of base class.
     */
    function transform($attr, $config, $context)
    {
        try {
            $attr['src'] = MediaProxy\getMediaUrl($attr['src']);
        } catch (MediaProxy\InvalidInternalLinkException $e) {
            // invalid internal link ==> remove <img src> attribute
            $GLOBALS['msg'][] = _('Ungültige interne Medienverknüpfung entfernt: ')
                . \htmlentities($e->getUrl());
            $attr['src'] = NULL; // remove <img src> attribute
        } catch (MediaProxy\ExternalMediaDeniedException $e) {
            $GLOBALS['msg'][] = _('Verbotene externe Medienverknüpfung entfernt: ')
                . \htmlentities($e->getUrl());
            $attr['src'] = NULL; // remove <img src> attribute
        }
        return $attr;
    }
}

//// media proxy //////////////////////////////////////////////////////////////

namespace Studip\MarkupPrivate\MediaProxy;

use Studip\MarkupPrivate\String;

/**
 * Check if media proxy should be used and if so return the respective URL.
 *
 * @param string $url   URL to media file.
 * @return mixed        URL string to media file (possibly 'proxied')
 *                      or NULL if URL is invalid.
 */
function getMediaUrl($url) {
    // even though proxied URLs shouldn't be stored in the database, the
    // next line will handle those cases where they're accidentally there
    $url = decodeMediaProxyUrl($url);

    // handle internal media links
    if (isStudipMediaUrl($url)) {
        return transformInternalIdnaLink($url);
    }
    if (isInternalLink($url)) {
        // link is studip-internal, but not to a valid media location
        throw new InvalidInternalLinkException($url);
    }

    // handle external media links
    $external_media = \Config::get()->LOAD_EXTERNAL_MEDIA;
    if ($external_media === 'proxy' &&
        \Seminar_Session::is_current_session_authenticated()
    ) {
        // media proxy must be accessed by an internal link
        return encodeMediaProxyUrl($url);
    }
    if ($external_media === 'allow') {
        return $url;
    }
    throw new ExternalMediaDeniedException($url);
}

/**
 * Return media proxy URL for an unproxied URL.
 *
 * @params string $url  Unproxied media URL.
 * @return string       Media proxy URL for accessing the same resource.
 */
function encodeMediaProxyUrl($url) {
    return transformInternalIdnaLink(
        getMediaProxyUrl() .'?url=' . \urlencode(\idna_link($url)));
}

/**
 * Extract the original URL from a media proxy URL.
 *
 * @param string  $url  The media proxy URL.
 * return string  The original URL. If $url does not point to the media
 *                proxy then this is the exact same value given by $url.
 */
function decodeMediaProxyUrl($url) {
    # TODO make it work for 'url=' at any position in query
    $urlpath = removeStudipDomain($url);
    $proxypath = removeStudipDomain(getMediaProxyUrl()) . '?url=';
    if (String\startsWith($urlpath, $proxypath)) {
        return \urldecode(String\removePrefix($urlpath, $proxypath));
    }
    return $url;
}

/**
 * Return Stud.IP's absolute media proxy URL.
 */
function getMediaProxyUrl() {
    return $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'dispatch.php/media_proxy';
}

/**
 * Test if an URL points to a valid internal Stud.IP media path.
 *
 * @param   string   $url  Internal Stud.IP URL.
 * @returns boolean  TRUE for internal media link URLs, FALSE otherwise.
 */
function isStudipMediaUrl($url) {
    return isInternalLink($url) &&
        isStudipMediaUrlPath(getStudipRelativePath($url));
}

function isInternalLink($url) {
    return is_internal_url(transformInternalIdnaLink($url));
}

//// url utilities ////////////////////////////////////////////////////////////

/**
 * Remove domain name from internal URLs.
 *
 * Remove scheme, domain and authentication information from internal
 * Stud.IP URLs. Leave external URLs untouched.
 *
 * @param string $url   URL from which to remove internal domain.
 * @returns string      URL without internal domain or the exact same
 *                      value as $url for external URLs.
 */
function removeStudipDomain($url) {
    if (!isInternalLink($url)) {
        return $url;
    }
    $parsed_url = \parse_url(transformInternalIdnaLink($url));
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return $path . $query . $fragment;
}

/**
 * Return a URL's path component with the absolute Stud.IP path removed.
 *
 * NOTE: If the URL is not an internal Stud.IP URL, the path component will
 * nevertheless be returned without issuing an error message.
 *
 * Example:
 * >>> getStudipRelativePath('http://localhost:8080'
 *      . '/studip/sendfile.php?type=0&file_id=ABC123&file_name=nice.jpg')
 * 'sendfile.php'
 *
 * @param string $url   The URL from which to return the Stud.IP-relative
 *                      path component.
 * returns string Stud.IP-relative path component of $url.
 */
function getStudipRelativePath($url) {
    $parsed_url = \parse_url(transformInternalIdnaLink($url));
    $parsed_studip_url = getParsedStudipUrl();
    return String\removePrefix($parsed_url['path'], $parsed_studip_url['path']);
}

/**
 * Return an associative array containing the Stud.IP URL elements.
 *
 * see also: http://php.net/manual/en/function.parse-url.php
 *
 * @returns mixed  Same values that PHP's parse_url() returns.
 */
function getParsedStudipUrl() {
    return \parse_url($GLOBALS['ABSOLUTE_URI_STUDIP']);
}

/**
 * Test if path is valid for internal Stud.IP media URLs.
 *
 * @params string $path The path component of an URL.
 * return boolean       TRUE for valid media paths, FALSE otherwise.
 */
function isStudipMediaUrlPath($path) {
    list($path_head) = \explode('/', $path);
    $valid_paths = array('sendfile.php', 'download', 'assets', 'pictures');
    return \strpos(\urldecode($path), '../') === false && \in_array($path_head, $valid_paths);
}

/**
 * Return a normalized, internal URL.
 *
 * @params string $url  An internal URL.
 * @returns string      Normalized internal URL.
 */
function transformInternalIdnaLink($url) {
    return \idna_link(\TransformInternalLinks($url));
}

//// url exceptions ///////////////////////////////////////////////////////////

class UrlException extends \Exception
{
    private $url;

    public function __construct($url) {
        parent::__construct();
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }
}

class InvalidInternalLinkException extends UrlException
{
}

class ExternalMediaDeniedException extends UrlException
{
}

//// string utilities /////////////////////////////////////////////////////////

namespace Studip\MarkupPrivate\String;

/**
 * Test if string starts with prefix.
 *
 * @param string $string  Tested string.
 * @param string $prefix  Prefix of tested string.
 *
 * @return boolean  TRUE if string starts with prefix.
 */
function startsWith($string, $prefix) {
    return \substr($string, 0, \strlen($prefix)) === $prefix;
}

/**
 * Test if string ends with suffix.
 *
 * @param string $string  Tested string.
 * @param string $suffix  Suffix of tested string.
 *
 * @return boolean  TRUE if string ends with suffix.
 */
function endsWith($string, $suffix) {
    return \substr($string, \strlen($string) - \strlen($suffix)) === $suffix;
}

/**
 * Remove prefix from string.
 *
 * Does not change the string if it has a different prefix.
 *
 * @param string $string The string that must start with the prefix.
 * @param string $prefix The prefix of the string.
 *
 * @return string String without prefix.
 */
function removePrefix($string, $prefix) {
    return startsWith($string, $prefix) ? \substr($string, \strlen($prefix)) : $string;
}
