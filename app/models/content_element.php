<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

require_once 'lib/functions.php';
require_once 'lib/visual.inc.php';
require_once 'lib/classes/Seminar.class.php';
require_once 'app/models/content_element.php';

abstract class StudipContentElement {
    
    protected $id;
    protected $data;
    
    function __construct($id){
        $this->id = $id;
        $this->restore();
    }
    
    function exists(){
        return $this->id !== null;
    }
    
    abstract function restore();
    
    abstract function isAccessible($user_id);
    
    abstract function getAbstract();
    
    abstract function getTitle();
    
    function getAbstractHtml(){
        return formatready($this->getAbstract());
    }
}

class StudipContentElementForum extends StudipContentElement {
    
    function restore(){
        $query = "SELECT * FROM px_topics WHERE topic_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->id));
        $data = $statement->fetch(PDO::FETCH_ASSOC);

        if($data){
            $this->data = $data;
            $this->id = $data['topic_id'];
        } else {
            $this->id = null;
        }
        return $this->exists();
    }
    
    function isAccessible($user_id){
        if($this->exists()){
            $type = get_object_type($this->data['Seminar_id']);
            if($type == 'sem'){
                $seminar = Seminar::GetInstance($this->data['Seminar_id']);
                if ($seminar->isPublic()) {
                    return true;
                } else if ($seminar->read_level == 1){
                    return $user_id && $user_id != 'nobody';
                } else {
                    return is_object($GLOBALS['perm']) && $GLOBALS['perm']->have_studip_perm('user', $this->data['Seminar_id'], $user_id);
                }
            } else {
                return true;
            }
        }
        return false;
    }
    
    function getAbstract(){
        return $this->data['description'];
    }
    
    function getTitle(){
        return $this->data['name'];
    }
    
    function getAbstractHtml(){
        include_once 'lib/forum.inc.php';
        $this->data['id'] = $this->id;
        return formatready(forum_parse_edit($this->getAbstract(), true)).
            "<div>".forum_get_buttons($this->data)."</div>";
    }
}

class StudipContentElementMessage extends StudipContentElement {
    
    function restore(){
        $query = "SELECT * FROM message WHERE message_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statment->execute(array($this->id));
        $data = $statement->fetch(PDO::FETCH_ASSOC);

        if($data){
            $this->data = $data;
            $this->id = $data['message_id'];
        } else {
            $this->id = null;
        }
        return $this->exists();
    }
    
    function isAccessible($user_id){
        if($this->exists()){
            $db = DBManager::Get();
            $st = $db->prepare("SELECT message_id FROM message_user WHERE user_id=? AND message_id=? LIMIT 1");
            $st->execute(array($user_id,$this->id));
            return $st->fetchColumn();
        }
        return false;
    }
    
    function getAbstract(){
        return $this->data['message'];
    }
    
    function getTitle(){
        return $this->data['subject'];
    }
    
}

class StudipContentElementContact extends StudipContentElement {
    
    function restore(){
        $query = "SELECT * FROM contact WHERE contact_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->id));
        $data = $statement->fetch(PDO::FETCH_ASSOC);

        if($data){
            $this->data = $data;
            $this->id = $data['contact_id'];
        } else {
            $this->id = null;
        }
        return $this->exists();
    }
    
    function isAccessible($user_id){
        if($this->exists()){
            return $this->data['owner_id'] == $user_id;
        }
        return false;
    }
    
    function getAbstract(){
        return get_fullname($this->data['user_id']);
    }
    
    function getTitle(){
        return get_fullname($this->data['user_id'],'no_title_rev');
    }
    
    function getAbstractHtml(){
        include_once 'lib/contact.inc.php';
        $description = '<div style="padding:5px;">';
        $userinfo = GetUserInfo($this->data['user_id']);
        if (is_array($userinfo)) {
            foreach($userinfo as $key => $value){
                $description .= "<b>".htmlready($key).":</b> ".$value."<br>";
            }
        }
        $userinstinfo = GetInstInfo($this->data['user_id']);
        if (is_array($userinstinfo)) {
            foreach($userinstinfo as $instinfo) {
                foreach($instinfo as $key => $value){
                    $description .= "<b>".htmlready($key).":</b> ".$value."<br>";
                }
            }
        }
        $extra = GetExtraUserinfo ($this->id);
        if (is_array($extra)) {
            foreach($extra as $key => $value){
                $description .= "<b>".htmlready($key).":</b> ".formatready($value)."<br>";
            }
        }
        return $description . '</div>';
    }
}
    
class StudipContentElementCalendarevent extends StudipContentElement {
    
    private $event;
    
    function restore(){
        global $RELATIVE_PATH_CALENDAR,$CALENDAR_DRIVER;
        require_once 'lib/calendar/lib/DbCalendarEvent.class.php';
        require_once 'lib/calendar/lib/SeminarEvent.class.php';
        
        $event = new DbCalendarEvent();
        if( event_restore($this->id, $event)){
            $this->event = $event;  
        } else {
            $event = new SeminarEvent($this->id);
            if($event->getSeminarId()){
                $this->event = $event;
            }
        }
        return $this->exists();
    }
    
    function exists(){
        return $this->event !== null;
    }
    
    function isAccessible($user_id){
        return $this->exists();
    }
    
    function getAbstract(){
        $text = '';
        if($this->exists()){
            $aterm = $this->event;
            $text = '**' . _("Zusammenfassung:") . '** '
            . $aterm->getTitle() . "\n--\n";
            if (strtolower(get_class($aterm)) == 'seminarevent') {
                $text .= '**' . _("Veranstaltung:") . '** '
                . $aterm->getSemName() . "\n";
            }
            if ($aterm->getDescription()) {
                $text .= '**' . _("Beschreibung:") . '** '
                . $aterm->getDescription() . "\n";
            }
            if ($categories = $aterm->toStringCategories()) {
                $text .= '**' . _("Kategorie:") . '** '
                . $categories . "\n";
            }
            if ($aterm->getProperty('LOCATION')) {
                $text .= '**' . _("Ort:") . '** '
                . $aterm->getProperty('LOCATION') . "\n";
            }
            if (strtolower(get_class($aterm)) != 'seminarevent') {
                $text .= '**' . _("Priorität:") . '** '
                . $aterm->toStringPriority() . "\n";
                $text .= '**' . _("Zugriff:") . '** '
                . $aterm->toStringAccessibility() . "\n";
                $text .= '**' . _("Wiederholung:") . '** '
                . $aterm->toStringRecurrence() . "\n";
            }
        }
        return $text;
    }
    
    function getTitle(){
        return $this->exists() ? $this->event->toStringDate('SHORT_DAY') : '';
    }

}
