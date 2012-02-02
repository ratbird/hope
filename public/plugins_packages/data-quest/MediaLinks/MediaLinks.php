<?php
require_once 'lib/classes/SimpleORMap.class.php';


class MediaLinks extends StudipPlugin implements StandardPlugin {

    public $config = array();
    public $seminar_id;

    function __construct() {
        parent::__construct();
        $this->me = get_class($this);
        $this->restoreConfig();
       
        if($this->isVisible() && Navigation::hasItem("/course/")) {
            $navi =  new Navigation($this->getDisplayTitle(), PluginEngine::getUrl($this,array(),'index'));
            $navi->setImage('icons/16/white/group');
            $navi->setActiveImage('icons/16/black/group');
            Navigation::addItem('/course/' . $this->me,$navi);
        }
    }

    function isVisible() {
        $seminar_id = Request::option('auswahl', $_SESSION['SessionSeminar']);
        $this->seminar_id = $seminar_id;
        return ($seminar_id
            && get_object_type($seminar_id, array('sem')) === 'sem'
           // && in_array(Seminar::getInstance($seminar_id)->institut_id, $this->config['INSTITUT_IDS'])
            && SeminarCategories::GetBySeminarId($seminar_id)->id == 1);
            //&& $GLOBALS['perm']->have_studip_perm('dozent', $seminar_id));
    }


    function getDisplayTitle(){
        return _("Media Links");
    }

    function restoreConfig() {
        $config = Config::getInstance();
        foreach($config->getFields('global', null, $this->me) as $field) {
            $fieldname = trim(strstr($field, '_'),'_');
            if ($fieldname) {
                if (in_array($fieldname, words('INSTITUT_IDS MAIL_RECIPIENTS'))) {
                    $this->config[$fieldname] = array_map('trim', preg_split('/[\s,]+/' ,$config->$field, -1, PREG_SPLIT_NO_EMPTY));
                } else {
                    $this->config[$fieldname] = trim($config->$field);
                }
            }
        }
    }

    /**
    * This method dispatches and displays all actions. It uses the template
    * method design pattern, so you may want to implement the methods #route
    * and/or #display to adapt to your needs.
    *
    * @param  string  the part of the dispatch path, that were not consumed yet
    *
    * @return void
    */
    function perform($unconsumed_path) {
        if(!$unconsumed_path){
            header("Location: " . PluginEngine::getUrl($this), 302);
            return false;
        }
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root, null, 'index');
        $dispatcher->current_plugin = $this;
        $dispatcher->dispatch($unconsumed_path);

    }
    public  function getIconNavigation($course_id, $last_visit) {
       return NULL;
    }
    public function getInfoTemplate($course_id) {
        return NULL;
    }

}
