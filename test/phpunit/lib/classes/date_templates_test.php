<?php

/**
 * date_templates_text.php - Test for date-templates
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Gl�ggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once 'vendor/flexi/lib/flexi.php';
require_once 'lib/functions.php';

class DateTemplatesTests extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->testData = Array (
            'regular' => Array (
                'turnus_data' => Array (
                    '0' => Array (
                        'metadate_id' => '0',
                        'cycle' => '0',
                        'start_hour' => '10',
                        'start_minute' => '00',
                        'end_hour' => '12',
                        'end_minute' => '00',
                        'day' => '1',
                        'desc' => 'Vorlesung',
                        'assigned_rooms' => Array (
                            '1' => '2'
                        ),
                        'freetext_rooms' => Array (
                            '<script>alert("b�se");</script>' => '16',
                        ),

                        'tostring' => 'Montag: 10:00 - 12:00',
                        'tostring_short' => 'Mo. 10:00 - 12:00',
                        'first_date' => Array (
                            'date' => '1287388800',
                            'end_time' => '1287396000',
                            'date_typ' => '1',
                            'raum' => '<script>alert("b�se");</script>',
                        )
                    )
                )
            ),

            'irregular' => Array (
                '0' => Array (
                    'date_typ' => '3',
                    'start_time' => '1273647600',
                    'end_time' => '1273662000',
                    'raum' => '<script>alert("b�se");</script>',
                    'typ' => '1',
                    'tostring' => 'Mi., 12.05.2010, 09:00 - 13:00',
                )
            )
        );
        
        date_default_timezone_set(@date_default_timezone_get());
        setlocale(LC_TIME, "C");

    }


    public function testExportTemplates()
    {
        $data = renderTemplate('dates/seminar_export', $this->testData, array('show_room' => true));
        $compare = 'Mo. 10:00 - 12:00 (w�chentlich) - Vorlesung, Ort: H�rsaal 1 <br>, (<script>alert("b�se");</script>), '
                 . "\n" . 'Termine am 12.05. 09:00 - 13:00, Ort: (<script>alert("b�se");</script>)';
        $this->assertEquals($compare, $data);


        $data = renderTemplate('dates/seminar_export_location', $this->testData);
        $compare = 'H�rsaal 1 <br>: Mo. 10:00 - 12:00 (2x), ' . "\n"
                 . '(<script>alert("b�se");</script>): Mo. 10:00 - 12:00 (16x)' . "\n"
                 . ' 12.05. 09:00 - 13:00';
        $this->assertEquals($compare, $data);

        $data = renderTemplate('dates/date_export', $this->testData, array('date' => new SingleDate()));
        $compare = 'Mo., 11.11.2010 12:00 - 14:00, Ort: H�rsaal 1 <br>';
        $this->assertEquals($compare, $data);

        // test single date with freetext
        $singledate = new SingleDate();
        $singledate->resource_id = NULL;
        $data = renderTemplate('dates/date_export', $this->testData, array('date' => $singledate));
        $compare = 'Mo., 11.11.2010 12:00 - 14:00, Ort: (<script>alert("b�se");</script>)';
        $this->assertEquals($compare, $data);
    }


    public function testHTMLTemplatesWithLink()
    {
        $this->markTestSkipped('This test is currently broken.'); // TODO: Fix it
        
        $data = renderTemplate('dates/seminar_html', $this->testData, array('show_room' => true));
        $compare = 'Montag: 10:00 - 12:00 (ab 10/18/10), <i>Vorlesung</i>, Ort: '
                 . '<a onclick="window.open(...)">H�rsaal 1</a>, '
                 . '(&lt;script&gt;alert(&quot;b�se&quot;);&lt;/script&gt;)<br>'
                 . 'Termine am 12.05. 09:00 - 13:00, Ort: (&lt;script&gt;alert(&quot;b�se&quot;);&lt;/script&gt;)';
        $this->assertEquals($compare, $data);


        $data = renderTemplate('dates/seminar_html_location', $this->testData);
        $compare = '<table class="default" style="width: auto;">
        <tr>
        <td style="vertical-align: top; padding: 0 10px 0 0;"><a onclick="window.open(...)">H�rsaal 1</a></td>
        <td style="padding: 0px;">Mo. 10:00 - 12:00 (2x)</td>
    </tr>
        <tr>
        <td style="vertical-align: top; padding: 0 10px 0 0;">(&lt;script&gt;alert(&quot;b�se&quot;);&lt;/script&gt;)</td>
        <td style="padding: 0px;">Mo. 10:00 - 12:00 (16x)<br> 12.05. 09:00 - 13:00</td>
    </tr>
    </table>';
        $this->assertEquals($compare, $data);


        $data = renderTemplate('dates/seminar_predominant_html', $this->testData, array('cycle_id' => '0'));
        $compare = '<a onclick="window.open(...)">H�rsaal 1</a>';
        $this->assertEquals($compare, $data);


        $data = renderTemplate('dates/date_html', $this->testData, array('date' => new SingleDate()));
        $compare = 'Mo., 11.11.2010 12:00 - 14:00, Ort: <a onclick="window.open(...)">H�rsaal 1</a>';
        $this->assertEquals($compare, $data);
    }


    public function testHTMLTemplatesWithoutLink()
    {
        $this->markTestSkipped('This test is currently broken.'); // TODO: Fix it

        $data = renderTemplate('dates/seminar_html', $this->testData, array('link' => false, 'show_room' => true));
        $compare = 'Montag: 10:00 - 12:00 (ab 10/18/10), <i>Vorlesung</i>, Ort: H�rsaal 1 &lt;br&gt;, '
                 . '(&lt;script&gt;alert(&quot;b�se&quot;);&lt;/script&gt;)<br>'
                 . 'Termine am 12.05. 09:00 - 13:00, Ort: (&lt;script&gt;alert(&quot;b�se&quot;);&lt;/script&gt;)';
        $this->assertEquals($compare, $data);


        $data = renderTemplate('dates/seminar_html_location', $this->testData, array('link' => false));
        $compare = '<table class="default" style="width: auto;">
        <tr>
        <td style="vertical-align: top; padding: 0 10px 0 0;">H�rsaal 1 &lt;br&gt;</td>
        <td style="padding: 0px;">Mo. 10:00 - 12:00 (2x)</td>
    </tr>
        <tr>
        <td style="vertical-align: top; padding: 0 10px 0 0;">(&lt;script&gt;alert(&quot;b�se&quot;);&lt;/script&gt;)</td>
        <td style="padding: 0px;">Mo. 10:00 - 12:00 (16x)<br> 12.05. 09:00 - 13:00</td>
    </tr>
    </table>';
        $this->assertEquals($compare, $data);


        $data = renderTemplate('dates/seminar_predominant_html', $this->testData, array(
            'cycle_id' => '0', 'link' => false));
        $compare = 'H�rsaal 1 &lt;br&gt;';
        $this->assertEquals($compare, $data);


        $data = renderTemplate('dates/date_html', $this->testData, array('date' => new SingleDate(), 'link' => false));
        $compare = 'Mo., 11.11.2010 12:00 - 14:00, Ort: H�rsaal 1 &lt;br&gt;';
        $this->assertEquals($compare, $data);

        // test single date with freetext
        $singledate = new SingleDate();
        $singledate->resource_id = NULL;
        $data = renderTemplate('dates/date_html', $this->testData, array('date' => $singledate));
        $compare = 'Mo., 11.11.2010 12:00 - 14:00, Ort: (&lt;script&gt;alert(&quot;b�se&quot;);&lt;/script&gt;)';
        $this->assertEquals($compare, $data);
    }


    public function testXMLTemplates()
    {
        $data = renderTemplate('dates/seminar_xml', $this->testData);
        $compare = '<raumzeit>
    <startwoche>0</startwoche>
    <datum>w�chentlich</datum>
    <wochentag>Mo</wochentag>
    <zeit>10:00-12:00</zeit>
    <raum>
        <gebucht>H�rsaal 1 &lt;br&gt;</gebucht>
        <freitext>&lt;script&gt;alert(&quot;b�se&quot;);&lt;/script&gt;</freitext>
    </raum>
</raumzeit>
<raumzeit>
    <datum>12.05.2010</datum>
    <wochentag>Mi</wochentag>
    <zeit>09:00-13:00</zeit>
    <raum>
        <gebucht></gebucht>
        <freitext>&lt;script&gt;alert(&quot;b�se&quot;);&lt;/script&gt;</freitext>
    </raum>
</raumzeit>';
        $this->assertEquals($compare, $data);


        $data = renderTemplate('dates/date_xml', $this->testData, array('date' => new SingleDate()));
        $compare = '<date>Mo., 11.11.2010 12:00 - 14:00, Ort: H�rsaal 1 &lt;br&gt;</date>';
        $this->assertEquals($compare, $data);
    }

}


// mock objects/functions
class ResourceObject
{
    static function Factory($resource_id)
    {
        $resObject = new ResourceObject();
        $resObject->resource_id = $resource_id;

        return $resObject;
    }

    function __construct() { }
    
    function getName()
    {
        return 'H�rsaal 1 <br>';
    }

    function getFormattedLink() {
        return '<a onclick="window.open(...)">H�rsaal 1</a>';
    }
}


class SingleDate
{
    public $resource_id = abcdef1234567890;

    function getResourceID()
    {
        return $this->resource_id;
    }

    function getFreeRoomText() {
        return '<script>alert("b�se");</script>';
    }

    function toString()
    {
        return 'Mo., 11.11.2010 12:00 - 14:00';
    }
}

function getPresenceTypes()
{
    return array(1,7);
}

function renderTemplate($template, $data, $params = array())
{
    $GLOBALS['template_factory'] = new Flexi_TemplateFactory(dirname(__FILE__) . '/../../../../templates');

    $template = $GLOBALS['template_factory']->open($template); 
    $template->set_attribute('dates', $data);

    $template->set_attributes($params);

    return trim($template->render());
}


// copied functions
function shrink_dates($dates)
{
    return array(' 12.05. 09:00 - 13:00');
}

function getFormattedRooms($rooms, $link = false)
{
    $room_list = array();

    if (is_array($rooms)) {
        foreach ($rooms as $room_id => $count) {
            if ($room_id) {
                $resObj =& ResourceObject::Factory($room_id);
                if ($link) {
                    $room_list[] = $resObj->getFormattedLink(TRUE, TRUE, TRUE);
                } else {
                    $room_list[] = htmlReady($resObj->getName());
                }
            }
        }
    }

    return $room_list;
}

function getPlainRooms($rooms)
{
    $room_list = array();

    if (is_array($rooms)) {
        foreach ($rooms as $room_id => $count) {
            if ($room_id) {
                $resObj =& ResourceObject::Factory($room_id);
                $room_list[] = $resObj->getName();
            }
        }
    }

    return $room_list;
}

function getWeekday($day_num, $short = null)
{
    $day = array('1' => 'Mo', '3' => 'Mi');
    return $day[$day_num];
}
