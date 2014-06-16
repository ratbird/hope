<?php

class DocUsergroupConfig extends SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'doc_usergroup_config';
        parent::__construct($id);
    }
    
    static function getGroupConfig($usergroup)
    {
        //$db= DBManager::get();
        $group = array_shift(DocUsergroupConfig::findByUsergroup($usergroup));
        if (!empty($group)) {
            $data['id'] = $group['id'];
            $data['name'] = $group['usergroup'];
            $data['upload_quota'] = $group['upload_quota'];
            $data['upload_unit'] = $group['upload_unit'];
            $data['quota'] = $group['quota'];
            $data['quota_unit'] = $group['quota_unit'];
            $data['forbidden'] = $group['upload_forbidden'];
            $data['area_close']= $group['area_close'];
            $data['area_close_text']=$group['area_close_text'];
            $data['types'] = DocUsergroupConfig::getForbiddenTypesNameFromGroup($group['usergroup']);
        } else {
            return array();
        }
        return $data;
    }
    
    static function getConfig($config_id)
    {
        $result = array_shift(DocUsergroupConfig::findById($config_id));
        if(!empty($result)){
                       $data['id'] = $result['id'];
            $data['name'] = $result['usergroup'];
            if($result['is_group_config'] == 0){
                $user = new User($result['usergroup']);//User::findByDatafield('user_id', $result['userresult']);
                $data['name'] = $user->getFullName();
            }
            $data['upload_quota'] = $result['upload_quota'];
            $data['upload_unit'] = $result['upload_unit'];
            $data['quota'] = $result['quota'];
            $data['quota_unit'] = $result['quota_unit'];
            $data['forbidden'] = $result['upload_forbidden'];
            $data['closed'] = $result['area_close'];
            $data['closed_text'] = $result['area_close_text'];
            $data['isGroupConfig'] = $result['is_group_config'];
            $data['types'] = DocUsergroupConfig::getForbiddenTypesNameFromGroup($result['usergroup']);

            return $data;
        }else{
            return false;
        }
        
    }
    
    /*
     * Returns all configuration for Groups (user, autor etc.)
     * 
     */
    static function getGroupConfigAll($configType = NULL)
    {
        if(isset($configType)){
            $result = DocUsergroupConfig::findBySQL('usergroup IS NOT NULL AND is_group_config = '. $configType.
                                                    ' ORDER BY usergroup');
        }else{
            $result = DocUsergroupConfig::findBySQL('usergroup IS NOT NULL ORDER BY usergroup');
        }
        $data = array();
        foreach ($result as $group) {
            $bar['id'] = $group['id'];
            $bar['name'] = $group['usergroup'];
            if($group['is_group_config'] == 0){
                $user = new User($group['usergroup']);//User::findByDatafield('user_id', $group['usergroup']);
                $bar['name'] = $user->getFullName();
            }
            $bar['upload_quota'] = $group['upload_quota'];
            $bar['upload_unit'] = $group['upload_unit'];
            $bar['quota'] = $group['quota'];
            $bar['quota_unit'] = $group['quota_unit'];
            $bar['forbidden'] = $group['upload_forbidden'];
            $bar['types'] = DocUsergroupConfig::getForbiddenTypesNameFromGroup($group['usergroup']);
            $bar['closed'] = $group['area_close'];
            
            $data[] = $bar;
        }
        return $data;
    }
    
    public static function getForbiddenTypesNameFromGroup($groupname) 
    {
        $db = DBManager::get();
        $data = array();
        $typeID = DocFileTypeForbidden::findBySQL('usergroup = ' . $db->quote($groupname));
        foreach ($typeID as $id) {
            $data[] = array_pop(DocFiletype::findById($id['dateityp_id']));
        }
        return $data;
    }
    
    public static function switchUploadStatus($config_id)
    {
        $result = array_shift(DocUsergroupConfig::findById($config_id));
        if(!empty($result)){
            if($result['upload_forbidden'] == 0){
                $result->setData(array('upload_forbidden'=>1));
            }else{
                $result->setData(array('upload_forbidden'=>0));
            }
            return $result->store();
        }else{
            return false;
        }
    }
    
    public static function switchDocumentAreaStatus($config_id, $reason_text = NULL)
    {
        $result = array_shift(DocUsergroupConfig::findById($config_id));
        if(!empty($result)){
            if($result['area_close'] == 0){
                if(!isset($reason_text)){
                    $reason_text = 'Keine Grund angegeben.';
                }
                $result->setData(array('area_close'=>1, 'area_close_text'=> $reason_text));
            }else{
                $result->setData(array('area_close'=>0, 'area_close_text'=> ''));
            }
            return $result->store();
        }else{
            return false;
        }
    }
    

    /**
     * return upload configuration for user for given user_id
     *       array('upload'=>in byte, 'upload_unit'=> kB, MB, GB or TB, 
     *              'quota'=>in byte, 'quota_unit'=> kB, MB, GB or TB,
     *               'forbidden'=> 1 or 0, 'types'=>array(forbidden datetypes)
     *               ,'area_close'=> 1=Dateibereich gesperrt 0=Dateibreich offen
     *               ,'area_close_text'=>Begruendung warm Dateibereich gesperrt) 
     * @param  $user_id a user_id
     * @return array()
     *  
     */
    public static function getUserConfig($user_id) 
    {
        $user = array_shift(User::findByUser_id($user_id));
        $config = DocUsergroupConfig::getGroupConfig($user_id);
        if (empty($config)) {
            $config = DocUsergroupConfig::getGroupConfig($user['perms']);
        }
        if (empty($config)) {
            $config = DocUsergroupConfig::getGroupConfig('default');
        }
        return $config;
    }
    
    public static function getUser($user_id)
    {
        $db = DBManager::get();
        $user = array_shift(User::findByUser_id($user_id));
        $userConfig = UserConfigEntry::findBySQL('field = '. $db->quote('CALENDAR_SETTINGS').' AND user_id = '. $db->quote($user_id));
        if(!empty($user) || !empty($userConfig)){
            $data['user'] = $user;
            $data['userConfig'] = $userConfig;            
            return $data;
        }else{
            return array();
        }       
    }
    /*
     * Method to store the individual-user or the group-settings
     */
    public function setConfig($data)
    {
        if (!empty($data)) {
            $config = array_pop(DocUsergroupConfig::findById($data['id']));
            if (empty($config)) {
                $config = new DocUsergroupConfig();
                $config->setData($data);
            } else {
                $config->setData(array('quota' => $data['quota'], 'upload_quota' => $data['upload_quota'],
                    'upload_unit' => $data['upload_unit'], 'quota_unit' => $data['quota_unit']));
            }
            $log[] = $config->store();
            $db = DBManager::get();
            DocFileTypeForbidden::deleteBySQL('usergroup = ' . $db->quote($config['usergroup']));
            foreach ($data['datetype_id'] as $file) {
                $filetype = new DocFileTypeForbidden();
                $filetype->setData(array('usergroup' => $config['usergroup'], 'dateityp_id' => $file));
                $log[] = $filetype->store();
            }
            return true;
        } else {
            return false;
        }
    }
    
    public static function searchForUser($searchData) 
    {
        $db = DBManager::get();
        $stringCount = 0;
        $searchString = '';
        $searchDataCount = 0;
        foreach ($searchData as $name => $wert) {
            $searchDataCount++;
            if (strlen($wert) > 0) {
                if ($stringCount > 0 && $searchDataCount <= count($searchData)) {
                    $stringCount++;
                    $searchString = ' AND ' . ' ' . $name . ' LIKE ' . $db->quote('%'.$wert.'%') . ' ';
                } else {
                    $searchString = ' ' . $name . ' LIKE ' . $db->quote('%'.$wert.'%') . ' ';
                    $stringCount++;
                }
                $searchQuery .= $searchString;
            }
        }
        if (strlen($searchQuery) > 0) {
            $user = User::findBySQL($searchQuery . ' ORDER BY Nachname');
            if (empty($user)) {
                return array();
            } else {
                return $user;
            }
        } else {
            return array();
        }
    }
}