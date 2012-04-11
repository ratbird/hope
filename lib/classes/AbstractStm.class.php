<?
# Lifter002: DONE - no html
# Lifter003: TEST - Seriously! Due to missing test case this is untested yet thorougly proofread
# Lifter007: TODO
# Lifter010: DONE - no html

require_once ("lib/functions.php");
define('LANGUAGE_ID',"09c438e63455e3e1b3deabe65fdbc087");

class  AbstractStm {
    
    var $id;
    var $id_number;
    var $title;
    var $subtitle;
    var $topics;
    var $aims;
    var $hints;
    var $duration;
    var $credits;
    var $workload;
    var $turnus;
    var $homeinst;
    var $elements = array();
    var $assigns = array();
    var $is_new;
    var $msg;

    function GetInstance($id = false, $refresh_cache = false){
        
        static $abs_stm_object_pool;
        
        if ($id){
            if ($refresh_cache){
                $abs_stm_object_pool[$id] = null;
            }
            if (is_object($abs_stm_object_pool[$id]) && $abs_stm_object_pool[$id]->getId() == $id){
                return $abs_stm_object_pool[$id];
            } else {
                $abs_stm_object_pool[$id] = new AbstractStm($id);
                return $abs_stm_object_pool[$id];
            }
        } else {
            return new AbstractStm(false);
        }
    }
        
    function &GetAbsStmTypes()
    {
        static $abs_stm_types;

        if ($abs_stm_types == null) {
            $query = "SELECT stm_type_id, name, abbrev
                      FROM stm_abstract_types
                      WHERE lang_id = ?
                      ORDER BY name";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(LANGUAGE_ID));
            $abs_stm_types = $statement->fetchGrouped();
        }

        return $abs_stm_types;
    }
    
    function &GetStg($abschl=false)
    {
        static $stgaenge;
        static $last_abschl;

        if ($stgaenge == null || $abschl != $last_abschl) {
            if ($abschl) {
                $query = "SELECT DISTINCT stg, dtxt FROM his_abstgv WHERE abschl = ? ORDER BY dtxt";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($abschl));
            } else {
                $query = "SELECT DISTINCT stg, dtxt FROM his_abstgv ORDER BY dtxt";
                $statement = DBManager::get()->query($query);
            }
            $stgaenge = $statement->fetchGrouped(PDO::FETCH_COLUMN);
        }
        $last_abschl = $abschl;     
        return $stgaenge;
    }

    function &GetPversions($abschl = false, $stg = false)
    {
        if ($abschl) {
            $query = "SELECT his_pvers.pvers, his_pvers.dtxt
                      FROM his_pvers
                      INNER JOIN his_abstgv ON (his_pvers.pvers = his_abstgv.pversion)
                      WHERE abschl = ? AND stg = ?
                      ORDER BY his_pvers.dtxt";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($abschl, $stg));
        } else {
            $query = "SELECT pvers, dtxt FROM his_pvers ORDER BY dtxt";
            $statement = DBManager::get()->query($query);
        }
        $pversions = $statement->fetchGrouped(PDO::FETCH_COLUMN);
        return $pversions;
    }
        
    function &GetAbsStms($abschl, $stg, $check_homeinst = false)
    {
        $parameters = array(LANGUAGE_ID, $abschl, $stg);
        $query_add  = '';

        if ($check_homeinst) {
            array_push($parameters, $GLOBALS['user']->id, $GLOBALS['user']->id);

            $query_add = "AND homeinst IN (
                            SELECT institut_id
                            FROM user_inst
                            WHERE user_id = ? AND inst_perms = 'admin'

                            UNION DISTINCT

                            SELECT c.institut_id
                            FROM user_inst AS a
                            INNER JOIN Institute AS b
                              ON (a.Institut_id = b.Institut_id AND b.Institut_id = b.fakultaets_id)
                            INNER JOIN Institute AS c
                              ON (c.fakultaets_id = b.Institut_id AND c.fakultaets_id != c.Institut_id)
                            WHERE user_id = ? AND inst_perms = 'admin'
                          )";
        }

        $query = "SELECT title, sat.stm_abstr_id
                  FROM stm_abstract_assign AS saa
                  INNER JOIN stm_abstract AS sa USING (stm_abstr_id)
                  INNER JOIN stm_abstract_text AS sat
                    ON (sa.stm_abstr_id = sat.stm_abstr_id AND lang_id = ?)
                  WHERE abschl = ? AND stg = ? {$query_add}
                  ORDER BY title";
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);

        return $statement->fetchGrouped(PDO::FETCH_COLUMN);
    }
        
    function &GetAbschluesse()
    {
        static $abschl;

        if ($abschl == null) {
            $query = "SELECT abint, dtxt FROM his_abschl ORDER BY dtxt";
            $statement = DBManager::get()->query($query);
            $abschl = $statement->fetchGrouped(PDO::FETCH_COLUMN);
        }
        return $abschl;
    }
        
    function &CheckAbstgv($abschl, $stg)
    {
        $query = "SELECT 1 FROM his_abstgv WHERE abschl = ? AND stg = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($abschl, $stg));
        $present = $statement->fetchColumn();

        return $present
            ? -1
            : 'Diese Kombination von Studiengang und Abschluss existiert nicht.';
    }

    /**
    * Constructor
    *
    * Pass nothing to create a abstract stm, or the id from an existing stm to change or delete
    * @access   public
    * @param    string  $abs_stm_id the seminar which should be retrieved
    */
    function AbstractStm($id = FALSE) {
        
        if ($id) {
            $this->id = $id;
            $this->restore();
        }
        if (!$this->id) {
            $this->id=$this->createId();
            $this->is_new = TRUE;
        }
        
    }

    /**
    *
    * creates an new id for this object
    * @access   private
    * @return   string  the unique id
    */
    function createId() {
        return md5(uniqid("AbstractStm",1));
    }

    
    function getId() {
        return $this->id;
    }
    
    function getIdNumber() {
        return $this->id_number;
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
    
    function getAims() {
        return $this->aims;
    }
    
    function getHints() {
        return $this->hints;
    }

    function getDuration() {
        return $this->duration;
    }
    
    function getCredits() {
        return $this->credits;
    }

    function getWorkload() {
        return $this->workload;
    }

    function getTurnus() {
        return $this->turnus;
    }

    function getHomeinst() {
        return $this->homeinst;
    }
    
    /**
    * restore the data
    *
    * the complete data of the object will be loaded from the db
    * @access   public
    * @return   booelan succesful restore?
    */
    
    function restore()
    {
        $query = "SELECT id_number, duration, credits, workload, turnus, title, subtitle, topics, aims, hints, homeinst
                  FROM stm_abstract
                  INNER JOIN stm_abstract_text USING (stm_abstr_id)
                  WHERE stm_abstr_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->id));
        $values = $statement->fetch(PDO::FETCH_ASSOC);

        if ($values) {
            $this->setValues($values);

            $query = "SELECT stm_type_id, abschl, stg, pversion, earliest, latest, recommed
                      FROM stm_abstract_assign
                      WHERE stm_abstr_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($thid->id));
            $this->assigns = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($this->assigns)) {
                $query = "SELECT element_id FROM stm_abstract_elements WHERE stm_abstr_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($this->id));

                while ($element_id = $statement->fetchColumn()) {
                    $temp = AbstractStmElement::GetInstance($element_id);
                    $this->elements[$temp->getGroup()][$temp->getPosition()] = $temp;
                }
            }
        }

        return FALSE;
    }

    function store($replace = false)
    {
        // leider kein Rollback bei MY_ISAM möglich, also erstmal ohne Sicherheiten ... :(
        if ($replace) {
            $this->delete();
        }

        $query = "INSERT INTO stm_abstract (stm_abstr_id, id_number, duration, credits,
                                            workload, turnus, mkdate, chdate, homeinst)
                  VALUES (?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->id,
            $this->id_number,
            $this->duration,
            $this->credits,
            $this->workload,
            $this->turnus,
            $this->homeinst,
        ));

        if (!$statement->rowCount()) {
            $this->msg[] = array('error', _('DB-Error beim Anlegen des Moduls'));
            return;
        }

        $query = "INSERT INTO stm_abstract_text (stm_abstr_id, lang_id, title, subtitle, topics, aims, hints)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->id,
            LANGUAGE_ID,
            $this->title,
            $this->subtitle,
            $this->topics,
            $this->aims,
            $this->hints,
        ));

        if (!$statement->rowCount()) {
            $this->msg[] = array('error', _('DB-Error beim Anlegen der Felder des Moduls'));
        }

        foreach ($this->elements as $index => $elem_list) {
            foreach ($elem_list as $index2 => $elem) {
                $elem->store();
                if (count($elem->msg) != 0) {
                    $this->msg = array_merge($this->msg, $elem->msg);
                    $elem->msg = array();
                }
            }
        }

        $query = "INSERT INTO stm_abstract_assign (stm_abstr_id, stm_type_id, abschl, stg, pversion, earliest, latest, recommed)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $statement = DBManager::get()->prepare($query);

        $success = 0;
        foreach ($this->assigns as $index => $val) {
            $statement->execute(array(
                $this->id,
                $val['stm_type_id'],
                $val['abschl'],
                $val['stg'],
                $val['pversion'],
                $val['earliest'],
                $val['latest'],
                $val['recommed'],
            ));
            $success += $statement->rowCount();
        }
        if (!$success) {
            $this->msg[] = array('error', _('DB-Error beim zuweisen der Studiengaenge'));
        }
    }
    
    function delete()
    {
        // leider kein Rollback bei MY_ISAM möglich, also erstmal ohne Sicherheiten ... :(

        $query = "DELETE FROM stm_abstract_elements WHERE stm_abstr_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->id));
        if (!$statement->rowCount()) {
            $this->msg[] = array('error', _('DB-Error beim Entfernen der Bestandteile'));
        }

        $query = "DELETE FROM stm_abstract_assign WHERE stm_abstr_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->id));
        if (!$statement->rowCount()) {
            $this->msg[] = array('error', _('DB-Error beim Entfernen der Studiengaenge'));
        }

        $query = "DELETE FROM stm_abstract_text WHERE stm_abstr_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this-id));
        if (!$statement->rowCount()) {
            $this->msg[] = array('error', _('DB-Error beim Entfernen der Textfelder'));
        }

        $query = "DELETE FROM stm_abstract WHERE stm_abstr_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this-id));
        if (!$statement->rowCount()) {
            $this->msg[] = array('error', _('DB-Error beim Entfernen des Moduls'));
        }
    }
    
    function setValues($val_array) {
        foreach($val_array as $name => $value) {
            $this->$name = $value;
        }
    }

    function getValues() {
        return array(   'id' => $this->id,
                        'id_number' => $this->id_number,
                        'title' => $this->title,
                        'subtitle' => $this->subtitle,
                        'topics' => $this->topics,
                        'aims' => $this->aims,
                        'hints' => $this->hints,
                        'duration' => $this->duration,
                        'credits' => $this->credits,
                        'workload' => $this->workload,
                        'turnus' => $this->turnus,
                        'homeinst' => $this->homeinst
        );
    
    }
            
    function checkValues() {
        $required = array('title','topics','aims','duration','credits','workload');
        
        foreach($required as $name){
            if (!isset($this->$name) || $this->$name == '') {
                $msg[] = array('error', _("Es wurden nicht alle notwendigen Felder ausgef&uuml;llt!"));
                break;
            }
        }
        
        return $msg;
    }

    function checkElements() {
    
        if (count($this->elements) == 0)
            $msg[] = array('error', _("Es muss mindestens eine Modulbestandteil-Kombination zugewiesen werden"));
        
        foreach ($this->elements as $index => $elements) {
            $workloadsum = 0;
            if (is_array($elements))
            {
                foreach($elements as $elem)
                    $workloadsum += $elem->getWorkload();
            }
            
            if ($workloadsum != $this->workload)        
                    $msg[] = array('error', sprintf(_("Die Summe des Arbeitsaufwandes der einzelnen Veranstaltungen in Kombination %s (%s) entspricht nicht dem Arbeitsaufwand des ganzen Moduls (%s)!"), $index+1, $workloadsum, $this->workload));
        }       
        return $msg;
    }
    
    function addElement($elem, $block) {
            $this->elements[$block][] = $elem;
    }
            
    function checkAssigns() {
    
        // mindestens eins
        if (count($this->assigns) == 0)
            $msg[] = array('error', sprintf(_("Es muss mindestens eine Abschluss-Studiengang-Modultyp-Kombination zugewiesen werden.")));
        
        // nicht zweimal das gleiche
        for ($i = 0; $i<count($this->assigns); $i++) {
            for ($j = ($i+1); $j<count($this->assigns); $j++) {
                if  ($this->assigns[$i]['stm_abstr_id'] == $this->assigns[$j]['stm_abstr_id'] &&
                $this->assigns[$i]['abschl'] == $this->assigns[$j]['abschl'] &&
                $this->assigns[$i]['stg'] == $this->assigns[$j]['stg'] &&
                $this->assigns[$i]['pversion'] == $this->assigns[$j]['pversion'])
                    $msg[] = array('error', sprintf(_("Die %s. und %s. Zeile stimmen &uuml;berein"), $i+1, $j+1));                  
            }
        }
        
        return $msg;
    }   
}
            
?>
