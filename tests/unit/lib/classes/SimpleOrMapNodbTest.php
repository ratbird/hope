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
require_once 'lib/models/SimpleORMap.class.php';
require_once 'lib/classes/Config.class.php';
require_once 'lib/classes/StudipCache.class.php';
require_once 'lib/classes/CSVArrayObject.class.php';

if (!class_exists('StudipArrayCache')) {
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
}

class auth_user_md5 extends SimpleORMap
{
    public $additional_dummy_data = null;

    protected static function configure()
    {
        $config['db_table'] = 'auth_user_md5';
        $config['additional_fields']['additional']['get'] = function ($record, $field) {return $record->additional_dummy_data;};
        $config['additional_fields']['additional']['set'] = function ($record, $field, $data) {return $record->additional_dummy_data = $data;};
        $config['serialized_fields']['csvdata'] = 'CSVArrayObject';
        parent::configure($config);
    }

    function getPerms()
    {
        return 'ok:' . $this->content['perms'];
    }

    function setPerms($perm)
    {
        return $this->content['perms'] = strtolower($perm);
    }

    public function registerCallback($types, $cb)
    {
        return parent::registerCallback($types, $cb);
    }
}

class SimpleOrMapNodbTest extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        SimpleORMap::expireTableScheme();
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
                                                            'null' => $rs['Null'],
                                                            'default' => $rs['Default'],
                                                            'extra' => $rs['Extra']
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

    function tearDown()
    {
        SimpleORMap::expireTableScheme();
        Config::set(null);
        StudipCacheFactory::setConfig(null);
        $GLOBALS['CACHING_ENABLE'] = false;
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
        $a->perms = 'ADMIN';
        $this->assertEquals('ok:admin', $a['perms']);
        $a->csvdata = '1,2,3,4,5';
        $this->assertInstanceOf('CSVArrayObject', $a->csvdata);
        $this->assertEquals('1,2,3,4,5', (string)$a->csvdata);
        $this->assertEquals(range(1,5), $a['csvdata']->getArrayCopy());
    }

    /**
     * @depends testConstruct
     */
    public function testDirty($a)
    {
        $this->assertEquals(true, $a->isDirty());
        $this->assertEquals(true, $a->isFieldDirty('email'));
        $this->assertEquals(false, $a->isFieldDirty('vorname'));
        $this->assertEquals(true, $a->isFieldDirty('csvdata'));
    }

    /**
     * @depends testConstruct
     */
    public function testRevert($a)
    {
        $a->revertValue('email');
        $a->revertValue('perms');
        $a->revertValue('csvdata');
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
        $data['csvdata'] = range(1,4);
        $a->setData($data, true);
        $this->assertEquals($data['vorname'], $a->vorname);
        $this->assertEquals($data['nachname'], $a->nachname);
        $this->assertEquals($data['email'], $a->email);
        $this->assertEquals($data['USERNAME'], $a->username);
        $this->assertEquals('1,2,3,4', (string)$a->csvdata);
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

    /**
     * @depends testConstruct
     */
    public function testPrimaryKey($a)
    {
        $a->setId(1);
        $this->assertEquals(1, $a->user_id);
        $this->assertEquals(1, $a->id);
        $this->assertEquals(1, $a->getId());
        $a->id = 2;
        $this->assertEquals(2, $a->user_id);
        $this->assertEquals(2, $a->id);
        $this->assertEquals(2, $a->getId());
    }

    /**
     * @depends testConstruct
     */
    public function testAdditional($a)
    {
        $this->assertNull($a->additional);
        $a->additional = 'test';
        $this->assertEquals($a->additional_dummy_data, $a->additional);
    }

    /**
     * @depends testConstruct
     */
    public function testToArray($a)
    {
        $to_array = $a->toArray();
        $this->assertEquals(2, $to_array['id']);
        $this->assertEquals(2, $to_array['user_id']);
        $this->assertEquals('test', $to_array['additional']);
        $this->assertEquals('ok:', $to_array['perms']);
        $this->assertEquals(range(1,4), $to_array['csvdata']);
        $this->assertArrayHasKey('visible', $to_array);
        $this->assertCount(16, $to_array);

        $to_array = $a->toArray('id user_id additional perms');
        $this->assertEquals(2, $to_array['id']);
        $this->assertEquals(2, $to_array['user_id']);
        $this->assertEquals('test', $to_array['additional']);
        $this->assertEquals('ok:', $to_array['perms']);
        $this->assertArrayNotHasKey('visible', $to_array);
        $this->assertCount(4, $to_array);
    }

    /**
     * @depends testConstruct
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage unknown not found.
     */
    public function testInvalidColumnException($a)
    {
        $a->unknown = 1;
    }

    /**
     * @depends testConstruct
     */
    public function testCallback($a)
    {
        $callback_was_here = null;
        $cb = function ($record, $type) use (&$callback_was_here)
        {
            $callback_was_here = $type;
            $record->id = 3;
            return false;
        };
        $a->registerCallback('before_store', $cb);
        $stored = $a->store();
        $this->assertFalse($stored);
        $this->assertEquals(3, $a->id);
        $this->assertEquals('before_store', $callback_was_here);
    }
}
