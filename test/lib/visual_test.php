<?php

/*
 * Copyright (C) 2010 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/visual.inc.php';
require_once 'lib/classes/Assets.class.php';
require_once 'lib/classes/Config.class.php';

class VisualFunctionsTest extends UnitTestCase
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
        $this->assertEqual(formatReady('*some*code*'), $expected);
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
          $this->assertEqual(htmlReady($string), $expected);
        }
    }

    public function testFormatReadyTicket1255()
    {
        $this->assertEqual(formatReady("!\nHallo Welt"), "!<br>Hallo Welt");
    }

    public function testLine()
    {
        $input = "Test\n--\nTest";
        $expected = 'Test<br><hr noshade="noshade" width="98%" size="1" align="center"><br>Test';
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testHeading()
    {
        $input = '!!%%Überschrift%%';
        $expected = '<h3 class="content"><i>&Uuml;berschrift</i></h3>';
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testBoldItalics()
    {
        $input = '**some %%code%%**';
        $expected = '<b>some <i>code</i></b>';
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testBigSmall()
    {
        $input = '++some --code--++';
        $expected = '<big>some <small>code</small></big>';
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testSimpleBoldItalics()
    {
        $input = '*bold*text* %some%italics%';
        $expected = '<b>bold text</b> <i>some italics</i>';
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testMissingClose()
    {
        $input = '**missing %%close';
        $expected = $input;
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testCloseBeforeOpen()
    {
        $input = 'there is -}no markup{- here';
        $expected = $input;
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testIncorrectNesting()
    {
        $input = '** test %% test ** test %%';
        $expected = '** test <i> test ** test </i>';
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testImage()
    {
        $input = '[img=Stud.IP-Logo]http://www.studip.de/logo.png';
        $expected = '<img src="http://www.studip.de/logo.png"  alt="Stud.IP-Logo" title="Stud.IP-Logo">';
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testTable()
    {
        $input = "|Name|Matrikelnummer|Studiengang|\n|Max Mustermann|55555|Mathe Diplom|\n";
        $expected = '<table style="border-collapse: collapse" cellpadding=3>'
                   .'<tr>'
                   .'<td style="border: thin solid #666666"><font size=-1>Name</font></td>'
                   .'<td style="border: thin solid #666666"><font size=-1>Matrikelnummer</font></td>'
                   .'<td style="border: thin solid #666666"><font size=-1>Studiengang</font></td>'
                   .'</tr>'
                   .'<tr>'
                   .'<td style="border: thin solid #666666"><font size=-1>Max Mustermann</font></td>'
                   .'<td style="border: thin solid #666666"><font size=-1>55555</font></td>'
                   .'<td style="border: thin solid #666666"><font size=-1>Mathe Diplom</font></td>'
                   .'</tr>'
                   .'</table>';
        $this->assertEqual(formatReady($input), $expected);
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
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testIndent()
    {
        $input = "\n\n  Ebene 1\n\n  Ebene 2\nEbene 2";
        $expected = '<blockquote>'
                   .'Ebene 1'
                   .'<blockquote>'
                   .'Ebene 2<br>'
                   .'Ebene 2'
                   .'</blockquote>'
                   .'</blockquote>';
        $this->assertEqual(formatReady($input, false), $expected);
    }

    public function testNop()
    {
        $input = '[nop]**A**[quote]B[/quote]{-C-}[/nop]';
        $expected = '**A**[quote]B[/quote]{-C-}';
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testPre()
    {
        $input = '[pre]**A**{-C-}[/pre]';
        $expected = '<pre><b>A</b><strike>C</strike></pre>';
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testQuote()
    {
        $input = '[quote=__Anonymous__]some text[/quote]';
        $expected = '<blockquote class="quote">'
                   .'<b><u>Anonymous</u> hat geschrieben:</b><hr>some text'
                   .'</blockquote>';
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testLink()
    {
        $input = '[Testlink]https://www.studip.de/';
        $link_extern = Assets::img('icons/16/blue/link-extern.png',
            array('class' => 'text-top', 'title' =>_('externer Link')));
        $expected = '<a href="https://www.studip.de/" target="_blank">' . $link_extern . ' Testlink</a>';
        $this->assertEqual(formatReady($input), $expected);
    }

    public function testMail()
    {
        $input = '[Mail]some.user@example.com';
        $link_extern = Assets::img('icons/16/blue/link-extern.png',
            array('class' => 'text-top', 'title' =>_('externer Link')));
        $expected = '<a href="mailto:some.user@example.com">' . $link_extern . ' Mail</a>';
        $this->assertEqual(formatReady($input), $expected);
    }
}

