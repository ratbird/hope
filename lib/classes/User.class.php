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

class User extends SimpleORMap
{
    protected $auth_user_md5;
    protected $user_info;
    
    static function find($id) 
    {
        return SimpleORMap::find(__CLASS__, $id);
    }
    
    static function findByUsername($username)
    {
        $user = self::findBySql('username = ' . DBManager::get()->quote($username));
        return isset($user[0]) ? $user[0] : null;
    }
    
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
            $ret[$c]->setData($row);
            $ret[$c]->setNew(false, $row['user_info_set'] === null);
            ++$c;
        }
        return $ret;
    }
    
    public static function toObject($id_or_object)
    {
        return SimpleORMap::toObject(__CLASS__, $id_or_object);
    }
    
    function __construct($id = null)
    {
        $this->auth_user_md5 = new AuthUserMd5($id);
        $this->user_info = new UserInfo($id);
        parent::__construct($id);
    }
    
    protected function getTableScheme()
    {
        $this->db_fields = $this->auth_user_md5->db_fields + $this->user_info->db_fields;
        $this->pk = $this->auth_user_md5->pk;
    }
    
    function getNewId ()
    {
        return  $this->auth_user_md5->getNewId();
    }
    
    function setId($id)
    {
        $this->auth_user_md5->setId($id);
        $this->user_info->setId($id);
    }
    
    function getWhereQuery()
    {
        return $this->auth_user_md5->getWhereQuery();
    }
    
    function restore ()
    {
        $where_query = $this->getWhereQuery();

        if ($where_query) {
            $query = "SELECT *, user_info.user_id as user_info_set FROM auth_user_md5 LEFT JOIN user_info USING (user_id) WHERE "
                    . join(" AND ", $where_query);
            $rs = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            if (isset($rs[0])) {
                if ($this->setData($rs[0], true)){
                    $this->content['user_info_set'] = $rs[0]['user_info_set'];
                    $this->content_db = $this->content;
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
    
    function store ()
    {
        $data = $this->toArray();
        $this->auth_user_md5->setData($data, true);
        $this->auth_user_md5->setNew($this->isNew());
        $this->user_info->setData($data, true);
        $this->user_info->setNew($this->isNew() && $this->content['user_info_set']);
        
        $trigger_chdate = !$this->isFieldDirty('chdate') && ($this->auth_user_md5->isDirty() || $this->user_info->isDirty());
        
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
    
    function triggerChdate ()
    {
       return $this->user_info->triggerChdate();
    }
    
    function delete ()
    {
        $ret = $this->auth_user_md5->delete();
        if ($ret) {
            $this->user_info->delete();
        }
        return $ret;
    }
    
}
