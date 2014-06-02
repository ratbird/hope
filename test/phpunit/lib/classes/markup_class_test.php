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
require_once 'lib/classes/Markup.class.php';

class StudipFormatTest extends PHPUnit_Framework_TestCase
{
    public function testRemoveHTML()
    {
        forEach (array(
            'plain text' => 'plain text',
            '<p>paragraph only</p>' => 'paragraph only',

            '<a>no href</a>' => 'no href',
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
}
