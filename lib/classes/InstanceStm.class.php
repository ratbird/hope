<?
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
define('LANGUAGE_ID','09c438e63455e3e1b3deabe65fdbc087');

require_once 'lib/functions.php';
require_once 'lib/classes/SemesterData.class.php';


class  InstanceStm
{
    var $stm_instance_id;
    var $stm_abstr_id;
    var $title;
    var $subtitle;
    var $topics;    
    var $hints; 
    var $homeinst;  
    var $responsible;   
    var $creator;   
    var $semester_id;   
    var $complete;
    var $msg;
    var $elements;
    
    function GetInstance($id = false, $refresh_cache = false){
        
        static $inst_stm_object_pool;
        
        if ($id){
            if ($refresh_cache){
                $inst_stm_object_pool[$id] = null;
            }
            if (is_object($inst_stm_object_pool[$id]) && $inst_stm_object_pool[$id]->getId() == $id){
                return $inst_stm_object_pool[$id];
            } else {
                $inst_stm_object_pool[$id] = new InstanceStm($id);
                return $inst_stm_object_pool[$id];
            }
        } else {
            return new InstanceStm(false);
        }
    }

    /**
    * Constructor
    *
    * Pass nothing to create a abstract stm, or the id from an existing stm to change or delete
    * @access   public
    * @param    string  $inst_stm_id    the stm which should be retrieved
    */
    function InstanceStm($id = FALSE) {
        if ($id) {
            $this->stm_instance_id = $id;
            $this->restore();
        }
        if (!$this->stm_instance_id) {
            $this->stm_instance_id=$this->createId();
        }
        
    }

    /**
    *
    * creates an new id for this object
    * @access   private
    * @return   string  the unique id
    */
    function createId() {
        return md5(uniqid("InstanceStm",1));
    }
    
    function getId() {
        return $this->stm_instance_id;
    }
    
    /**
    * restore the data
    *
    * the complete data of the object will be loaded from the db
    * @access   public
    * @return   booelan succesful restore?
    */
    /// TODO noch nicht richtig implementiert
    function restore() 
    {
        $query = "SELECT stm_abstr_id, semester_id, homeinst, creator, responsible, complete,
                         title, subtitle, topics, hints 
                  FROM stm_instances
                  NATURAL JOIN stm_instances_text 
                  WHERE stm_instances.stm_instance_id = ?";
        $statemtent = DBManager::get()->prepare($query);
        $statement->execute(array($this->stm_instance_id));
        $vals = $statement->fetch(PDO::FETCH_ASSOC);

        if ($vals) {
            $this->setValues($vals);

            $query = "SELECT elementgroup, position, element_id, sem_id 
                      FROM stm_instances_elements AS a
                      INNER JOIN stm_abstract_elements AS b USING (element_id)
                      WHERE stm_instance_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->stm_instance_id));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $element = array(
                    'element_id' => $row['element_id'],
                    'sem_id'     => $row['sem_id']
                );
                $this->addElement($element, $row['elementgroup'], $row['position']);                    
            }
        }
        return FALSE;
    }

    function store($replace = false)
    {
        // leider kein Rollback bei MY_ISAM möglich, also erstmal ohne Sicherheiten ... :(
        $de_id = LANGUAGE_ID;

        if ($replace) {
            $this->delete();
        }
        
        if ($this->stm_abstr_id) {
            $query = "INSERT INTO stm_instances
                      SET stm_instance_id = ?, stm_abstr_id = ?, semester_id = ?,
                          lang_id = ?, homeinst = ?, responsible = ?,
                          creator = ?, complete = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->stm_instance_id,
                $this->stm_abstr_id,
                $this->semester_id,
                $de_id,
                $this->homeinst,
                $this->responsible,
                $this->creator,
                $this->complete
            ));
            if ($statement->rowCount() == 0) {
                $msg[] = array('error', _('DB-Error'));
            }

            $query = "INSERT INTO stm_instances_text
                      SET stm_instance_id = ?, lang_id = ?, title = ?,
                          subtitle = ?, topics = ?, hints = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->stm_instance_id,
                $de_id,
                $this->subtitle,
                $this->topics,
                $this->hints
            ));
            if ($statement->rowCount() == 0) {
                $msg[] = array('error', _('DB-Error'));
            }
            
            if ($this->elements) {
                $query = "INSERT INTO stm_instances_elements
                          SET stm_instance_id = ?, element_id = ?, sem_id = ?";
                $statement = DBManager::get()->prepare($query);

                foreach ($this->elements as $group => $elementgroup) {
                    foreach ($elementgroup as $position => $elements) {
                        foreach ($elements as $element) {
                            $statement->execute(array(
                                $this->stm_instance_id,
                                $element['element_id'],
                                $element['sem_id']
                            ));
                            if ($statement->rowCount() == 0) {
                                $msg[] = array('error', _('DB-Error'));
                            }
                        }
                    }
                }
            }
        }
        return $msg;
    }

    function delete()
    {
        // leider kein Rollback bei MY_ISAM möglich, also erstmal ohne Sicherheiten ... :(
        $query = "DELETE FROM stm_instances_elements WHERE stm_instance_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->stm_instance_id));

        $query = "DELETE FROM stm_instances_text WHERE stm_instance_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->stm_instance_id));
        if ($statement->rowCount() == 0) {
            $this->msg[] = array('error', _('DB-Error beim Entfernen der Textfelder'));
        }

        $query = "DELETE FROM stm_instances WHERE stm_instance_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->stm_instance_id));
        if ($statement->rowCount() == 0) {
            $this->msg[] = array('error', _('DB-Error beim Entfernen des Moduls'));
        }   
    }
    
    function getStmAbstrId() {
        return $this->stm_abstr_id;
    }

    function getTitle() {
        return $this->title;
    }

    function getSubtitle() {
        return $this->subtitle;
    }

    function getTopics() {
        return $this->topics;
    }

    function getHints() {
        return $this->hints;
    }

    function getSemesterId() {
        return $this->semester_id;
    }

    function getSemesterName() {
        $semesterdata = SemesterData::GetInstance();
        $semester = $semesterdata->getSemesterData($this->semester_id);
        
        return $semester['name'];
    }
    
    function getHomeinst() {
        return $this->homeinst;
    }

    function getHomeinstName()
    {
        $query = "SELECT Name FROM Institute WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->homeinst));
        return $statement->fetchColumn() ?: '';
    }

    function getResponsible() {
        return $this->responsible;
    }

    function getComplete() {
        return $this->complete;
    }

    function getCreator() {
        return $this->creator;
    }

    function getResponsibleName()
    {
        $user = User::find($this->responsible);
        return $user ? $user->getFullName('full_rev') : '';
    }

    function getCreatorName()
    {
        $user = User::find($this->creator);
        return $user ? $user->getFullName('full_rev') : '';
    }

    function setValues($val_array) {
        foreach($val_array as $name => $value) {
            $this->$name = $value;
        }
    }

    function getValues() {
        return array(   'stm_instance_id' => $this->stm_instance_id,
                        'stm_abstr_id' => $this->stm_abstr_id,
                        'title' => $this->title,
                        'subtitle' => $this->subtitle,
                        'topics' => $this->topics,
                        'hints' => $this->hints,
                        'elements' => $this->elements,
                        'semester_id' => $this->semester_id,
                        'homeinst' => $this->homeinst,
                        'complete' => $this->complete,
                        'responsible' => $this->responsible,
                        'creator' => $this->creator
        );
    
    }

    function addElement($element, $block, $position) {
            $this->elements[$block][$position][] = $element;
    }

    function removeElement($block, $position, $number) {
            $size = count ($this->elements[$block][$position]);
            for ($i=$number; $i<$size-1; $i++)
                $this->elements[$block][$position][$i] = $this->elements[$block][$position][$i+1];
            unset($this->elements[$block][$position][$size-1]); 
    }

    function checkValues() {
        $required = array('semester_id','homeinst','responsible');
        
        foreach($required as $name){
            if (!isset($this->$name) || $this->$name == '') {
                $this->msg[] = array('error', sprintf(_("Es wurden nicht alle notwendigen Felder ausgef&uuml;llt!")));
                break;
            }
        }
    }   

    function isFilled($block, $position)
    {
        return count($this->elements[$block][$position]) > 0;
    }
}
