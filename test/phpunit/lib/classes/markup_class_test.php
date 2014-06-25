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

# common set-up, usually done by lib/bootstraph.php and
# config/config_local.inc.php when run on web server
$STUDIP_BASE_PATH = realpath(dirname(__FILE__) . '/../../../..');
$ABSOLUTE_PATH_STUDIP = $STUDIP_BASE_PATH . '/public/';

# needed by visual.inc.php
require_once 'lib/classes/DbView.class.php';
require_once 'lib/classes/TreeAbstract.class.php';

# needed by Markup.class.php
require_once 'lib/visual.inc.php';
require_once 'lib/classes/Config.class.php';

# class and functions that are tested by this script
require_once 'lib/classes/Markup.class.php';

# helper functions

function fakeServer($uri) {
    $urlComponents = parse_url($uri);

    if (isset($urlComponents['host'])) {
        $_SERVER['SERVER_NAME'] = $urlComponents['host'];
        $_SERVER['HTTP_HOST'] = $urlComponents['host'];
    }

    $_SERVER['HTTPS'] = false;
    $_SERVER['SERVER_PORT'] = 80;
    if (isset($urlComponents['scheme'])
        && strtolower($urlComponents['scheme']) == 'https'
    ) {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = 443;
    }

    if (isset($urlComponents['port'])) {
        $_SERVER['SERVER_PORT'] = $urlComponents['port'];
    }

    $path = '';
    if (isset($urlComponents['path'])) {
        $_SERVER['PHP_SELF'] = $urlComponents['path'];
        $path = $urlComponents['path'];
    }

    $query = isset($urlComponents['query']) ? ('?' . $urlComponents['query']) : '';
    $fragment = isset($urlComponents['fragment']) ? ('#' . $urlComponents['fragment']) : '';
    $_SERVER['REQUEST_URI'] = $path . $query . $fragment;

    if (!isset($GLOBALS['CONVERT_IDNA_URL'])) {
        $GLOBALS['CONVERT_IDNA_URL'] = false;
    }
}

function computeRelativePath() {
    // computes the value of $CANONICAL_RELATIVE_PATH_STUDIP
    global $_SERVER, $CANONICAL_RELATIVE_PATH_STUDIP;

    // code copied from config/config_local.inc.php
    $CANONICAL_RELATIVE_PATH_STUDIP = dirname($_SERVER['PHP_SELF']);
    if (DIRECTORY_SEPARATOR != '/') {
        $CANONICAL_RELATIVE_PATH_STUDIP = str_replace(
            DIRECTORY_SEPARATOR, '/', $CANONICAL_RELATIVE_PATH_STUDIP
        );
    }

    if (substr($CANONICAL_RELATIVE_PATH_STUDIP, -1) != '/') {
        $CANONICAL_RELATIVE_PATH_STUDIP .= '/';
    }
}

function computeAbsoluteURI() {
    // computes the value of $ABSOLUTE_URI_STUDIP
    global $_SERVER, $ABSOLUTE_URI_STUDIP, $CANONICAL_RELATIVE_PATH_STUDIP;

    // code copied from config/config_local.inc.php
    if (isset($_SERVER['SERVER_NAME'])) {
        // work around possible bug in lighttpd
        if (strpos($_SERVER['SERVER_NAME'], ':') !== false) {
            list($_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']) =
                explode(':', $_SERVER['SERVER_NAME']);
        }

        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
        $ABSOLUTE_URI_STUDIP = $https ? 'https' : 'http';
        $ABSOLUTE_URI_STUDIP .= '://'.$_SERVER['SERVER_NAME'];

        if ($https && $_SERVER['SERVER_PORT'] != 443 ||
            !$https && $_SERVER['SERVER_PORT'] != 80
        ) {
            $ABSOLUTE_URI_STUDIP .= ':'.$_SERVER['SERVER_PORT'];
        }

        $ABSOLUTE_URI_STUDIP .= $CANONICAL_RELATIVE_PATH_STUDIP;
    }
}

function echoWebGlobals()
{
    echo PHP_EOL . "base path\t" . $GLOBALS['STUDIP_BASE_PATH'];
    echo PHP_EOL . "PHP self\t" . $_SERVER['PHP_SELF'];
    echo PHP_EOL . "relative path\t" . $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP']; 
    echo PHP_EOL . "server name\t" . $_SERVER['SERVER_NAME'];
    echo PHP_EOL . "server port\t" . $_SERVER['SERVER_PORT'];
    echo PHP_EOL . "HTTPS\t\t" . $_SERVER['HTTPS'];
    echo PHP_EOL . "absolute URI\t" . $GLOBALS['ABSOLUTE_URI_STUDIP'];
}

# the actual test

class MarkupTest extends PHPUnit_Framework_TestCase
{
    private static $originalErrorReporting;

    public static function setUpBeforeClass()
    {
        MarkupTest::$originalErrorReporting = error_reporting();
        // check for all errors in this test
        // E_STRICT was not part of E_ALL in PHP < 5.4.0
        error_reporting(E_ALL | E_STRICT);
    }

    public static function tearDownAfterClass()
    {
        error_reporting(MarkupTest::$originalErrorReporting);
    }

    public function testRemoveHTML()
    {
        forEach (array(
            'plain text' => 'plain text',
            '<p>paragraph only</p>' => 'paragraph only',

            '<a>no href</a>' => 'no href',
            '<a href=""></a>' => '',
            '<a href="">empty href</a>' => 'empty href',
            '<a href="href only" />' => '[href%20only]',
            '<a href="href end-tag"></a>' => '[href%20end-tag]',
            '<a href="http://href.de">and text</a>' => '[http://href.de]and text',
            'before <a href="http://href.de">and text</a> after'
            => 'before [http://href.de]and text after',

            '<img>no src</img>' => 'no src',
            '<img src="src only" />' => '[src%20only]',
            '<img src="src end-tag"></img>' => '[src%20end-tag]',
            '<img src="http://src.de">and text</a>' => '[http://src.de]and text',
            'before <img src="http://src.de">and text</img> after'
            => 'before [http://src.de]and text after',

            // some "real" urls
            '<a href="https://example.org/">Example'
            => '[https://example.org/]Example',
            '<img src="https://example.org/image.png">'
            => '[https://example.org/image.png]',
            '<p>link <a href="http://example.org">Example-Domain</a> and picture <img src="https://example.org/image.png"></p>'
            => 'link [http://example.org]Example-Domain and picture [https://example.org/image.png]'
        ) as $in => $out) {
            $this->assertEquals($out, StudIp\Markup::removeHtml($in));
        }
    }

    public function testGetMediaUrl()
    {
        global $_SERVER, $STUDIP_DOMAINS, $STUDIP_BASE_PATH;

        # mock class Config
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

        # exceptions
        $namespace = 'Studip\MarkupPrivate\MediaProxy\\';
        $invalidInternalLink = $namespace . 'InvalidInternalLinkException';
        $externalMediaDenied = $namespace . 'ExternalMediaDeniedException';

        # URLs
        $sendfile = 'sendfile.php?type=0&file_id=9eea7ca20cba01dd4ea394b3b53027cc&file_name=image.png';
        $wiki = 'wiki.php?cid=a07535cf2f8a72df33c12ddfa4b53dde&view=show';
        $wikipediaLogo = 'http://upload.wikimedia.org/wikipedia/meta/0/08/Wikipedia-logo-v2_1x.png';

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
/*            array(
                'in' => $wikipediaLogo,
                'out' => $wikipediaLogo,
                'uri' => $getUrl('org', $wiki),
                'domains' => $domains,
                'externalMedia' => 'proxy'
            ),*/
        ) as $test) {
            $index++;

            # fake Stud.IP web server set-up
            fakeServer($test['uri']);
            $STUDIP_DOMAINS = $test['domains'];
            unset($GLOBALS['TransformInternalLinks_domainData']);
            computeRelativePath();
            computeAbsoluteURI();
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
}
