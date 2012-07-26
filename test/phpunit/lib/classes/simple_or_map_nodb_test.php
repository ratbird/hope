<?php
/*
 * SimpleOrMapNodbTest - unit tests for the SimpleOrMap class without database access
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
require_once 'lib/classes/SimpleORMap.class.php';
require_once 'lib/classes/Config.class.php';
require_once 'lib/classes/StudipCache.class.php';

class StudipArrayCache implements StudipCache {
    public $data = array();

    function expire($key)
    {
        unset($this->data);
    }

    function read($key)
    {
        return $this->data[$key];
    }

    function write($name, $content, $expire = 43200)
    {
        return ($this->data[$name] = $content);
    }
}

class auth_user_md5 extends SimpleORMap
{}

class SimpleOrMapNodbTest extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $testconfig = new Config(array('cache_class' => 'StudipArrayCache'));
        Config::set($testconfig);
        StudipCacheFactory::setConfig($testconfig);
        $GLOBALS['CACHING_ENABLE'] = true;
        $cache = StudipCacheFactory::getCache();
        foreach (array('auth_user_md5') as $db_table) {
            include TEST_FIXTURES_PATH."simpleormap/$db_table.php";
            foreach ($result as $rs) {
                $db_fields[strtolower($rs['Field'])] = array(
                                                            'name' => $rs['Field'],
                                                            'type' => $rs['Type'],
                                                            'key'  => $rs['Key'],
                                                            'null' => $rs['Null'],
                                                            'default' => $rs['Default']
                );
                if ($rs['Key'] == 'PRI'){
                    $pk[] = strtolower($rs['Field']);
                }
            }
            $schemes[$db_table]['db_fields'] = $db_fields;
            $schemes[$db_table]['pk'] = $pk;
        }
        $cache->write('DB_TABLE_SCHEMES', serialize($schemes));
    }

    public function testConstruct()
    {
        $a = new auth_user_md5();
        $this->assertInstanceOf('SimpleOrMap', $a);
        return $a;
    }

    /**
     * @depends testConstruct
     */
    public function testMetaData($a)
    {
        $meta = $a->getTableMetadata();
        //$this->assertEquals('auth_user_md5', $meta['db_table']);
        $this->assertEquals('user_id', $meta['pk'][0]);
        $this->assertArrayHasKey('email', $meta['fields']);
    }

    /**
     * @depends testConstruct
     */
    public function testGetterAndSetter($a)
    {
        $mail = 'noack@data-quest';
        $a->email = $mail;
        $this->assertEquals($mail, $a->email);
        $this->assertEquals($mail, $a->EMAIL);
        $mail = 'anoack@data-quest';
        $a['email'] = $mail;
        $this->assertEquals($mail, $a['email']);
    }

    /**
     * @depends testConstruct
     */
    public function testDirty($a)
    {
        $this->assertEquals(true, $a->isDirty());
        $this->assertEquals(true, $a->isFieldDirty('email'));
        $this->assertEquals(false, $a->isFieldDirty('vorname'));
    }

    /**
     * @depends testConstruct
     */
    public function testRevert($a)
    {
        $a->revertValue('email');
        $this->assertEquals(false, $a->isDirty());
        $this->assertEquals(false, $a->isFieldDirty('email'));
    }

    /**
     * @depends testConstruct
     */
    public function testsetData($a)
    {
        $a->vorname = 'André';
        $data['email'] = 'fuhse@data-quest.de';
        $data['vorname'] = 'Rasmus';
        $data['nachname'] = 'Fuhse';
        $data['USERNAME'] = 'krassmus';
        $a->setData($data, true);
        $this->assertEquals($data['vorname'], $a->vorname);
        $this->assertEquals($data['nachname'], $a->nachname);
        $this->assertEquals($data['email'], $a->email);
        $this->assertEquals($data['USERNAME'], $a->username);
        $this->assertEquals(false, $a->isDirty());
        
        $data2['vorname'] = 'Krassmus';
        $data2['username'] = 'rasmus';
        $a->setData($data2, false);
        $this->assertEquals($data2['vorname'], $a->vorname);
        $this->assertEquals($data2['username'], $a->username);
        $this->assertEquals($data['nachname'], $a->nachname);
        $this->assertEquals($data['email'], $a->email);
        $this->assertEquals(true, $a->isDirty());
    }
}
