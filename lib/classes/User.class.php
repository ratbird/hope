<?php
/**
 * User.class.php
 * model class for combined auth_user_md5/user_info record
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2011 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

require_once 'AuthUserMd5.class.php';
require_once 'UserInfo.class.php';

/**
 * Enter description here ...
 *
 */
class User extends SimpleORMap
{
    /**
     * internal used UserInfo object
     * @var AuthUserMd5
     */
    protected $auth_user_md5;

    /**
     * internal used UserInfo object
     * @var UserInfo
     */
    protected $user_info;

    /**
     * flag for existence of user_info record
     * @var bool
     */
    private $user_info_exists;

    /**
     * returns new User instance for given user id
     * when found in db, else null
     * @param string a user id
     * @return mixed a User object or null
     */
    static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    /**
     * returns new User instance for given username
     * when found in db, else null
     * @param string a username
     * @return mixed a User object or null
     */
    static function findByUsername($username)
    {
        $user = self::findBySql('username = ' . DBManager::get()->quote($username));
        return isset($user[0]) ? $user[0] : null;
    }

    /**
     * returns array of instances of given class filtered by given sql
     * should be overridden in subclass to omit $class param
     * @param string $class
     * @param string sql clause to use on the right side of WHERE
     * @return array
     */
    static function findBySql($where)
    {
        $db = DBManager::get();
        $sql = "SELECT *, user_info.user_id as user_info_set FROM auth_user_md5 LEFT JOIN user_info USING (user_id) WHERE " . $where;
        $rs = $db->query($sql);
        $ret = array();
        $c = 0;
        while($row = $rs->fetch(PDO::FETCH_ASSOC))
        {
            $ret[$c] = new User();
            $ret[$c]->setData($row, true);
            $ret[$c]->setNew(false);
            ++$c;
        }
        return $ret;
    }

    /**
     * returns user object for given id or null
     * the param could be a string, an assoc array containing primary key field
     * or an already matching object. In all these cases an object is returned
     *
     * @param mixed $id_or_object id as string, object or assoc array
     * @return User
     */
    public static function toObject($id_or_object)
    {
        return SimpleORMap::toObject(__CLASS__, $id_or_object);
    }

    /**
     *
     * @param string $id a user id
     */
    function __construct($id = null)
    {
        $this->auth_user_md5 = new AuthUserMd5();
        $this->user_info = new UserInfo();
        parent::__construct($id);
    }

    protected function getTableScheme()
    {
        $this->db_fields = $this->auth_user_md5->db_fields + $this->user_info->db_fields;
        $this->pk = $this->auth_user_md5->pk;
    }

    /* (non-PHPdoc)
     * @see SimpleORMap::setId()
     */
    function setId($id)
    {
        return $this->auth_user_md5->setId($id);
    }

    /* (non-PHPdoc)
     * @see SimpleORMap::getNewId()
     */
    function getNewId()
    {
        return  $this->auth_user_md5->getNewId();
    }

    /* (non-PHPdoc)
     * @see SimpleORMap::getWhereQuery()
     */
    function getWhereQuery()
    {
        return $this->auth_user_md5->getWhereQuery();
    }

    /* (non-PHPdoc)
     * @see SimpleORMap::setData()
     */
    function setData($data, $reset = false)
    {
        $ret = parent::setData($data, $reset);
        $this->user_info_exists = $data['user_info_set'];
        return $ret;
    }

    /* (non-PHPdoc)
     * @see SimpleORMap::restore()
     */
    function restore()
    {
        $where_query = $this->getWhereQuery();

        if ($where_query) {
            $query = "SELECT *, user_info.user_id as user_info_set FROM auth_user_md5 LEFT JOIN user_info USING (user_id) WHERE "
                    . join(" AND ", $where_query);
            $rs = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            if (isset($rs[0])) {
                if ($this->setData($rs[0], true)){
                    $this->setNew(false);
                    return true;
                } else {
                    $this->setNew(true);
                    return false;
                }
            }
        } else {
            $this->setNew(true);
            $this->initializeContent();
            return FALSE;
        }
    }

    /* (non-PHPdoc)
     * @see SimpleORMap::store()
     */
    function store()
    {
        $data = $this->toArray();
        $this->auth_user_md5->setData($data, true);
        $this->auth_user_md5->setNew($this->isNew());
        $this->user_info->setData($data, true);
        $this->user_info->setNew($this->isNew() || !$this->user_info_exists);

        $trigger_chdate = !$this->isFieldDirty('chdate') && $this->isDirty();

        $ret_a = $this->auth_user_md5->store();
        $ret_u = $this->user_info->store();

        if ($ret_a === false || $ret_u === false) {
            $ret = false;
        } else if ($ret_a === 1 || $ret_u === 1) {
            $this->triggerChdate();
            $ret = 1;
        } else {
            $ret = 0;
        }
        $this->restore();
        return $ret;
    }

    /* (non-PHPdoc)
     * @see SimpleORMap::triggerChdate()
     */
    function triggerChdate()
    {
       return $this->user_info->triggerChdate();
    }

    /* (non-PHPdoc)
     * @see SimpleORMap::delete()
     */
    function delete()
    {
        $ret = $this->auth_user_md5->delete();
        if ($ret) {
            $this->user_info->delete();
        }
        return $ret;
    }

    /**
     * returns the name in specified format
     * (formats defined in $GLOBALS['_fullname_sql'])
     *
     * @param string one of full,full_rev,no_title,no_title_rev,no_title_short,no_title_motto,full_rev_username
     * @return string guess what - the fullname
     */
    function getFullName($format = "full")
    {
        $sql = $GLOBALS['_fullname_sql'][$format];
        $db = DBManager::get();
        if (!$sql) {
            return $this->vorname . ' ' . $this->nachname;
        }
        foreach ($this->db_fields as $one) {
            $search[] = $one['name'];
            $replace[] = $db->quote($this->getValue($one['name']));
        }
        return $db->query("SELECT " . str_replace($search, $replace, $sql))->fetchColumn();
    }

}