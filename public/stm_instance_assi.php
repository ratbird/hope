<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO

require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include"lib/seminar_open.php"; // initialise Stud.IP-Session

require_once 'lib/classes/StudipForm.class.php';
require_once 'lib/classes/Table.class.php';
require_once "config.inc.php";      //wir brauchen die Seminar-Typen
require_once "lib/visual.inc.php";      //wir brauchen die Seminar-Typen
require_once "lib/classes/SemBrowse.class.php";
require_once "lib/classes/Seminar.class.php";
require_once "lib/classes/SemesterData.class.php";
require_once "lib/classes/AbstractStm.class.php";
require_once "lib/classes/AbstractStmElement.class.php";
require_once "lib/classes/InstanceStm.class.php";
require_once "lib/classes/StmInstanceAssiVisualization.class.php";

class InstanceStmControl {

    var $msg = array();
    var $abs_stm;
    var $inst_stm;
    var $cur_seminar;
    var $sel_group;
    var $sel_stm_form;
    var $stg_input_form;
    var $abs_summary_form;
    var $add_info_form;
    var $sel_elementgroup_form;
    var $fill_group_form;
    var $summary_form;
    var $sem_browse;
    var $users_found;
    var $delete_form;
    var $info_form;


    function InstanceStmControl(){
    }

    function setSelStmForm(){

        $stm_arr = $this->getMyStmInstances();
        global $perm;

        $form_fields = array();

        if ($perm->have_perm('admin')) $form_buttons = array("neuanlegen" => array('type' => 'neuanlegen', 'info' => "neues Allgemeines Modul"));
        else $form_buttons = array();

        foreach ($stm_arr as $id => $name) {
            $form_fields["$id"] = array('type' => 'NoForm', 'info' => $name);
            $form_buttons["sel_$id"] = array('type' => 'edit', 'info' => "Allgemeines Modul bearbeiten");
            if ($perm->have_perm("admin"))
                $form_buttons["del_$id"] = array('type' => 'loeschen', 'info' => "Allgemeines Modul entfernen");

            $form_buttons["info_$id"] = array('type' => 'details', 'info' => "Details des Allgemeines Modul");
        }
        if (!is_object($this->sel_stm_form)){
            $this->sel_stm_form = new StudipForm($form_fields, $form_buttons, "sel_stm_form", false);
        } else {
            $this->sel_stm_form->form_fields = $form_fields;
            $this->sel_stm_form->form_buttons = $form_buttons;
        }
        return true;
    }

    function setDeleteFormObject(){


        $form_fields = array(
        );

        $form_buttons = array('back' => array('type' => 'zurueck', 'info' => _("Zum vorherigen Formular")),
                            'continue' => array('type' => 'loeschen', 'info' => _("Das Modul loeschen")),
                            );

        if (!is_object($this->delete_form)){
            $this->delete_form = new StudipForm($form_fields, $form_buttons, "delete_form", false);
        } else {
            $this->delete_form->form_fields = $form_fields;
        }
        return true;
    }

    function setInfoFormObject(){


        $form_fields = array(
        );

        $form_buttons = array('back' => array('type' => 'zurueck', 'info' => _("Zum vorherigen Formular")),
                            );

        if (!is_object($this->info_form)){
            $this->info_form = new StudipForm($form_fields, $form_buttons, "info_form", false);
        } else {
            $this->info_form->form_fields = $form_fields;
        }
        return true;
    }

    function setStgInputFormObject($sel_abschl, $sel_stg, $sel_stm){

        $abschl = AbstractStm::GetAbschluesse();
        $abschl_arr = array();

        foreach($abschl as $abschlid => $name)
            $abschl_arr[] = array('name' => $name, 'value' => $abschlid);

        if (!$sel_abschl)
            $sel_abschl = $abschl_arr[0]['value'];

        $stgaenge = AbstractStm::GetStg($sel_abschl);
        $stg_arr = array();
        foreach($stgaenge as $stg => $name)
            $stg_arr[] = array('name' => $name, 'value' => $stg);

        if (!$sel_stg)
            $sel_stg = $stg_arr[0]['value'];

        $abs_stms = AbstractStm::GetAbsStms($sel_abschl, $sel_stg , !$GLOBALS['perm']->have_perm('root'));
        $abs_stm_arr = array();
        foreach($abs_stms as $name => $id)
            $abs_stm_arr[] = array('name' => $name, 'value' => $id);

        // Ja das stimmt so Studiengang (beamtendeutsch) = Abschluss (HIS) und Studienprogramm(beamtendeutsch) = Studiengang (HIS)
        $form_fields = array(
            'abschl_list'   =>  array('type' => 'select', 'caption' => 'Studiengang', 'info' => "", 'options' => $abschl_arr, 'default_value' => $sel_abschl),
            'stg_list'  =>  array('type' => 'select', 'caption' => 'Studienprogramm', 'info' => '', 'options' => $stg_arr),
            'abs_stm_list'  =>  array('type' => 'select', 'caption' => 'Allgemeines Modul', 'info' => '', 'options' => $abs_stm_arr)
        );

        $form_buttons = array(
                            'back' => array('type' => 'zurueck', 'info' => _("Zum vorherigen Formular")),
                            'continue' => array('type' => 'weiter', 'info' => _("Dieses Formular abschicken"))
                            );


        if (!is_object($this->abs_input_form)){
            $this->stg_input_form = new StudipForm($form_fields, $form_buttons, "abs_input_form", false);
        } else {
            $this->stg_input_form->form_fields = $form_fields;
        }
        return true;
    }

    function setAbsSummaryForm(){


        $form_fields = array();

        $form_buttons = array('back' => array('type' => 'zurueck', 'info' => _("Zum vorherigen Formular")),
                            'continue' => array('type' => 'weiter', 'info' => _("Das Modul abspeichern")),
                            );

        if (!is_object($this->abs_summary_form)){
            $this->abs_summary_form = new StudipForm($form_fields, $form_buttons, "abs_summary_form", false);
        } else {
            $this->abs_summary_form->form_fields = $form_fields;
        }
        return true;
    }

    function setAddInfoFormObject(){
        $semesterdata = new SemesterData();

        $cur_data = $semesterdata->getAllSemesterData();
        $sem_arr = array();

        foreach ($cur_data as $sem)
                $sem_arr[] = array('name' => $sem['name'], 'value' => $sem['semester_id']);

        $inst_arr = $this->getMyInst();

        if ($this->inst_stm) {
            $title = $this->inst_stm->getTitle();
            $subtitle = $this->inst_stm->getSubtitle();
            $topics = $this->inst_stm->getTopics();
            $hints = $this->inst_stm->getHints();
            $cur_sem_id = $this->inst_stm->getSemesterId();
            $cur_homeinst = $this->inst_stm->getHomeinst();
            $cur_responsible = $this->inst_stm->getResponsible();
        }
        if(!$cur_sem_id) $cur_sem_id = $_SESSION['_default_sem'];

        $form_fields = array(   'title' =>  array('type' => 'text', 'caption' => 'semesterspezifischer Titel', 'info' => 'Der Name des anzulegenden Studienmoduls kann hier nochmal ge&auml;ndert werden. Das Allgemeine Modul bleibt davon unber&uuml;hrt.', 'default_value' => $title),
                                'subtitle' =>   array('type' => 'text', 'caption' => 'semesterspezifischer Untertitel', 'info' => 'Der Untertitel des anzulegenden Studienmoduls kann hier nochmal ge&auml;ndert werden. Das Allgemeine Modul bleibt davon unber&uuml;hrt.', 'default_value' => $subtitle),
                                'topics' =>     array('type' => 'textarea', 'caption' => 'semesterspezifische Inhalte', 'info' => 'Die behandelten Themen k&ouml;nnen hier noch einmal erweitert werden. Sie kommen zu den Themen des allgemeinen Moduls hinzu.', 'default_value' => $topics),
                                'hints' =>  array('type' => 'textarea', 'caption' => 'semesterspezifische Hinweise', 'info' => 'Die behandelten Themen k&ouml;nnen hier noch einmal erweitert werden. Sie kommen zu den Themen des allgemeinen Moduls hinzu.', 'default_value' => $hints),
                                'semester_id' => array('type' => 'select', 'caption' => 'Semester', 'info' => '', 'options' => $sem_arr, 'required' => 'true', 'default_value' => $cur_sem_id),
                                'homeinst' => array('type' => 'select', 'caption' => 'Heimat-Einrichtung', 'info' => '', 'options' => $inst_arr, 'required' => 'true', 'default_value' => $cur_homeinst),
                                'search_user' => array('type' => 'text', 'caption' => 'Modulverantwortlicher', 'info' => 'Hier muss der Modulverantwortliche angegeben werden. Nutzen Sie dazu die freie Suche, die allerdings nur nach Dozenten in der ausgew&auml;hlten Heimateinrichtung sucht.', 'required' => 'true')
        );

        if (!is_array($this->users_found))
            $this->users_found = array();

        if ($cur_responsible &&
                array_search(array('name' => $this->inst_stm->getResponsibleName(),'value' => $cur_responsible), $this->users_found)==0)
            $this->users_found[] = array('name' => $this->inst_stm->getResponsibleName(),'value' => $cur_responsible);

        if ($this->users_found) {
            $form_fields['responsible'] = array('type' => 'select', 'caption' => '', 'info' => '', 'options' => $this->users_found, 'default_value' => $cur_responsible);
        }

        $form_buttons = array('back' => array('type' => 'zurueck', 'info' => _("Zum vorherigen Formular")),
                            'reset' => array('type' => 'zuruecksetzen', 'info' => _("Formularfelder leeren")),
                            'continue' => array('type' => 'weiter', 'info' => _("Dieses Formular abschicken")),
                            'search' => array('type' => 'icons/16/black/search.png', 'info' => _("Freie Suche"), 'is_picture' => 'true'),
                            'preview' => array('type' => 'icons/16/black/question-circle.png', 'info' => _("Wiki Vorschau"), 'is_picture' => 'true')
                            );


        if (!is_object($this->add_info_form)){
            $this->add_info_form = new StudipForm($form_fields, $form_buttons, "add_info_form", false);
        } else {
            $this->add_info_form->form_fields = $form_fields;
        }
        return true;
    }

    function setSelElementgroupForm(){

        $form_fields = array(
        );

        $form_buttons = array('back' => array('type' => 'zurueck', 'info' => _("Zum vorherigen Formular")),
                            'continue' => array('type' => 'weiter', 'info' => _("Dieses Formular abschicken")));

        for ($i = 0; $i < count($this->abs_stm->elements); $i++) {
            $form_buttons["sel_$i"] = array('type' => 'auswaehlen', 'info' => _("Diese Kombination benutzen"));
        }

        if (!is_object($this->sel_elementgroup_form)){
            $this->sel_elementgroup_form = new StudipForm($form_fields, $form_buttons, "sel_elementgroup_form", false);
        } else {
            $this->sel_elementgroup_form->form_fields = $form_fields;
            $this->sel_elementgroup_form->form_buttons = $form_buttons;
        }
        return true;
    }

    function setFillGroupForm(){
        global $stm_inst_data;

        $group_index = $_SESSION['stm_inst_data']["sel_group"];
        $form_fields = array(
        );

        $form_buttons = array('continue' => array('type' => 'fertigstellen', 'info' => _("Dieses Formular abschicken")));

        for ($i = 0; $i < count($this->sel_group); $i++) {
            $form_buttons["fill_$i"] = array('type' => 'zuweisen', 'info' => _("Gewaehlte Veranstaltung zuweisen"));
            for ($j = 0; $j < count($this->inst_stm->elements[$group_index][$i]); $j++) {
                $form_buttons["remove_". $i . "_" . $j] = array('type' => Assets::image_path('icons/16/blue/trash.png'), 'info' => _("Diese Veranstaltung entfernen"), 'is_picture' => 'true');
            }
        }

        if (!is_object($this->fill_group_form)){
            $this->fill_group_form = new StudipForm($form_fields, $form_buttons, "fill_group_form", false);
        } else {
            $this->fill_group_form->form_fields = $form_fields;
            $this->fill_group_form->form_buttons = $form_buttons;
        }
        return true;
    }

    function setSummaryForm(){

        if ($this->inst_stm)
            $cur_complete = $this->inst_stm->getComplete();
        else
            $cur_complete = false;

        $form_fields = array('complete' => array('type' => 'checkbox', 'caption' => 'Modul vollst&#228;ndig', 'info' => '', 'default_value' => $cur_complete));

        $form_buttons = array('back' => array('type' => 'zurueck', 'info' => _("Zum vorherigen Formular")),
                            'continue' => array('type' => 'speichern', 'info' => _("Das Modul abspeichern")),
                            );

        if (!is_object($this->summary_form)){
            $this->summary_form = new StudipForm($form_fields, $form_buttons, "summary_form", false);
        } else {
            $this->summary_form->form_fields = $form_fields;
        }
        return true;
    }

    function getMyStmInstances() {

        global $perm, $user;

        $parameters = array(
            ':lang_id' => LANGUAGE_ID,
            ':user_id' => $user->id,
        );
        if ($perm->get_perm() == 'dozent'){
            $query = "SELECT stm_instances.stm_instance_id, title
                      FROM stm_instances
                      NATURAL JOIN stm_instances_text
                      WHERE stm_instances_text.lang_id = :lang_id AND responsible = :user_id
                      ORDER BY title";
        } elseif ($perm->get_perm() == 'admin'){
            $query = "SELECT stm_instances.stm_instance_id, title
                      FROM stm_instances
                      NATURAL JOIN stm_instances_text
                      WHERE stm_instances_text.lang_id = :lang_id AND homeinst IN (
                          SELECT institut_id FROM user_inst WHERE user_id = :user_id AND inst_perms = 'admin'
                        UNION DISTINCT
                          SELECT c.institut_id
                          FROM user_inst AS a
                          INNER JOIN Institute AS b ON (a.Institut_id = b.Institut_id AND b.Institut_id = b.fakultaets_id)
                          INNER JOIN Institute AS c ON (b.Institut_id = c.fakultaets_id AND c.fakultaets_id != c.Institut_id)
                          WHERE user_id = :user_id AND inst_perms = 'admin'
                      )
                      ORDER BY title";
        } else {
            $query = "SELECT stm_instance_id, title
                      FROM stm_instances_text
                      WHERE lang_id = :lang_id
                      ORDER BY title";
            unset($parameters[':user_id']);
        }
        $statement = DBManager::get()->prepare($query);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->execute();

        return $statement->fetchGrouped(PDO::FETCH_COLUMN);
    }

    function show(){

        static $vis;
        global $user, $perm;

        //$GLOBALS['sess']->register('stm_inst_data');

        global $stm_inst_data;

        // erstmal alle Daten wieder herstellen

        if (isset($_SESSION['stm_inst_data']["abs_stm_id"]))
            $this->abs_stm = AbstractStm::GetInstance($_SESSION['stm_inst_data']['abs_stm_id']);

        $this->users_found = $_SESSION['stm_inst_data']["users_found"];

        if (isset($_SESSION['stm_inst_data']['cur_sem_id']))
            $this->cur_seminar = Seminar::GetInstance($_SESSION['stm_inst_data']['cur_sem_id']);

        if (isset($_SESSION['stm_inst_data']['is_edit']))
            $is_edit = $_SESSION['stm_inst_data']['is_edit'];
        else
            $is_edit = false;

        if (isset($_SESSION['stm_inst_data']['inst_stm_vals'])) {
            $this->inst_stm = InstanceStm::GetInstance();
            $this->inst_stm->setValues($_SESSION['stm_inst_data']['inst_stm_vals']);
        }

        if (isset($_SESSION['stm_inst_data']["sel_group"]))
            $this->sel_group = $this->abs_stm->elements[$_SESSION['stm_inst_data']['sel_group']];

        if ($vis == null)
            $vis = new StmInstanceAssiVisualization($this);

        $init_data = array("level" => "f","cmd"=>"show","show_class"=>"all","group_by"=>0,"default_sem"=>"all","sem_status"=>"all");
        $this->sem_browse = new SemBrowse($init_data);
        $this->sem_browse->target_url = "stm_instance_assi.php";
        $this->sem_browse->target_id = "sem_id";

        $this->setSelStmForm();
        $this->setStgInputFormObject($_SESSION['stm_inst_data']['cur_abschl'], $_SESSION['stm_inst_data']['cur_stg'], $_SESSION['stm_inst_data']['cur_abs_stm']);
        $this->setAbsSummaryForm();
        $this->setAddInfoFormObject();
        $this->setSelElementgroupForm();
        $this->setFillGroupForm();
        $this->setSummaryForm();
        $this->setDeleteFormObject();
        $this->setInfoFormObject();

        // jetzt die Steuerlogik

        // VORAUSWAHL
        if ($this->sel_stm_form->IsSended()) {
            if ($this->sel_stm_form->IsClicked("neuanlegen")) {
                $is_edit = false;
                //$GLOBALS['sess']->unregister('stm_inst_data');
                $_SESSION['stm_inst_data'] ='';
                $vis->showStgInputForm($this->stg_input_form);
            }
            else {
                foreach ($this->sel_stm_form->form_fields as $name => $field) {
                    if ($this->sel_stm_form->IsClicked("sel_$name")) {
                        $this->inst_stm = InstanceStm::GetInstance($name);
                        $this->abs_stm = AbstractStm::GetInstance($this->inst_stm->getStmAbstrId());
                        $_SESSION['stm_data']['is_edit'] = true;
                        $is_edit = true;
                        $this->setAddInfoFormObject();
                        $vis->showAddInfoForm($this->add_info_form);
                        break;
                    }
                    elseif ($this->sel_stm_form->IsClicked("del_$name")) {
                        $this->inst_stm = InstanceStm::GetInstance($name);
                        $this->abs_stm = AbstractStm::GetInstance($this->inst_stm->getStmAbstrId());
                        $vis->showSummaryForm($this->delete_form, $this->inst_stm, $this->abs_stm);
                        break;
                    }
                    elseif ($this->sel_stm_form->IsClicked("info_$name")) {
                        $this->inst_stm = InstanceStm::GetInstance($name);
                        $this->abs_stm = AbstractStm::GetInstance($this->inst_stm->getStmAbstrId());
                        $vis->showSummaryForm($this->info_form, $this->inst_stm, $this->abs_stm);
                        break;
                    }
                }
            }
        }
        // INFOFORM
        elseif ($this->info_form->IsSended()) {
            if ($this->info_form->IsClicked("back")) {
                $vis->showSelStmForm($this->sel_stm_form);
            }
            else { // continue
                $vis->showSelStmForm($this->sel_stm_form);
            }
        }
        // LOESCHFORM
        elseif ($this->delete_form->IsSended() && $perm->have_perm('admin')) {
            if ($this->delete_form->IsClicked("continue")) {
                $this->inst_stm->delete();
                if (count($this->inst_stm->msg) != 0) {
                    $vis->showError($this->inst_stm->msg);
                    $vis->showSummaryForm($this->delete_form, $this->inst_stm, $this->abs_stm);
                }
                else {
                    //$GLOBALS['sess']->unregister('stm_inst_data');
                    $_SESSION['stm_inst_data'] = '';
                    unset($this->inst_stm);
                    unset($this->abs_stm);
                    unset($this->cur_seminar);
                    $this->setSelStmForm();
                    $vis->showError(array(array('msg', sprintf(_("Das konkrete Modul wurde entfernt")))));
                    $vis->showSelStmForm($this->sel_stm_form);
                    return;
                }
            }
            else { // back
                $vis->showSelStmForm($this->sel_stm_form);
            }
        }
        // ERSTES FORMULAR BEI NEU ANLEGEN
        elseif ($this->stg_input_form->IsSended()) {
            if ($this->stg_input_form->IsClicked("continue")) {
                if (!isset($this->stg_input_form->form_values['abs_stm_list']))
                {
                    $vis->showError(array(array('error', sprintf(_("Es muss ein Allgemeines Modul ausgew&auml;hlt werden.")))));
                    $vis->showStgInputForm($this->stg_input_form);
                }
                else
                {
                    $this->abs_stm = AbstractStm::GetInstance($this->stg_input_form->form_values['abs_stm_list']);
                    $this->inst_stm = InstanceStm::GetInstance();
                    $this->inst_stm->setValues(array(   'stm_abstr_id' => $this->abs_stm->getId(),
                                                        'creator' => $user->id,
                                                        'title' => $this->abs_stm->getTitle(),
                                                        'subtitle' => $this->abs_stm->getSubtitle(),
                                                        'topics' => $this->abs_stm->getTopics(),
                                                        'hints' => $this->abs_stm->getHints()
                                                        ));
                    $vis->showAbsSummaryForm($this->abs_summary_form, $this->abs_stm);
                }
            }
            elseif ($this->stg_input_form->IsClicked("back")) {
                $vis->showSelStmForm($this->sel_stm_form);
            }
            else { // select-Felder geaendert
                $_SESSION['stm_inst_data']['cur_abschl'] = $this->stg_input_form->form_values['abschl_list'];//
                $_SESSION['stm_inst_data']['cur_stg'] = $this->stg_input_form->form_values['stg_list'];//
                $this->setStgInputFormObject($_SESSION['stm_inst_data']['cur_abschl'], $_SESSION['stm_inst_data']['cur_stg'], $_SESSION['stm_inst_data']['cur_abs_stm']);
                $vis->showStgInputForm($this->stg_input_form);
            }
        }
        // ABSTRAKTE ZUSAMMENFASSUNG FORMULAR
        elseif ($this->abs_summary_form->IsSended()) {
            if ($this->abs_summary_form->IsClicked("continue")) {
                    $vis->showAddInfoForm($this->add_info_form);
            }
            else {
                $vis->showStgInputForm($this->stg_input_form);
            }
        }
        // ZWEITES FORMULAR
        elseif ($this->add_info_form->IsSended()) {
            if ($this->add_info_form->IsClicked("continue")) {
                $this->inst_stm->setValues($this->add_info_form->form_values);
                $this->inst_stm->checkValues();
                if (count($this->inst_stm->msg) > 0) {
                    $vis->showError($this->inst_stm->msg);
                    $this->inst_stm = array();
                    $vis->showAddInfoForm($this->add_info_form);
                } else
                    $vis->showSelElementgroupForm($this->sel_elementgroup_form,$this->abs_stm, $this->inst_stm);
            }
            elseif ($this->add_info_form->IsClicked("preview")) {
                $vis->showAddInfoForm($this->add_info_form);
            }
            elseif ($this->add_info_form->IsClicked("back")) {
                if ($is_edit)
                    $vis->showSelStmForm($this->sel_stm_form);
                else
                    $vis->showAbsSummaryForm($this->abs_summary_form, $this->abs_stm);
            }
            elseif ($this->add_info_form->IsClicked("reset")) {
                $this->add_info_form->doFormReset();
                $this->setAddInfoFormObject();
                $vis->showAddInfoForm($this->add_info_form);
            }
            elseif ($this->add_info_form->IsClicked("search")) {
                $this->users_found = $this->searchUser($this->add_info_form->form_values['homeinst'], $this->add_info_form->form_values['search_user']);
                if (count($this->users_found) > 0) {
                    $_SESSION['stm_inst_data']['users_found'] = $this->users_found;
                    $this->setAddInfoFormObject();
                }
                else
                    $vis->showError(array(array('info', sprintf(_("Es wurde kein Nutzer gefunden")))));
                $vis->showAddInfoForm($this->add_info_form);
            }
            else { // select change
                $this->users_found = null;
                $_SESSION['stm_inst_data']['users_found'] = $this->users_found;
                $this->setAddInfoFormObject();
                $vis->showAddInfoForm($this->add_info_form);
            }
        }
        // DRITTES FORMULAR
        elseif ($this->sel_elementgroup_form->IsSended()) {
            if ($this->sel_elementgroup_form->IsClicked("continue")) {
                $vis->showSummaryForm($this->summary_form, $this->inst_stm, $this->abs_stm);
            }
            elseif ($this->sel_elementgroup_form->IsClicked("back")) {
                $vis->showAddInfoForm($this->add_info_form);
            }
            else { // pruefen, ob Element gewählt wurde
                for ($i=0; $i<count($this->abs_stm->elements); $i++) {
                    if ($this->sel_elementgroup_form->IsClicked("sel_$i")) {
                        $_SESSION['stm_inst_data']['sel_group'] = $i;
                        $this->sel_group = $this->abs_stm->elements[$i];
                        $this->setFillGroupForm();
                        $vis->showFillGroupForm($this->fill_group_form, $this->sel_group, $this->sem_browse, $this->inst_stm, $_SESSION['stm_inst_data']['sel_group']);
                    }
                }
            }
        }
        // FILL_GROUP FORMULAR
        elseif ($this->fill_group_form->IsSended()
        || $this->sem_browse->search_obj->search_button_clicked
        || Request::get("send_from_search")) {
            if ($this->fill_group_form->IsClicked("continue")) {
                $vis->showSelElementgroupForm($this->sel_elementgroup_form,$this->abs_stm, $this->inst_stm);
            }
            elseif(Request::get("send_from_search")) {
                $this->cur_seminar = Seminar::GetInstance(Request::option("sem_id"));
                $vis->showFillGroupForm($this->fill_group_form, $this->sel_group, $this->sem_browse, $this->inst_stm, $_SESSION['stm_inst_data']['sel_group'], $this->cur_seminar);
            }
            else
            {
                $button_clicked = false;
                for ($i=0; $i<count($this->sel_group); $i++) {
                    if ($this->fill_group_form->IsClicked("fill_$i")) {
                        $button_clicked = true;
                        $this->inst_stm->addElement(array("sem_id" => $this->cur_seminar->getId(), "element_id" => $this->sel_group[$i]->getId()), $_SESSION['stm_inst_data']['sel_group'], $i);
                        $this->setFillGroupForm();
                        $vis->showFillGroupForm($this->fill_group_form, $this->sel_group, $this->sem_browse, $this->inst_stm, $_SESSION['stm_inst_data']['sel_group']);
                        break;
                    }
                    for ($j=0; $j<count($this->inst_stm->elements[($_SESSION['stm_inst_data']['sel_group'])][$i]); $j++) {
                        if ($this->fill_group_form->IsClicked("remove_" . $i ."_" .$j)) {
                            $button_clicked = true;
                            $this->inst_stm->removeElement($_SESSION['stm_inst_data']['sel_group'], $i, $j);
                            $this->setFillGroupForm();
                            $vis->showFillGroupForm($this->fill_group_form, $this->sel_group, $this->sem_browse, $this->inst_stm, $_SESSION['stm_inst_data']['sel_group']);
                            break;
                        }
                    }
                }
                if (!$button_clicked)
                    $vis->showFillGroupForm($this->fill_group_form, $this->sel_group, $this->sem_browse, $this->inst_stm, $_SESSION['stm_inst_data']['sel_group']);
            }
        }
        // SUMMARY FORMULAR
        elseif ($this->summary_form->IsSended()) {
            if ($this->summary_form->IsClicked("continue")) {
                $this->inst_stm->complete = $this->summary_form->form_values['complete'];
                $this->msg = $this->inst_stm->store($is_edit);

                if (count($this->msg) != 0) {
                    $vis->showError($this->msg);
                    $vis->showSummaryForm($this->summary_form, $this->inst_stm, $this->abs_stm);
                }
                else {
                    //$GLOBALS['sess']->unregister('stm_inst_data');
                    $_SESSION['stm_inst_data'] = '';
                    $this->setStgInputFormObject(null, null, null);
                    $vis->showError(array(array('msg', sprintf(_("Das Modul wurde erfolgreich angelegt")))));
                    $this->setSelStmForm();
                    $vis->showSelStmForm($this->sel_stm_form);
                    return;
                }
            }
            else {
                $vis->showSelElementgroupForm($this->sel_elementgroup_form,$this->abs_stm, $this->inst_stm);
            }
        }
        // sonst ANFANG
        else
            $vis->showSelStmForm($this->sel_stm_form);


        // Sessionvariablen setzen
        if ($this->abs_stm) {
            $_SESSION['stm_inst_data']['abs_stm_id'] = $this->abs_stm->GetId();
        }

        if ($this->inst_stm) {
            $_SESSION['stm_inst_data']['inst_stm_vals'] = $this->inst_stm->getValues();
        }

        if ($this->cur_seminar) {
            $_SESSION['stm_inst_data']['cur_sem_id'] = $this->cur_seminar->getId();
        }

        $_SESSION['stm_inst_data']["is_edit"] = $is_edit;
    }

    function getMyInst() {

        global $perm;
        global $user;
        
        $query = "SELECT Institut_id AS value, CONCAT(REPEAT('&#160;', 4), Name) AS name
                  FROM Institute
                  WHERE fakultaets_id = ? AND Institut_id != fakultaets_id
                  ORDER BY Name";
        $inner_statement = DBManager::get()->prepare($query);

        $parameters = array($user->id);
        if (!$perm->have_perm('admin')) {
            $query = "SELECT Name, Institut_id, Institut_id = fakultaets_id AS is_fak, inst_perms
                      FROM user_inst
                      LEFT JOIN Institute USING (Institut_id)
                      WHERE user_id = ? AND inst_perms = 'dozent'
                      ORDER BY is_fak, Name";
        } else if (!$perm->have_perm('root')) {
            $query = "SELECT Name, Institut_id, Institut_id = fakultaets_id AS is_fak, inst_perms
                      FROM user_inst
                      LEFT JOIN Institute USING (Institut_id)
                      WHERE user_id = ? AND inst_perms = 'admin'
                      ORDER BY is_fak, Name";
        } else {
            $query = "SELECT Name, Institut_id, 1 AS is_fak, 'admin' AS inst_perms
                      FROM Institute
                      WHERE Institut_id = fakultaets_id
                      ORDER BY Name";
            $parameters = array();
        }
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);

        $inst_arr = array();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $inst_arr[] = array('name' => $row['Name'], 'value' => $row['Institut_id']);

            if ($row['is_fak'] && $row['inst_perms'] == 'admin') {
                $inner_statement->execute(array($row['Institut_id']));
                while ($temp = $inner_statement->fetch(PDO::FETCH_ASSOC)) {
                    $inst_arr[] = $temp;
                }
                $inner_statement->closeCursor();
            }
        }
        return $inst_arr;

    }

    function searchUser($inst_id, $search_str)
    {
        global $_fullname_sql;

        $query = "SELECT DISTINCT user_id AS value, {$_fullname_sql['full_rev']} AS name
                  FROM user_inst
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE Institut_id = :inst_id AND inst_perms = 'dozent'
                    AND (username LIKE CONCAT('%', :needle, '%') OR
                         Vorname LIKE CONCAT('%', :needle, '%') OR
                         Nachname LIKE CONCAT('%', :needle, '%'))
                  ORDER BY Nachname, Vorname";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':inst_id', $inst_id);
        $statement->bindValue(':needle', $search_str);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

$perm->check("dozent");
// Start of Output
PageLayout::setTitle(_("Konkrete Studienmodule bearbeiten"));
Navigation::activateItem('/tools/modules');
include ("lib/include/html_head.inc.php"); // Output of html head
include ("lib/include/header.php");   // Output of Stud.IP head

if(get_config('STM_ENABLE')){
    $stm_class = new InstanceStmControl();
    $stm_class->show();
}
page_close();

?>
