<?php
/**
 * markup_class_test.php - Unit tests for the Markup class.
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
require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once 'tests/unit/fakeserver.php';

# needed by visual.inc.php
require_once 'lib/classes/DbView.class.php';
require_once 'lib/classes/TreeAbstract.class.php';

# needed by Markup.class.php
require_once 'lib/visual.inc.php';
require_once 'lib/classes/Config.class.php';

# class and functions that are tested by this script
require_once 'lib/classes/Markup.class.php';

# Seminar_Session cannot be mocked since it uses static functions.
# Also, including phplib_local.inc.php, where Seminar_Session is
# defined, introduces a massive amount of dependencies that are otherwise
# completely unneeded for testing the Markup class.
# Instead, create a fake class.
# => But note, this will fail if another test case does the same thing!
class Seminar_Session
{
    public static function is_current_session_authenticated()
    {
        return true;
    }
}

/**
 * Test case for Markup class.
 */
class MarkupTest extends PHPUnit_Framework_TestCase
{
    # check for all errors in this test

    private static $originalErrorReporting;

    public function setUp()
    {
        MarkupTest::$originalErrorReporting = error_reporting();

        // E_STRICT was not part of E_ALL in PHP < 5.4.0
        error_reporting(E_ALL | E_STRICT);
    }

    public function tearDown()
    {
        error_reporting(MarkupTest::$originalErrorReporting);
    }

    //// unit tests

    /**
     * Identify HTML content.
     *
     * @param $html string  HTML text as defined by current
     *                      Stud.IP without fallback-solution.
     *
     * @dataProvider htmlProvider
     */
    public function testIsHtml($html)
    {
        $this->mockConfig();
        Config::get()->WYSIWYG = true;
        Config::get()->WYSIWYG_HTML_HEURISTIC_FALLBACK = false;
        $this->assertTrue(StudIp\Markup::isHtml($html));
    }

    // Stud.IP 3.2's HTML marker
    const STUDIP_3_2_HTML_MARKER
        = '<!-- HTML: Insert text after this line only. -->';

    /**
     * Provide plain HTML, no Stud.IP markup allowed.
     */
    public function htmlProvider()
    {
        return array_merge(
            $this->newHtmlProvider(),
            array(array(self::STUDIP_3_2_HTML_MARKER))
        );
    }

    /**
     * Provide plain HTML, no Stud.IP markup allowed.
     *
     * Must not contain Stud.IP 3.2's HTML marker, even though
     * it's a valid "plain HTML" marker from Stud.IP 3.3 onwards.
     */
    public function newHtmlProvider()
    {
        return array_map(function ($e) { return array($e); }, array(
            // simplest possible marker, default marker
            '<!--HTML-->',
            StudIp\Markup::HTML_MARKER,

            // case insensitivity
            '<!--html-->',
            '<!--Html-->',
            '<!--hTmL-->',
            '<!--htMl-->',

            // initial whitespace
            ' <!--HTML-->', // spaces
            "\t<!--HTML-->", // tabs
            "\n<!--HTML-->", // new line
            "\r<!--HTML-->", // carriage return
            "\r\n<!--HTML-->", // windows line break
            " \t\r\n \t\r\n \t\r\n<!--HTML-->", // all mixed up

            // whitespace in marker
            '<!--   HTML   -->', // spaces
            "<!--\t\t\tHTML\t\t\t-->", // tabs
            "<!--\n\n\nHTML\n\n\n-->", // new line
            "<!--\r\r\rHTML\r\r\r-->", // carriage return
            "<!--\r\n\r\n\r\nHTML\r\n\r\n\r\n-->", // windows line break
            "<!-- \t\r\nHTML \t\r\n-->", // all mixed up

            // appended content
            '<!-- HTML --><p>Blabla</p>', // closing HTML tags at end
            '<!-- HTML -->&nbsp;<br />&nbsp;', // HTML entities at end
            '<!-- HTML -->HTML with plain text only.', // plain text at end
            '<!-- HTML --> text <br> text <br /> &nbsp;  \t\r\n  ', // misc contents

            // other cases
            '<!-- HTML5 -->', // possible versioning for future extensions
            '<!-- HTML-SML -->' // another variant for future extensions
        ));
    }

    /**
     * Identify mixed HTML + Stud.IP markup content.
     *
     * Identification of plain Stud.IP markup contents is
     * not tested here. Only identification of older HTML
     * contents that where allowed to contain Stud.IP
     * markup is tested.
     *
     * @param $studipHtml string  Text containing HTML and
     *                            maybe also Stud.IP markup,
     *                            as defined by older Stud.IP
     *                            versions.
     *
     * @dataProvider studipHtmlProvider
     */
    public function testIsStudipHtml($studipHtml)
    {
        $this->mockConfig();
        Config::get()->WYSIWYG = true;
        Config::get()->WYSIWYG_HTML_HEURISTIC_FALLBACK = true;
        $this->assertTrue(StudIp\Markup::isHtml($studipHtml));
        $this->assertTrue(StudIp\Markup::isHtmlFallback($studipHtml));
    }

    /**
     * Identify plain HTML contents when fallback allows
     * older, mixed HTML+Stud.IP markup contents.
     *
     * @param $html string Text containing plain HTML.
     *
     * @dataProvider newHtmlProvider
     */
    public function testIsHtmlButNotStudipHtml($html)
    {
        $this->mockConfig();
        Config::get()->WYSIWYG = true;
        Config::get()->WYSIWYG_HTML_HEURISTIC_FALLBACK = true;
        $this->assertTrue(StudIp\Markup::isHtml($html));
        $this->assertFalse(StudIp\Markup::isHtmlFallback($html));
    }

    /**
     * Provide contents that contain HTML with optional
     * Stud.IP markup as created by WYSIWYG implementation
     * in Stud.IP 3.0, 3.1 and 3.2.
     */
    public function studipHtmlProvider()
    {
        return array_merge(
            $this->studipHtmlHeuristicProvider(),
            $this->studipHtmlMarkerProvider()
        );
    }

    /**
     * Provide contents that contain HTML with optional
     * Stud.IP markup as created by WYSIWYG implementation
     * in Stud.IP 3.0 & 3.1.
     *
     * Heuristic: Text containing HTML and optional Stud.IP
     * markup starts with < and ends with >.
     */
    public function studipHtmlHeuristicProvider()
    {
        return array_map(function ($e) { return array($e); }, array(
            '<>', // not valid HTML, yet still detected previously
            '<div></div>',
            '<div></div><p></p>',
            "<div>\r\n</div>\r\n<p>\r\n</p>",
            " \t\r\n \t\r\n<div> \t\r\n<br> \t\r\n</div> \t\r\n \t\r\n",

            // second heuristic: text starts with HTML marker
            self::STUDIP_3_2_HTML_MARKER
        ));
    }

    /**
     * Provide contents that contain HTML with optional
     * Stud.IP markup as created by WYSIWYG implementation
     * in Stud.IP 3.2.
     *
     * Heuristic: Text containing HTML and optional Stud.IP
     * markup starts with
     *   '<!-- HTML: Insert text after this line only. -->'.
     */
    public function studipHtmlMarkerProvider()
    {
        return array_map(function ($e) { return array($e); }, array(
            '<!-- HTML: Insert text after this line only. -->' // old marker
        ));
    }

    /**
     * Test if plain text content is identified by Markup::isHtml().
     *
     * @param $text string  Plain text as defined by current
     *                      Stud.IP without fallback-solution.
     *
     * @dataProvider isNotHtmlProvider
     */
    public function testIsNotHtml($text)
    {
        $this->mockConfig();
        Config::get()->WYSIWYG = true;
        Config::get()->WYSIWYG_HTML_HEURISTIC_FALLBACK = false;
        $this->assertFalse(StudIp\Markup::isHtml($text));
    }

    /**
     * Data provider for testIsNotHtml().
     */
    public function isNotHtmlProvider()
    {
        return array_map(function ($e) { return array($e); }, array(
            // content must start with marker
            '<p><!--HTML--></p>',
            'text <!--HTML-->',

            // only whitespace allowed before "HTML"
            '<!-- XHTML -->', // letters
            '<!-- 5 HTML -->', // numbers
            '<!-- ! HTML -->', // punctuation

            // other invalid cases
            '<!---->',
            '<!-- -->',
            '<!--H--><!--T--><!--M--><!--L-->',
            '<!-- H --><!-- T --><!-- M --><!-- L -->',
            '<!-- This is HTML!!! -->'
        ));
    }

    public function testRemoveHTML()
    {
        forEach (array(
            'plain text' => 'plain text',
            '<p>paragraph only</p>' => 'paragraph only',

            '<a>no href</a>' => 'no href',
            '<a href=""></a>' => '',
            '<a href="">empty href</a>' => 'empty href',
            '<a href="href only" />' => '[ href%20only ]',
            '<a href="href end-tag"></a>' => '[ href%20end-tag ]',
            '<a href="http://href.de">and text</a>' => '[ http://href.de ]and text',
            'before <a href="http://href.de">and text</a> after'
            => 'before [ http://href.de ]and text after',

            '<img>no src</img>' => 'no src',
            '<img src="src only" />' => '[ src%20only ]',
            '<img src="src end-tag"></img>' => '[ src%20end-tag ]',
            '<img src="http://src.de">and text</a>' => '[ http://src.de ]and text',
            'before <img src="http://src.de">and text</img> after'
            => 'before [ http://src.de ]and text after',

            // some "real" urls
            '<a href="https://example.org/">Example'
            => '[ https://example.org/ ]Example',
            '<img src="https://example.org/image.png">'
            => '[ https://example.org/image.png ]',
            '<p>link <a href="http://example.org">Example-Domain</a> and picture <img src="https://example.org/image.png"></p>'
            => 'link [ http://example.org ]Example-Domain and picture [ https://example.org/image.png ]'
        ) as $in => $out) {
            $this->assertEquals($out, StudIp\Markup::removeHtml($in));
        }
    }

    public function testGetMediaUrl()
    {
        $this->mockConfig();

        # exceptions
        $namespace = 'Studip\MarkupPrivate\MediaProxy\\';
        $invalidInternalLink = $namespace . 'InvalidInternalLinkException';
        $externalMediaDenied = $namespace . 'ExternalMediaDeniedException';

        # URLs
        $sendfile = 'sendfile.php?type=0&file_id=9eea7ca20cba01dd4ea394b3b53027cc&file_name=image.png';
        $wiki = 'wiki.php?cid=a07535cf2f8a72df33c12ddfa4b53dde&view=show';
        $wikipediaLogo = 'http://upload.wikimedia.org/wikipedia/meta/0/08/Wikipedia-logo-v2_1x.png';
        $proxy = 'dispatch.php/media_proxy?url=';
        $proxiedWikipediaLogo = $proxy . 'http%3A%2F%2Fupload.wikimedia.org%2Fwikipedia%2Fmeta%2F0%2F08%2FWikipedia-logo-v2_1x.png';

        # domains
        $domains = array(
            'org' => 'example.org/studip',
            'home' => 'example.org/~home',
            'net' => 'example.net/studip',
        );

        $getUrl = function ($domainKey, $path) use (&$domains) {
            return 'http://' . $domains[$domainKey] . '/' . $path;
        };

        # run various tests
        $index = 0;
        foreach (array(
            array(
                'in' => $getUrl('org', 'image.jpg'),
                'exception' => $invalidInternalLink,
                'uri' => $getUrl('org', 'index.php'),
                'domains' => $domains,
                'externalMedia' => 'allow'
            ),
            array(
                'in' => $getUrl('org', $sendfile),
                'out' => $getUrl('org', $sendfile),
                'uri' => $getUrl('org', 'index.php'),
                'domains' => $domains,
                'externalMedia' => 'allow'
            ),
            array(
                'in' => $getUrl('org', $sendfile),
                'out' => $getUrl('home', $sendfile),
                'uri' => $getUrl('home', $wiki),
                'domains' => $domains,
                'externalMedia' => 'allow'
            ),
            array(
                'in' => $getUrl('org', $sendfile),
                'out' => $getUrl('net', $sendfile),
                'uri' => $getUrl('net', $wiki),
                'domains' => $domains,
                'externalMedia' => 'allow'
            ),
            array(
                'in' => $wikipediaLogo,
                'out' => $wikipediaLogo,
                'uri' => $getUrl('org', $wiki),
                'domains' => $domains,
                'externalMedia' => 'allow'
            ),
            array(
                'in' => $wikipediaLogo,
                'exception' => $externalMediaDenied,
                'uri' => $getUrl('org', $wiki),
                'domains' => $domains,
                'externalMedia' => 'deny'
            ),
            array(
                'in' => $wikipediaLogo,
                'out' => $getUrl('org', $proxiedWikipediaLogo),
                'uri' => $getUrl('org', $wiki),
                'domains' => $domains,
                'externalMedia' => 'proxy'
            ),
        ) as $test) {
            $index++;

            # fake Stud.IP web server set-up
            fakeServer($test['uri'], $test['domains']);
            Config::get()->LOAD_EXTERNAL_MEDIA = $test['externalMedia'];
            //echoWebGlobals(); // call to help with debugging

            # test getMediaUrl
            try {
                $out = Studip\MarkupPrivate\MediaProxy\getMediaUrl($test['in']);

                if (isset($test['exception'])) {
                    $this->fail(
                        'Test ' . $index . ' did not raise '
                        . $test['exception'] . '. Output: ' . $out . '.'
                    );
                }
            } catch (PHPUnit_Framework_Error_Notice $e) {
                throw $e;
            } catch (Exception $e) {
                if ( !isset($test['exception'])) {
                    $this->fail(
                        'Test ' . $index . ' raised ' . get_class($e) . '.'
                    );
                }
                if (get_class($e) !== $test['exception']) {
                    $this->fail(
                        'Test ' . $index . ' raised ' . get_class($e)
                        . ' instead of ' . $test['exception'] . '.'
                    );
                }
            }
            if (isset($test['out'])) {
                $this->assertEquals($test['out'], $out, 'Test ' . $index);
            }
        }
    }

    //// helpers

    /**
     * Mocks the Config class, so we don't need a database to
     * run tests.
     */
    private function mockConfig() {
        $configStub = $this->getMockBuilder('Config')
        ->disableOriginalConstructor()
        ->getMock();

        $properties = array();

        $configStub->expects($this->any())
        ->method('__get')
        ->will($this->returnCallback(function ($property) use (&$properties) {
            return $properties[$property];
        }));

        $configStub->expects($this->any())
        ->method('__set')
        ->will($this->returnCallback(function ($property, $value) use (&$properties) {
            $properties[$property] = $value;
            return $properties[$property];
        }));

        Config::set($configStub);
    }
}
