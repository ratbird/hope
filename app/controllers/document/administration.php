<?
/**
 * Document_AdministrationController
 *
 * @author      Stefan Osterloh <s.osterloh@uni-oldenburg.de>
 * @license     GPL2 or any later version
 * @category    Stud.IP
 * @since       3.1
 */

require_once 'app/controllers/authenticated_controller.php';

class Document_AdministrationController extends AuthenticatedController {

    public function before_filter(&$action, &$args) 
    {
        parent::before_filter($action, $args);
        Navigation::activateItem('/admin/config/document_area');
        PageLayout::setTitle(_('Dateibereich') . ' - ' . _('Administration'));
         if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }
        if(empty($_SESSION['document_config_filter'])){
            $_SESSION['document_config_filter'] = 'all';
        }
        PageLayout::addScript('ui.multiselect.js');
        PageLayout::addStylesheet('jquery-ui-multiselect.css');
        $this->getInfobox();

    }
    
    public function index_action($configType = NULL)
    {
        $viewData['configs'] =  DocUsergroupConfig::getGroupConfigAll($configType);
        $this->viewData = $viewData;
    }
    
    public function filter_action()
    {
        if(Request::submitted('filter')){
            $_SESSION['document_config_filter'] = Request::get('showFilter');
        }
        if($_SESSION['document_config_filter'] == 'group'){
            $this->redirect('document/administration/index/1'); 
        }else if($_SESSION['document_config_filter'] == 'individual'){
            $this->redirect('document/administration/index/0'); 
        }else{
            $this->redirect('document/administration/index'); 
        }
    }
    
    public function activateUpload_action($config_id)
    {
        DocUsergroupConfig::switchUploadStatus($config_id);
        $this->redirect('document/administration/filter');
    }
    
    public function deactivateUpload_action($config_id)
    {
        DocUsergroupConfig::switchUploadStatus($config_id);
        $this->redirect('document/administration/filter');
    }
    
    public function activateDocumentArea_action($config_id)
    {
        DocUsergroupConfig::switchDocumentAreaStatus($config_id);
        $this->redirect('document/administration/filter');
    }
    public function deactivateDocumentArea_action($config_id)
    {
        if (Request::submitted('store')) {
            if(strlen(Request::get('reason_text'))>0){
                DocUsergroupConfig::switchDocumentAreaStatus($config_id, Request::get('reason_text'));
            }else{
                DocUsergroupConfig::switchDocumentAreaStatus($config_id);
            }
            $this->redirect('document/administration/filter');
        } else {
            $config = DocUsergroupConfig::getConfig($config_id);
            if ($config != false) {
                $this->config_id = $config_id;
                $this->reason_text = $config['closed_text'];
                if (Request::isXhr()) {
                    header('X-Title: ' . _('Persönlichen Dateibereich für ' . $config['name'] . ' sperren'));
                } else {
                    $this->header = 'Persönlichen Dateibereich für ' . $config['name'] . ' sperren';
                }
            } else {
                PageLayout::postMessage(MessageBox::error(_('Einstellung konnten nicht gefunden werden.')));
                $this->redirect('document/administration/filter');
            }
        }
    }
    
    public function edit_action($config_id = 0, $isGroupConfig = false)
    {
        $this->types = DocFiletype::findBySQL('id IS NOT NULL ORDER BY type'); 
        //Existing entry     
        if($config_id != 0){
            $this->config_id = $config_id;
            $this->config = DocUsergroupConfig::getConfig($config_id);
            if($this->config != false){
                if($this->config['isGroupConfig'] == 1){
                    $this->isGroupConfig == 'true';
                }else{
                    $this->isGroupConfig == 'false';
                }
                $this->isGroupConfig == '';
                $this->config['upload_quota']= $this->sizeInUnit($this->config['upload_quota'], $this->config['upload_unit']);
                $this->config['quota']= $this->sizeInUnit($this->config['quota'], $this->config['quota_unit']);
                if(Request::isXhr()){
                    header('X-Title: ' . _('Persönlichen Dateibereich für ' . $this->config['name'] . ' konfigurieren'));
                }else{
                    $this->head = 'Persönlichen Dateibereich für ' . $this->config['name'] . ' konfigurieren';
                }
            }else{
                PageLayout::postMessage(MessageBox::error(_('Einstellung konnten nicht gefunden werden.')));
                $this->redirect('document/administration/filter');
            }
        //New Entry
        }else{
            //Configuration Entry for a group
            $this->config_id = 0;
            if($isGroupConfig == 'true'){
                $this->config['types'] = array();
                $this->isGroupConfig = $isGroupConfig;
                $groupConfigs = DocUsergroupConfig::getGroupConfigAll(1);
                $groups = array("default" => "default", "user" =>"user", "autor" => "autor",
                            "tutor" => "tutor", "dozent" => "dozent", "admin" => "admin",
                            "root" => "root");
                foreach($groups as $group){
                    foreach($groupConfigs as $config){
                        if($config['name'] == $group){
                            unset($groups[$config['name']]);
                        }
                    }
                }
                $this->groups = $groups;
                if (Request::isXhr()) {
                    header('X-Title: ' . _('Neue Gruppeneinstellung für den Persönlichen Dateibereich erstellen'));
                } else {
                    $this->head = 'Neue Gruppeneinstellung für den Persönlichen Dateibereich erstellen';
                }
            //Configuration Entry for a User    
            }
            if($isGroupConfig != 'true' && $isGroupConfig != 'false'){
                $user = new User($isGroupConfig);
                $groupConfigs = DocUsergroupConfig::getGroupConfigAll(1);
                $this->config['types'] = array();
                $this->isGroupConfig = false;
                $this->user_id = $isGroupConfig;
                if (Request::isXhr()) {
                    header('X-Title: ' . _('Neue Einstellung für '.$user->getFullName()));
                } else {
                    $this->head = 'Neue Einstellung für '.$user->getFullName();
                }
            }
        }
    }
    
    function store_action($config_id, $isGroupConfig)
    {
        if (Request::float('upload_size') && Request::float('quota_size')) {
            if($config_id == 0){
                $data['id'] = '';
                $data['usergroup'] = Request::get('group');
            }else{
                $data['id'] = $config_id;
            }
            $data['upload_quota'] = $this->sizeInByte(Request::float('upload_size'), Request::get('unitUpload'));
            $data['quota'] = $this->sizeInByte(Request::float('quota_size'), Request::get('unitQuota'));
            if($isGroupConfig == false){
                $data['is_group_config'] = 0;
            }else{
                $data['is_group_config'] = 1;
            }
            if ($data['upload_quota'] <= $data['quota'] && $data['quota'] >= 0 && $data['upload_quota'] >= 0) {
                $data['upload_forbidden'] =  '0';
                $data['quota_unit'] = Request::get('unitQuota');
                $data['upload_unit'] = Request::get('unitUpload');
                $data['datetype_id'] = Request::intArray('datetype');
                if(DocUsergroupConfig::setConfig($data)){
                    $message = 'Das Speichern der Einstellungen war erfolgreich. ';
                    PageLayout::postMessage(MessageBox::success($message));     
                }else{
                    PageLayout::postMessage(MessageBox::error(_(
                            'Beim speichern der Einstellungen ist ein Fehler aufgetreten'.
                            ' oder es wurden keine Änderungen vorgenommen.')));
                }
            }else{
                PageLayout::postMessage(MessageBox::error(_(
                        'Upload-Quota ist größer als das gesamte Nutzer-Quota. Bitte korrigieren Sie Ihre Eingabe.')));
            }
        }else{
             PageLayout::postMessage(MessageBox::error(_(
                     'Es wurden fehlerhafte Werte für die Quota eingegeben.')));
        } 
        $this->redirect('document/administration/filter');
    }
    
    /*
     * $id represents the value for the primarykey in the Database
     * $type represents the kind of configuration. individual oder group-config
     */
    public function delete_action($config_id)
    {
        $config = array_shift(DocUsergroupConfig::findById($config_id));
        //var_dump($config);die;
        if(!empty($config)){
            $db = DBManager::get();
            DocUsergroupConfig::deleteBySQL('id = ' . $config_id);
            DocFileTypeForbidden::deleteBySQL('usergroup = ' . $db->quote($config['usergroup']));
        }
        $this->redirect('document/administration/filter/'); 
             
    }
    
    public function individual_action($user_id = null) 
    {
        $users = array();
        if ($user_id != null) {
            $users = DocUsergroupConfig::searchForUser(array('user_id' => $user_id));
        }
        if (Request::submitted('search')) {
            $data['username'] = Request::get('userName');
            $data['Vorname'] = Request::get('userVorname');
            $data['Nachname'] = Request::get('userNachname');
            $data['Email'] = Request::get('userMail');
            if (Request::get('userGroup') != 'alle') {
                $data['perms'] = Request::get('userGroup');
            }
            $users = DocUsergroupConfig::searchForUser($data);
        }
        $userSetting = array();

        foreach ($users as $u) {
            $config = DocUsergroupConfig::getGroupConfig($u['user_id']);
            $foo = array();
            foreach ($u as $key => $value) {
                $foo[$key] = $value;
            }
            if (empty($config)) {
                $foo['upload'] = 'keine individuelle Einstellung';
                $foo['upload_unit'] = '';
                $foo['quota'] = 'keine individuelle Einstellung';
                $foo['quota_unit'] = '';
                $foo['forbidden'] = 0;
                $foo['area_close'] = 0;
                $foo['types'] = array();
                $foo['deleteIcon'] = 0;
                $userSetting[] = $foo;
            } else {
                $foo['config_id'] = $config['id'];
                $foo['upload'] = $this->sizeInUnit($config['upload_quota'], $config['upload_unit']);
                $foo['upload_unit'] = $config['upload_unit'];
                $foo['quota'] = $this->sizeInUnit($config['quota'], $config['quota_unit']);
                $foo['quota_unit'] = $config['quota_unit'];
                $foo['forbidden'] = $config['forbidden'];
                $foo['area_close'] = $config['area_close'];
                $foo['types'] = $config['types'];
                $foo['deleteIcon'] = 1;
                $userSetting[] = $foo;
            }
        }
        $viewData['users'] = $userSetting;
        $this->viewData = $viewData;
    }
    
    public function sizeInByte($size, $unit)
    {
        $byte = 0;
        switch ($unit) {
            case 'kB' :
                $byte = $size * 1024;
                break;
            case 'MB':
                $byte = $size * 1048576;
                break;
            case'GB':
                $byte = $size * 1073741824;
                break;
            case 'TB':
                $byte = $size * 1099511627776;
                break;
        }
        return $byte;
    }
    
    /*
     * TODO
     * Wenn relsize() aus functions.php mit float umgehen kann
     * dann kommt diese Funktion raus
     */
    public function sizeInUnit($byte, $unit) 
    {
        $size = 0;
        switch ($unit) {
            case 'kB' :
                $size = $byte / 1024;
                break;
            case 'MB':
                $size = $byte / 1048576;
                break;
            case'GB':
                $size = $byte / 1073741824;
                break;
            case 'TB':
                $size = $byte / 1099511627776;
                break;
        }
        return $size;
    }
    
    //Infobox erstellen mit Navigation ->erweiterbarkeit
    function getInfobox()
    {
        $sidebar = Sidebar::get();
        $sidebar->setImage('sidebar/files-sidebar.png');
        $sidebar->setTitle(_('Persönlicher Dateibereich'));
        
        $widget = new ActionsWidget();

        $widget->addLink(_('Neue Gruppeneinstellungen'),
                         $this->url_for('document/administration/edit/0/true'),
                         'icons/16/black/add')
               ->asDialog();
        $widget->addLink(_('Neue individuelle Einstellungen'),
                         $this->url_for('document/administration/individual'),
                         'icons/16/black/add');

        $sidebar->addWidget($widget);
    }
}
