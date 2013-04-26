<?php
/*
 * SimpleCollectionTest - unit tests for the SimpleCollection class
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once 'lib/models/SimpleCollection.class.php';

class SimpleCollectionTest extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
    }
    
    function tearDown()
    {
    }

    public function testConstruct()
    {
        $data[] = array('id' => 1, 'vorname' => 'André', 'nachname' => 'Noack', 'perm' => 'dozent');
        $data[] = array('id' => 2, 'vorname' => 'Stefan', 'nachname' => 'Suchi', 'perm' => 'dozent');
        $data[] = array('id' => 10, 'vorname' => 'Elmar', 'nachname' => 'Ludwig', 'perm' => 'admin');
        $data[] = array('id' => 11, 'vorname' => 'Jan-Hendrik', 'nachname' => 'Wilms', 'perm' => 'tutor');
        $data[] = array('id' => 15, 'vorname' => 'Nico', 'nachname' => 'Müller', 'perm' => 'root');
        
        $a = SimpleCollection::createFromArray($data);
        $this->assertInstanceOf('SimpleCollection', $a);
        $this->assertEquals($a[0], $data[0]);
        $this->assertEquals($a->getArrayCopy(), $data);
        $finder = function () use ($data) {return $data;};
        $a = new SimpleCollection($finder);
        $this->assertEquals($a[0], $data[0]);
        $this->assertEquals($a->getArrayCopy(), $data);
        return $a;
    }

    /**
     * @depends testConstruct
     */
    public function testFindBy($a)
    {
        $test = $a->findBy('id', 1);
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(1, $test);
        $test = $a->findBy('id', array(1,2));
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(2, $test);
        $test = $a->findBy('id', '1', '==');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(1, $test);
        $test = $a->findBy('id', '1', '===');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(0, $test);
        $test = $a->findBy('id', '1', '!=');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(4, $test);
        $test = $a->findBy('id', '1', '!==');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(5, $test);
        $test = $a->findBy('id', 5, '>');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(3, $test);
        $test = $a->findBy('id', 5, '>=');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(3, $test);
        $test = $a->findBy('id', array(10,15), '><');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(1, $test);
        $test = $a->findBy('id', array(10,15), '>=<=');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(3, $test);
        $test = $a->findBy('vorname', 'andre', '%=');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(1, $test);
        $test = $a->findBy('nachname', 'll', '*=');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(1, $test);
        $test = $a->findBy('nachname', 'Müll', '^=');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(1, $test);
        $test = $a->findBy('nachname', 'lms', '$=');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(1, $test);
        $test = $a->findBy('nachname', '/[^a-zA-Z]/', '~=');
        $this->assertInstanceOf('SimpleCollection', $test);
        $this->assertCount(1, $test);
        $one = $a->findOneBy('id', 10);
        $this->assertEquals('Elmar', $one['vorname']);
        $this->assertEquals('Elmar', $a->findBy('id', 10)->val('vorname'));
    }
    
    /**
     * @depends testConstruct
     */
    public function testPluck($a)
    {
        $expected = array(1, 2, 10, 11, 15);
        $this->assertEquals($expected, $a->pluck('id'));
        $expected = array(array(1, 'dozent'), array(2, 'dozent'), array(10, 'admin'),array(11, 'tutor'), array(15, 'root'));
        $this->assertEquals($expected, $a->pluck(array('id', 'perm')));
    }

    /**
     * @depends testConstruct
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage unknown operator: foo
     */
    public function testInvalidCompOperatorException($a)
    {
        SimpleCollection::getCompFunc('foo', null);
    }
}
