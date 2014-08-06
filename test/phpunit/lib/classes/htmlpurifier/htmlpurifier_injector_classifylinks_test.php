<?php
/**
 * htmlpurifier_injector_classifylinks_test.php - Unit tests for the HTMLPurifier_Injector_ClassifyLinks class.
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
require_once dirname(__FILE__) . '/../../../bootstrap.php';
require_once 'test/phpunit/fakeserver.php';

require_once 'vendor/HTMLPurifier/HTMLPurifier.auto.php';

# needed by visual.inc.php, which is required by link classifier
require_once 'lib/classes/DbView.class.php';
require_once 'lib/classes/TreeAbstract.class.php';

require_once 'lib/classes/htmlpurifier/HTMLPurifier_Injector_ClassifyLinks.php';

/**
 * Test case for HTMLPurifier_Injector_ClassifyLinks.
 */
class HTMLPurifier_Injector_ClassifyLinksTest extends PHPUnit_Framework_TestCase
{
    private static $originalErrorReporting;

    public static function setUpBeforeClass()
    {
        self::$originalErrorReporting = error_reporting();
        // check for all errors in this test
        // E_STRICT was not part of E_ALL in PHP < 5.4.0
        error_reporting(E_ALL | E_STRICT);
    }

    public static function tearDownAfterClass()
    {
        error_reporting(self::$originalErrorReporting);
    }

    public function testClassifyLinks()
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.RemoveInvalidImg', true);
        $config->set('Attr.AllowedFrameTargets', array('_blank'));
        $config->set('Attr.AllowedRel', array('nofollow'));
        $config->set('AutoFormat.Custom', array('ClassifyLinks'));

        $purifier = new \HTMLPurifier($config);

        # domains
        $domains = array(
            'org' => 'example.org:80/studip',
            'home' => 'example.org:80/~home',
            'net' => 'example.net:80/studip',
        );

        $getUrl = function ($domainKey, $path) use (&$domains) {
            return 'http://' . $domains[$domainKey] . '/' . $path;
        };

        # fake web server
        fakeServer($getUrl('org', 'index.php'), $domains);

        # run tests
        $in = '<a href="http://www.google.de">Google</a>';
        $out = '<a href="http://www.google.de" class="link-extern">Google</a>';

        $this->assertEquals($out, $purifier->purify($in));
    }
}
