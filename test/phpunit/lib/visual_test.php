<?php

/*
 * Copyright (C) 2010 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__FILE__) . '/../bootstrap.php';
require_once 'lib/visual.inc.php';
require_once 'lib/classes/Config.class.php';

class VisualFunctionsTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        static $config = array(
            'LOAD_EXTERNAL_MEDIA' => 'allow'
        );

        Config::set(new Config($config));
    }

    public function testFormatReady()
    {
        $expected = '<b>some code</b>';
        $this->assertEquals($expected, formatReady('*some*code*'));
    }

    public function testHtmlReady()
    {
        $pairs = array(
          'abc'    => 'abc',
          'äöü'    => '&auml;&ouml;&uuml;',
          '<'      => '&lt;',
          '"'      => '&quot;',
          "'"      => '&#039;',
          '&amp;'  => '&amp;amp;',
          '&#039;' => '&#039;',
          ''       => '',
          NULL     => NULL
        );

        foreach ($pairs as $string => $expected) {
          $this->assertEquals($expected, htmlReady($string));
        }
    }

    public function testFormatReadyTicket1255()
    {
        $this->assertEquals("!<br>Hallo Welt", formatReady("!\nHallo Welt"));
    }

    public function testLine()
    {
        $input = "Test\n--\nTest";
        $expected = 'Test<br><hr class="content"><br>Test';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testHeading()
    {
        $input = '!!%%Überschrift%%';
        $expected = '<h3 class="content"><i>&Uuml;berschrift</i></h3>';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testBoldItalics()
    {
        $input = '**some %%code%%**';
        $expected = '<b>some <i>code</i></b>';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testBigSmall()
    {
        $input = '++some --code--++';
        $expected = '<big>some <small>code</small></big>';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testSimpleBoldItalics()
    {
        $input = '*bold*text* %some%italics%';
        $expected = '<b>bold text</b> <i>some italics</i>';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testMissingClose()
    {
        $input = '**missing %%close';
        $expected = $input;
        $this->assertEquals($expected, formatReady($input));
    }

    public function testCloseBeforeOpen()
    {
        $input = 'there is -}no markup{- here';
        $expected = $input;
        $this->assertEquals($expected, formatReady($input));
    }

    public function testIncorrectNesting()
    {
        $input = '** test %% test ** test %%';
        $expected = '** test <i> test ** test </i>';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testImage()
    {
        $input = '[img=Stud.IP-Logo]http://www.studip.de/logo.png';
        $expected = '<img src="http://www.studip.de/logo.png" style="" title="Stud.IP-Logo" alt="Stud.IP-Logo">';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testTable()
    {
        $input = "|Name|Matrikelnummer|Studiengang|\n|Max Mustermann|55555|Mathe Diplom|\n";
        $expected = '<table class="content">'
                   .'<tr>'
                   .'<td>Name</td>'
                   .'<td>Matrikelnummer</td>'
                   .'<td>Studiengang</td>'
                   .'</tr>'
                   .'<tr>'
                   .'<td>Max Mustermann</td>'
                   .'<td>55555</td>'
                   .'<td>Mathe Diplom</td>'
                   .'</tr>'
                   .'</table>';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testList()
    {
        $input = "- Einführung\n- Hauptteil\n-= Argument 1\n-= Argument 2\n- Schluss\n";
        $expected = '<ul>'
                   .'<li>Einf&uuml;hrung</li>'
                   .'<li>Hauptteil<ol>'
                   .'<li>Argument 1</li>'
                   .'<li>Argument 2</li>'
                   .'</ol></li>'
                   .'<li>Schluss</li>'
                   .'</ul>';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testIndent()
    {
        $input = "  Ebene 1\n    Ebene 2\n    Ebene 2\n  Ebene 1\n";
        $expected = '<div class="indent">'
                   .'Ebene 1<br>'
                   .'<div class="indent">'
                   .'Ebene 2<br>'
                   .'Ebene 2<br>'
                   .'</div>'
                   .'Ebene 1<br>'
                   .'</div>';
        $this->assertEquals($expected, formatReady($input, false));
    }

    public function testNop()
    {
        $input = '[nop]**A**[quote]B[/quote]{-C-}[/nop]';
        $expected = '**A**[quote]B[/quote]{-C-}';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testPre()
    {
        $input = '[pre]**A**{-C-}[/pre]';
        $expected = '<pre><b>A</b><strike>C</strike></pre>';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testQuote()
    {
        $input = '[quote=__Anonymous__]some text[/quote]';
        $expected = '<blockquote class="quote">'
                   .'<b><u>Anonymous</u> hat geschrieben:</b><hr>some text'
                   .'</blockquote>';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testLink()
    {
        $input = '[Testlink]https://www.studip.de/';
        $expected = '<a class="link-extern" href="https://www.studip.de/" target="_blank">Testlink</a>';
        $this->assertEquals($expected, formatReady($input));
    }

    public function testMail()
    {
        $input = '[Mail]some.user+tag@example.com';
        $expected = '<a class="link-extern" href="mailto:some.user+tag@example.com">Mail</a>';
        $this->assertEquals($expected, formatReady($input));
    }
}
