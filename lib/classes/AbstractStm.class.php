<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

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

        if ($abs_stm_types == null)
        {
            $db = new DB_Seminar;
            $abs_stm_types = array();   
            $db->query("SELECT * FROM stm_abstract_types WHERE lang_id='".LANGUAGE_ID."' ORDER BY name");       

            if ($db->num_rows()) {
                while ($db->next_record()) {
                    $abs_stm_types[$db->f("stm_type_id")] = array('name' => $db->f("name"), 'abbrev' => $db->f("abbrev"));  
                }
            }
        }
        return $abs_stm_types;
    }
    
    function &GetStg($abschl=false)
    {
        static $stgaenge;
        static $last_abschl;

        if ($stgaenge == null || $abschl != $last_abschl)
        {
            $stgaenge = array();    
            $db = new DB_Seminar;
            if ($abschl) 
                $db->query("SELECT DISTINCT stg, dtxt AS name FROM his_abstgv WHERE abschl='$abschl' ORDER BY dtxt");       
            else 
                $db->query("SELECT DISTINCT stg , dtxt AS name FROM his_abstgv ORDER BY dtxt");     

            while ($db->next_record()) {
                $stgaenge[$db->f('stg')] = $db->f('name');  
            }
        }
        $last_abschl = $abschl;     
        return $stgaenge;
    }

    function &GetPversions($abschl = false, $stg = false)
    {
//      var_dump($abschl); echo "<br><br>";
//      var_dump($stg); echo "<br><br>";
        $pversions = array();   
        $db = new DB_Seminar;

        if ($abschl)
            $db->query("SELECT his_pvers.pvers AS pvers, his_pvers.dtxt AS name FROM his_pvers INNER JOIN his_abstgv ON his_pvers.pvers=his_abstgv.pversion WHERE abschl='$abschl' AND stg='$stg' ORDER BY his_pvers.dtxt");        
        else 
            $db->query("SELECT pvers, dtxt AS name FROM his_pvers ORDER BY dtxt");

        while ($db->next_record()) {
            $pversions[$db->f('pvers')] = $db->f('name');   
        }
        return $pversions;
    }
        
    function &GetAbsStms($abschl, $stg, $check_homeinst = false)
    {
        $stms = array();    
        $db = new DB_Seminar;
        if($check_homeinst){
            $cs = "AND homeinst IN (
                        SELECT institut_id
                        FROM user_inst a
                        WHERE user_id = '" . $GLOBALS['user']->id . "'
                        AND inst_perms = 'admin'
                        UNION DISTINCT
                        SELECT c.institut_id
                        FROM user_inst a
                        INNER JOIN Institute b ON ( a.Institut_id = b.Institut_id
                        AND b.Institut_id = b.fakultaets_id )
                        INNER JOIN Institute c ON ( c.fakultaets_id = b.institut_id
                        AND c.fakultaets_id != c.institut_id )
                        WHERE user_id = '" . $GLOBALS['user']->id . "'
                        AND inst_perms = 'admin'
                        )";
        }
        $db->query("SELECT stm_abstract_text.stm_abstr_id AS id, title 
                    FROM stm_abstract_assign 
                    INNER JOIN stm_abstract ON stm_abstract.stm_abstr_id = stm_abstract_assign.stm_abstr_id
                    INNER JOIN stm_abstract_text ON stm_abstract.stm_abstr_id=stm_abstract_text.stm_abstr_id AND lang_id='".LANGUAGE_ID."'
                    WHERE abschl='$abschl' AND stg='$stg' $cs ORDER BY title");     
        
        while ($db->next_record()) {
            $stms[$db->f('title')] = $db->f('id');  
        }
        return $stms;
    }
        
    function &GetAbschluesse()
    {
        static $abschl;

        if ($abschl == null)
        {
            $abschl = array();  
            $db = new DB_Seminar;
            $db->query("SELECT abint AS abschl, dtxt as name FROM his_abschl ORDER BY dtxt") ;      

            while ($db->next_record()) {
                $abschl[$db->f('abschl')] = $db->f('name'); 
            }
        }
        return $abschl;
    }
        
    function &CheckAbstgv($abschl, $stg)
    {
        $db = new DB_Seminar;
        $db->query("SELECT * FROM his_abstgv WHERE abschl = '$abschl' AND stg = '$stg'");       

        if (!$db->num_rows()) 
            return "Diese Kombination von Studiengang und Abschluss existiert nicht.";

        return -1;  
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
    * @access   publihc
    * @return   booelan succesful restore?
    */
    
    function restore() {
    
        $db = new DB_Seminar;
        
        $db->query("SELECT id_number, duration, credits, workload, turnus, title, subtitle, topics, aims, hints, homeinst FROM stm_abstract INNER JOIN stm_abstract_text ON stm_abstract.stm_abstr_id=stm_abstract_text.stm_abstr_id WHERE stm_abstract.stm_abstr_id='$this->id'");

        if ($db->next_record()) {
            $vals = array('id_number' => $db->f('id_number'), 
                'duration' => $db->f('duration'), 
                'credits' => $db->f('credits'), 
                'workload' => $db->f('workload'), 
                'turnus' => $db->f('turnus'), 
                'title' => $db->f('title'), 
                'subtitle' => $db->f('subtitle'), 
                'topics' => $db->f('topics'), 
                'aims' => $db->f('aims'),
                'hints' => $db->f('hints'),
                'homeinst' => $db->f('homeinst')
                );

            $this->setValues($vals);
            
            $db->query("SELECT stm_type_id, abschl, stg, pversion, earliest, latest, recommed FROM stm_abstract_assign WHERE stm_abstr_id='$this->id'");
            if ($db->num_rows()) {
                while ($db->next_record()) {
                    $this->assigns[] = array(
                    "stm_type_id" => $db->f("stm_type_id"),
                    "abschl" => $db->f("abschl"),
                    "stg" => $db->f("stg"),
                    "pversion" => $db->f("pversion"),
                    "earliest" => $db->f("earliest"),
                    "latest" => $db->f("latest"),
                    "recommed" => $db->f("recommed")                    
                    );
                }
                
                $db->query("SELECT element_id FROM stm_abstract_elements WHERE stm_abstr_id='$this->id'");
                if ($db->num_rows()) {
                    while ($db->next_record()) {
                        $temp_elem = AbstractStmElement::GetInstance($db->f("element_id"));
                        $this->elements[$temp_elem->getGroup()][$temp_elem->getPosition()] = $temp_elem;            
                    }
                }
            }
        }
        return FALSE;
    }

    function store($replace = false) {
        // leider kein Rollback bei MY_ISAM möglich, also erstmal ohne Sicherheiten ... :(
        $db = new DB_Seminar;
        $de_id = LANGUAGE_ID;
        if ($replace)
            $this->delete();
        
        $now = time();  
        $db->query("INSERT INTO stm_abstract SET
                stm_abstr_id = '$this->id', 
                id_number = '$this->id_number',
                duration = '$this->duration',
                credits = '$this->credits',
                workload = '$this->workload',
                turnus = '$this->turnus',
                mkdate = '$now', 
                chdate = '$now',
                homeinst = '$this->homeinst'
                ");
            if (!$db->affected_rows()) {
                $this->msg[] = array('error', _("DB-Error beim Anlegen des Moduls"));
                return;
            }
                
            $db->query("INSERT INTO stm_abstract_text SET
                stm_abstr_id = '$this->id', 
                lang_id = '$de_id',
                title = '$this->title',
                subtitle = '$this->subtitle',
                topics = '$this->topics',
                aims = '$this->aims',
                hints = '$this->hints'
                ");
            if (!$db->affected_rows()) {
                $this->msg[] = array('error', _("DB-Error beim Anlegen der Felder des Moduls"));
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
            foreach ($this->assigns as $index => $val) {
                $db->query("INSERT INTO stm_abstract_assign SET
                        stm_abstr_id = '$this->id',
                        stm_type_id = '" . $val['stm_type_id'] . "',
                        abschl = '" . $val['abschl']. "',
                        stg = '" . $val['stg'] . "',
                        pversion = '" . $val['pversion']. "',
                        earliest = '" . $val['earliest'] . "',
                        latest = '" . $val['latest'] . "',
                        recommed = '" . $val['recommed'] . "'
                        ");
            }
            if (!$db->affected_rows()) {
                $this->msg[] = array('error',_("DB-Error beim zuweisen der Studiengaenge"));
            }
    }
    
    function delete() {
        // leider kein Rollback bei MY_ISAM möglich, also erstmal ohne Sicherheiten ... :(
        $db = new DB_Seminar;
            $db->query("DELETE FROM stm_abstract_elements WHERE stm_abstr_id = '$this->id'");
            if (!$db->affected_rows()) {
                $this->msg[] = array('error', _("DB-Error beim Entfernen der Bestandteile"));
            }
            $db->query("DELETE FROM stm_abstract_assign WHERE stm_abstr_id = '$this->id'");
            if (!$db->affected_rows()) {
                $this->msg[] = array('error', _("DB-Error beim Entfernen der Studiengaenge"));
            }
            $db->query("DELETE FROM stm_abstract_text WHERE stm_abstr_id = '$this->id'");
            if (!$db->affected_rows()) {
                $this->msg[] = array('error', _("DB-Error beim Entfernen der Textfelder"));
            }
            $db->query("DELETE FROM stm_abstract WHERE stm_abstr_id = '$this->id'");
            if (!$db->affected_rows()) {
                $this->msg[] = array('error', _("DB-Error beim Entfernen des Moduls"));
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
