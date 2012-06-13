<?php
/*
 * studip_format_test.php - unit tests for the StudipFormat class
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

require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once 'lib/classes/StudipFormat.php';

function markupBold($markup, $matches, $contents)
{
    return '<b>' . $contents . '</b>';
}

class StudipFormatTest extends PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->old_rules = StudipFormat::getStudipMarkups();
    }

    function tearDown()
    {
        foreach(StudipFormat::getStudipMarkups() as $key => $value) {
            StudipFormat::removeStudipMarkup($key);
        }

        foreach($this->old_rules as $key => $value) {
            StudipFormat::addStudipMarkup($key, @$value['start'], @$value['end'], @$value['callback']);
        }
    }

    public function testAddStudipMarkup()
    {
        StudipFormat::addStudipMarkup('bb-bold', '\{b\}', '\{\/b\}', 'markupBold');
        $markup = new StudipFormat();

        $input = '{b}some %%code%%{/b}';
        $expected = '<b>some <i>code</i></b>';
        $this->assertEquals($expected, $markup->format($input));
    }

    public function testRemoveStudipMarkup()
    {
        StudipFormat::removeStudipMarkup('bold');
        $markup = new StudipFormat();

        $input = '**some %%code%%**';
        $expected = '**some <i>code</i>**';
        $this->assertEquals($expected, $markup->format($input));
    }
}
