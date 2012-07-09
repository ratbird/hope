<?php
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO

require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ("lib/seminar_open.php"); // initialise Stud.IP-Session

require_once('lib/classes/StudipForm.class.php');
require_once('lib/classes/Table.class.php');

require_once("lib/classes/StmAbstractAssiVisualization.class.php");
require_once("lib/classes/AbstractStm.class.php");
require_once("lib/classes/AbstractStmElement.class.php");

class AbstractStmControl{

    var $msg = array();
    var $sel_abs_stm_form;
    var $abs_input_form;
    var $elem_input_form;
    var $assign_form;
    var $summary_form;
    var $abs_stm;
    var $abs_elements; // zweidimensionales Array (Kombinationen und Listen -> OR und AND von Elementen)
    var $assigns;
    var $delete_form;
    var $info_form;


    function AbstractStmPlugin(){
    }

    function setSelAbsStmForm(){

        $stm_arr = $this->getAllAbsStm();

        $form_fields = array();

        $form_buttons = array("neuanlegen" => array('type' => 'Anlegen', 'info' => "neues Allgemeines Modul"));

        foreach ($stm_arr as $id => $vals) {
            $form_fields["$id"] = array('type' => 'NoForm', 'info' => $vals['title']);
            if (!$vals['instanced']) {
                $form_buttons["sel_$id"] = array('type' => 'Bearbeiten', 'info' => "Allgemeines Modul bearbeiten");
                $form_buttons["del_$id"] = array('type' => 'Löschen', 'info' => "Allgemeines Modul entfernen");
            }
            $form_buttons["info_$id"] = array('type' => 'Details', 'info' => "Details des Allgemeines Modul");
        }
        if (!is_object($this->sel_abs_stm_form)){
            $this->sel_abs_stm_form = new StudipForm($form_fields, $form_buttons, "sel_stm_form", false);
        } else {
            $this->sel_abs_stm_form->form_fields = $form_fields;
            $this->sel_abs_stm_form->form_buttons = $form_buttons;
        }
        return true;
    }

    function setDeleteFormObject(){


        $form_fields = array(
        );

        $form_buttons = array('back' => array('type' => 'Zurück', 'info' => _("Zum vorherigen Formular")),
                            'continue' => array('type' => 'Löschen', 'info' => _("Das Modul loeschen")),
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

        $form_buttons = array('back' => array('type' => 'Zurück', 'info' => _("Zum vorherigen Formular")),
                            );

        if (!is_object($this->info_form)){
            $this->info_form = new StudipForm($form_fields, $form_buttons, "info_form", false);
        } else {
            $this->info_form->form_fields = $form_fields;
        }
        return true;
    }

    function setInputFormObject(){

        $inst_arr = $this->getMyInst();

        $form_fields = array(   'title' =>  array('type' => 'text', 'caption' => 'Titel', 'info' => 'Der Name des allgemeinen Studienmoduls', 'default_value' => $this->abs_stm->getTitle(), 'required' => 'true'),
                                'subtitle' =>   array('type' => 'text', 'caption' => 'Untertitel', 'info' => 'Optionaler Untertitel des allgemeinen Studienmoduls', 'default_value' => $this->abs_stm->getSubtitle()),
                                'id_number' =>  array('type' => 'text', 'caption' => 'alphanumerische ID', 'info' => 'Alphanumerische ID des Moduls', 'default_value' => $this->abs_stm->getIdNumber()),
                                'topics' =>     array('type' => 'textarea', 'caption' => 'Inhalte', 'info' => 'Allgemein behandelte Themen', 'required' => 'true', 'default_value' => $this->abs_stm->getTopics()),
                                'aims' =>   array('type' => 'textarea', 'caption' => 'Lernziele', 'info' => 'allgemeine Beschreibung der Lernziele', 'required' => 'true', 'default_value' => $this->abs_stm->getAims()),
                                'hints' =>  array('type' => 'textarea', 'caption' => 'allgemeine Hinweise', 'info' => 'allgemeine Hinweise zum Modul', 'default_value' => $this->abs_stm->getHints()),
                                'duration' =>   array('type' => 'text', 'caption' => 'Dauer', 'info' => 'Laenge in Semestern', 'required' => 'true', 'default_value' => $this->abs_stm->getDuration()),
                                'credits' =>    array('type' => 'text', 'caption' => 'ECTS-Punkte', 'info' => 'Anzahl der Leistungspunkte/Kreditpunkte', 'required' => 'true', 'default_value' => $this->abs_stm->getCredits()),
                                'workload' =>   array('type' => 'text', 'caption' => 'Arbeitsaufwand', 'info' => 'Studentischer Arbeitsaufwand in Stunden', 'required' => 'true', 'default_value' => $this->abs_stm->getWorkload()),
                                'turnus' =>     array('type' => 'select', 'default_value' => $this->abs_stm->getTurnus(), 'options' => array(   array('name' =>'kein', 'value' => '0'),
                                                                                                array('name' =>'Sommersemester', 'value' => '1'),
                                                                                                array('name' =>'Wintersemester', 'value' => '2')),
                                'caption' => 'Angebotsturnus', 'info' => 'Optionaler Turnus des Modulbeginns'),
                                'homeinst' => array('type' => 'select', 'default_value' => $this->abs_stm->getHomeinst(), 'caption' => 'Heimat-Einrichtung', 'info' => 'Die Einrichtung, der das allgemeine Modul zugeordnet ist', 'options' => $inst_arr, required => 'true'),
        );

        $form_buttons = array('continue' => array('type' => 'Weiter', 'info' => _("Dieses Formular abschicken")),
                            'reset' => array('type' => 'Zurücksetzen', 'info' => _("Formularfelder leeren")),
                            'back' => array('type' => 'Zurück', 'info' => _("Zum vorherigen Formular")),
                            'preview' => array('type' => 'icons/16/black/question-circle.png', 'info' => _("Wiki Vorschau"), 'is_picture' => 'true'));




        if (!is_object($this->abs_input_form)){
            $this->abs_input_form = new StudipForm($form_fields, $form_buttons, "abs_input_form", false);
        } else {
            $this->abs_input_form->form_fields = $form_fields;
        }
        return true;
    }

    function setElementsInputFormObject(){

        $abs_element_types = AbstractStmElement::GetStmElementTypes();

        foreach($abs_element_types as $id => $val)
            $type_arr[] = array('name' => $val["name"], 'value' => $id);

        $sws_arr[] = array('name' => "-", 'value' => '-');
        for ($i=1; $i<61; $i++)
            $sws_arr[] = array('name' => $i , 'value' => $i);

        $form_fields = array();

        $form_buttons = array('back' => array('type' => 'Zurück', 'info' => _("Zum vorherigen Formular")),
                            'reset' => array('type' => 'Zurücksetzen', 'info' => _("Formularfelder leeren")),
                            'continue' => array('type' => 'Weiter', 'info' => _("Dieses Formular abschicken")),
                            'add_element_type' => array('type' => 'Anlegen', 'info' => _("Neue Lehr- und Lernform anlegen")),
                            'add_block' => array('type' => 'Neues Feld', 'info' => _("Neue Kombination hinzuf&#252;gen")));

        for ( $i = 0; $i< count($this->abs_elements); $i++) {
        $form_fields = array_merge($form_fields,
            array(
            'sws_' . $i =>  array('type' => 'select', 'caption' => 'SWS', 'info' => 'Laenge der Veranstaltung in Semesterwochenstunden', options => $sws_arr),
            'workload_' . $i =>     array('type' => 'text', 'caption' => 'Studentische Arbeitszeit in Stunden', 'info' => 'Studentischer Arbeitsaufwand in Stunden', 'required' => 'true'),
            'semester_' . $i =>     array('type' => 'select', 'caption' => 'Semester', 'options' => array(  array('name' =>'kein', 'value' => '0'),
                                array('name' =>'Sommersemester', 'value' => '1'),
                                array('name' =>'Wintersemester', 'value' => '2')))
            ));

            $form_fields['element_type_id_' . $i]   =   array('type' => 'select', 'caption' => 'Lehr- und Lernformen', 'info' => '', 'options' => $type_arr);

            $form_buttons['add_elem_' . $i] = array('type' => 'Hinzufügen', 'info' => _("Veranstaltung zu dieser Kombination hinzuf&#252;gen"));
            if ($i != 0) {
                $form_buttons['remove_block_' . $i] = array('type' => 'Entfernen', 'info' => _("Kombination entfernen"));
            }

            foreach($this->abs_elements[$i] as $index => $elem) {
                $form_buttons['remove_elem_' . $i . '_' . $index] = array('type' => Assets::image_path('icons/16/blue/trash.png'), 'info' => _("Diese Zeile entfernen"), 'is_picture' => 'true');
            }
        }

        $form_fields['new_element_name'] = array('type' => 'text', 'caption' => 'Name');
        $form_fields['new_element_abbrev'] = array('type' => 'text', 'caption' => 'Kurzform');

        if (!is_object($this->elem_input_form)){
            $this->elem_input_form = new StudipForm($form_fields, $form_buttons, "elem_input_form", false);
        } else {
            $this->elem_input_form->form_fields = $form_fields;
            $this->elem_input_form->form_buttons = $form_buttons;
        }
        return true;
    }

    function setAssignFormObject($sel_abschl, $sel_stg){

        $abschl = AbstractStm::GetAbschluesse();
        $abschl_arr = array();

        $abschl_arr[] = array('name' => 'ASQ', 'value' => 'asq');

        foreach($abschl as $abschlid => $name)
            $abschl_arr[] = array('name' => $name, 'value' => $abschlid);

        if (!$sel_abschl)
            $sel_abschl = $abschl_arr[1]['value'];

        $stg_arr = array();
        if ($sel_abschl != 'asq') {
            $stgaenge = AbstractStm::GetStg($sel_abschl);
            foreach($stgaenge as $stg => $name)
                $stg_arr[] = array('name' => $name, 'value' => $stg);
        }

        if (!$sel_stg)
            $sel_stg = $stg_arr[0]['value'];

        $pversions = AbstractStm::getPversions($sel_abschl, $sel_stg);
        foreach($pversions as $pvers => $name)
            $pvers_arr[] = array('name' => $name, 'value' => $pvers);

        $abs_types = AbstractStm::GetAbsStmTypes();
        foreach($abs_types as $id => $val)
            $type_arr[] = array('name' => $val["name"], 'value' => $id);

        $sem_arr[] = array('name' => "-" , 'value' => '-');
        for ($i=1; $i<13; $i++)
            $sem_arr[] = array('name' => $i , 'value' => $i);

        // Ja das stimmt so Studiengang (beamtendeutsch) = Abschluss (HIS) und Studienprogramm(beamtendeutsch) = Studiengang (HIS)
        $form_fields = array(
            'abschl_list'   =>  array('type' => 'select', 'caption' => 'Studiengang', 'info' => "", 'options' => $abschl_arr, 'default_value' => $sel_abschl),
            'stg_list'  => array('type' => 'select', 'caption' => 'Studienprogramm', 'info' => '', 'options' => $stg_arr),
            'type_list' => array('type' => 'select', 'caption' => 'Modulart', 'info' => 'Die verfuegbaren Modultypen', 'options' => $type_arr),
            'pversion'  =>  array('type' => 'select', 'caption' => _("Version der Pr&#252;fungsordnung"), 'info' => '', 'options' => $pvers_arr),
            'earliest'  =>  array('type' => 'select', 'caption' => 'Fr&#252;hestes Studiensemester', 'info' => 'Der frueheste Zeitpunkt, dieses Modul zu belegen', 'options' => $sem_arr),
            'latest'    =>  array('type' => 'select', 'caption' => 'Sp&#228;testes Studiensemester', 'info' => 'Der spaeteste Zeitpunkt, dieses Modul zu belegen', 'options' => $sem_arr),
            'recommed'  =>  array('type' => 'select', 'caption' => 'Empfohlenes Studiensemester', 'info' => 'Der empfohlene Zeitpunkt, dieses Modul zu belegen', 'options' => $sem_arr)
        );

        $form_buttons = array('back' => array('type' => 'Zurück', 'info' => _("Zum vorherigen Formular")),
                            'continue' => array('type' => 'Weiter', 'info' => _("Dieses Formular abschicken")),
                            'reset' => array('type' => 'Zurücksetzen', 'info' => _("Formularfelder leeren")),
                            'add' => array('type' => 'Hinzufügen', 'info' => _("Kombination hinzufuegen")),
                            'submit' => array('type' => 'Abschicken', 'info' => ""));

        foreach ($this->assigns as $index => $val)
            $form_buttons['remove_' . $index] = array('type' => Assets::image_path('icons/16/blue/trash.png'), 'info' => _("Diese Zeile entfernen"), 'is_picture' => 'true');

        if (!is_object($this->assign_form)){
            $this->assign_form = new StudipForm($form_fields, $form_buttons, "assign_form", false);
        } else {
            $this->assign_form->form_fields = $form_fields;
            $this->assign_form->form_buttons = $form_buttons;
        }
        return true;
    }

    function setSummaryFormObject(){


        $form_fields = array(
        );

        $form_buttons = array('back' => array('type' => 'Zurück', 'info' => _("Zum vorherigen Formular")),
                            'continue' => array('type' => 'Speichern', 'info' => _("Das Modul abspeichern")),
                            );

        if (!is_object($this->summary_form)){
            $this->summary_form = new StudipForm($form_fields, $form_buttons, "summary_form", false);
        } else {
            $this->summary_form->form_fields = $form_fields;
        }
        return true;
    }

    function getMyInst() {
        // Prepare statement that reads all institutes for a faculty
        $query = "SELECT Institut_id AS value, CONCAT(REPEAT('&#160;', 4), Name) AS name
                  FROM Institute
                  WHERE fakultaets_id = ? AND Institut_id != fakultaets_id
                  ORDER BY Name";
        $institutes_statement = DBManager::get()->prepare($query);
        
        // Prepare and execute statement that reads all faculties
        $query = "SELECT Name, Institut_id, 1 AS is_fak, 'admin' AS inst_perms
                  FROM Institute
                  WHERE Institut_id = fakultaets_id
                  ORDER BY Name";
        $statement = DBManager::get()->query($query);
        $faculties = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (count($faculties) === 0) {
            return array();
        }

        $inst_arr = array();
        foreach ($faculties as $faculty) {
            $inst_arr[] = array(
                'name'  => $faculty['Name'],
                'value' => $faculty['Institut_id'],
            );

            if ($faculty['is_fak'] && $faculty['inst_perms'] == 'admin') {
                $institutes_statement->execute(array($faculty['Institut_id']));
                $institutes = $institutes_statement->fetchAll(PDO::FETCH_ASSOC);
                $institutes_statement->closeCursor();

                $inst_arr = array_merge($inst_arr, $institutes);
            }
        }
        return $inst_arr;

    }

    function getAllAbsStm() {
        $query = "SELECT stm_abstr_id, title, COUNT( stm_instance_id ) AS instanced
                  FROM stm_abstract_text a
                  INNER JOIN stm_abstract b USING (stm_abstr_id)
                  LEFT JOIN stm_instances c USING (stm_abstr_id)
                  WHERE a.lang_id = ? AND a.stm_abstr_id != ''
                  GROUP BY stm_abstr_id
                  ORDER BY title";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(LANGUAGE_ID));
        return $statement->fetchGrouped(PDO::FETCH_ASSOC);
    }

    function show(){

        static $stmvis;

      //  $GLOBALS['sess']->register('stm_data');

        global $stm_data;

        // erstmal alle Daten wieder herstellen

        if (!isset($_SESSION['stm_data']['cur_abschl']))
            $_SESSION['stm_data']['cur_abschl'] = false;

        if (!isset($_SESSION['stm_data']['is_edit']))
            $_SESSION['stm_data']['is_edit'] = false;

        if (!isset($_SESSION['stm_data']['cur_stg']))
            $_SESSION['stm_data']['cur_stg'] = false;

        if (is_array($_SESSION['stm_data']['abs_elements_vals'])) {
            foreach($_SESSION['stm_data']['abs_elements_vals'] as $index => $val_list) {
                $this->abs_elements[$index] = array();
                foreach($val_list as $val) {
                    $elem = AbstractStmElement::GetInstance();
                    $elem->setValues($val);
                    $this->abs_elements[$index][] = $elem;
                }
            }
        }

        if (is_array($_SESSION['stm_data']['assigns'])) {
            foreach($_SESSION['stm_data']['assigns'] as $val) {
                $this->assigns[] = $val;
            }
        }

        if ($stmvis == null)
            $stmvis = new StmAbstractAssiVisualization($this);

        if (!is_array($this->abs_elements))
            $this->abs_elements[] = array();

        if (!is_array($this->assigns))
            $this->assigns = array();

        $this->abs_stm = AbstractStm::GetInstance();

        if (isset($_SESSION['stm_data']['cur_abs_stm']))
            $this->abs_stm->setValues($_SESSION['stm_data']['cur_abs_stm']);

        if (isset($_SESSION['stm_data']['assigns']))
            $this->abs_stm->setValues(array('assigns' => $this->assigns));

        $this->setSelAbsStmForm();
        $this->setDeleteFormObject();
        $this->setInfoFormObject();
        $this->setInputFormObject();
        $this->setElementsInputFormObject();
        $this->setAssignFormObject($_SESSION['stm_data']['cur_abschl'], $_SESSION['stm_data']['cur_stg']);
        $this->setSummaryFormObject();


        // die Elemente hinzufügen
        foreach($this->abs_elements as $index => $val_list)
            foreach ($val_list as $val)
                $this->abs_stm->addElement($val, $index);

        // jetzt die Steuerlogik

        // VORAUSWAHL
        if ($this->sel_abs_stm_form->IsSended()) {
            if ($this->sel_abs_stm_form->IsClicked("neuanlegen")) {
                $this->abs_stm = AbstractStm::GetInstance();
                $this->setInputFormObject();
                //$GLOBALS['sess']->unregister('stm_data');
                $_SESSION['stm_data'] = '';
                $stmvis->showInputForm($this->abs_input_form);
            }
            else {
                foreach ($this->sel_abs_stm_form->form_fields as $name => $field) {
                    if ($this->sel_abs_stm_form->IsClicked("sel_$name")) {
                        $this->abs_stm = AbstractStm::GetInstance($name);
                        $this->abs_elements = $this->abs_stm->elements;
                        $this->assigns = $this->abs_stm->assigns;
                        $_SESSION['stm_data']['is_edit'] = true;
                        $this->setInputFormObject();
                        $stmvis->showInputForm($this->abs_input_form);
                        break;
                    }
                    elseif ($this->sel_abs_stm_form->IsClicked("del_$name")) {
                        $this->abs_stm = AbstractStm::GetInstance($name);
                        $this->abs_elements = $this->abs_stm->elements;
                        $this->assigns = $this->abs_stm->assigns;
                        $stmvis->showSummaryForm($this->delete_form, $this->abs_stm, $this->abs_input_form, $this->elem_input_form, $this->assign_form);
                        break;
                    }
                    elseif ($this->sel_abs_stm_form->IsClicked("info_$name")) {
                        $this->abs_stm = AbstractStm::GetInstance($name);
                        $stmvis->showSummaryForm($this->info_form, $this->abs_stm, $this->abs_input_form, $this->elem_input_form, $this->assign_form);
                        break;
                    }
                }
            }
        }
        // INFOFORM
        elseif ($this->info_form->IsSended()) {
            if ($this->info_form->IsClicked("back")) {
                $stmvis->showSelAbsStmForm($this->sel_abs_stm_form);
            }
            else { // continue
                $stmvis->showSelAbsStmForm($this->sel_abs_stm_form);
            }
        }
        // LOESCHFORM
        elseif ($this->delete_form->IsSended()) {
            if ($this->delete_form->IsClicked("continue")) {
                $this->abs_stm->delete();
                if (count($this->abs_stm->msg) != 0) {
                    $stmvis->showError($this->abs_stm->msg);
                    $stmvis->showSummaryForm($this->delete_form, $this->abs_stm, $this->abs_input_form, $this->elem_input_form, $this->assign_form);
                }
                else {
                    //$GLOBALS['sess']->unregister('stm_data');
                    $_SESSION['stm_data'] = '';
                    $this->setSelAbsStmForm();
                    $stmvis->showError(array(array('msg', sprintf(_("Das allgemeine Modul wurde entfernt")))));
                    $stmvis->showSelAbsStmForm($this->sel_abs_stm_form);
                    return;
                }
            }
            else { // back
                $stmvis->showSelAbsStmForm($this->sel_abs_stm_form);
            }
        }
        // ERSTES FORMULAR
        elseif ($this->abs_input_form->IsSended()) {
            if ($this->abs_input_form->IsClicked("back")) {
                $stmvis->showSelAbsStmForm($this->sel_abs_stm_form);
            }
            elseif ($this->abs_input_form->IsClicked("reset")) {
                $this->abs_input_form->doFormReset();
                $stmvis->showInputForm($this->abs_input_form);
            }
            elseif ($this->abs_input_form->IsClicked("preview")) {
                $stmvis->showInputForm($this->abs_input_form);
            }
            else { // continue
                $this->abs_stm->setValues($this->abs_input_form->form_values);
                $this->msg = $this->abs_stm->checkValues();

                $stmvis->showError($this->msg);

                if (count($this->msg) ==0)
                    $stmvis->showElementsInputForm($this->elem_input_form, $this->abs_elements);
                else
                    $stmvis->showInputForm($this->abs_input_form);
            }
        }
        // ZWEITES FORMULAR
        elseif($this->elem_input_form->IsSended()) {
            if ($this->elem_input_form->IsClicked("reset")) {
                $this->elem_input_form->doFormReset();
                $this->abs_elements = array();
                $this->abs_elements[] = array();
                $this->setElementsInputFormObject();
                $stmvis->showElementsInputForm($this->elem_input_form, $this->abs_elements);
            }
            elseif ($this->elem_input_form->IsClicked("back")) {
                $this->abs_input_form->form_values = $this->abs_stm->getValues();
                $stmvis->showInputForm($this->abs_input_form);
            }
            elseif ($this->elem_input_form->IsClicked("add_block")) {
                $this->abs_elements[count($this->abs_elements)] = array();
                $this->setElementsInputFormObject();
                $stmvis->showElementsInputForm($this->elem_input_form, $this->abs_elements);
            }
            elseif ($this->elem_input_form->IsClicked("continue")) {
                $this->msg = $this->abs_stm->checkElements();

                $stmvis->showError($this->msg);

                if (count($this->msg) ==0)
                    $stmvis->showAssignForm($this->assign_form, $this->assigns);
                else
                    $stmvis->showElementsInputForm($this->elem_input_form, $this->abs_elements);
            }
            elseif ($this->elem_input_form->IsClicked("add_element_type")) {
                $this->msg[] = AbstractStmElement::AddElementType($this->elem_input_form->form_values['new_element_name'], $this->elem_input_form->form_values['new_element_abbrev']);

                $stmvis->showError($this->msg);

                $this->setElementsInputFormObject();
                $stmvis->showElementsInputForm($this->elem_input_form, $this->abs_elements);
            }
            else
            {
                $shift_others_from_block = false;
                $shift_all_others = false;
                foreach ($this->abs_elements as $i => $elements) {
                    if ($this->elem_input_form->IsClicked("add_elem_" . $i)) {
                        $temp_elem = AbstractStmElement::GetInstance();
                        $temp_elem->setValues(array(
                        'element_type_id' => $this->elem_input_form->form_values['element_type_id_' .$i],
                        'sws' => $this->elem_input_form->form_values['sws_' .$i],
                        'workload' => $this->elem_input_form->form_values['workload_' .$i],
                        'semester' => $this->elem_input_form->form_values['semester_' .$i],
                        'stm_abstr_id' => $this->abs_stm->getId(),
                        'elementgroup' => $i,
                        'position' => count($this->abs_elements[$i])
                        ));
                        $this->msg = $temp_elem->checkValues();
                        $stmvis->showError($this->msg);
                        if (count($this->msg) ==0) {
                            $this->abs_elements[$i][] = $temp_elem;
                        }
                        break;
                    }
                    foreach($this->abs_elements[$i] as $index => $val)
                    {
                        if ($shift_others_from_block) {
                            $val->setValues(array('position' => ($index-1)));
                            $this->abs_elements[$i][($index-1)] = $val;
                            unset($this->abs_elements[$i][$index]);
                        }

                        if ($this->elem_input_form->IsClicked("remove_elem_" . $i . '_' . $index)) {
                            $shift_others_from_block = true;
                            unset($this->abs_elements[$i][$index]);
                        }
                    }
                    if ($shift_all_others)
                    {
                        foreach($this->abs_elements[$i] as $j => $val)
                        {
                            $this->abs_elements[$i][$j]->setValues(array('elementgroup' => sprintf("%s",($i-1))));
                        }
                        $this->abs_elements[$i-1] = $this->abs_elements[$i];
                        unset($this->abs_elements[$i]);
                    }
                    if ($i !=0 && $this->elem_input_form->IsClicked('remove_block_' . $i))
                    {
                        $shift_all_others = true;
                        unset($this->abs_elements[$i]);
                    }
                    if ($shift_others_from_block)
                        break;
                }
                $this->setElementsInputFormObject();
                $stmvis->showElementsInputForm($this->elem_input_form, $this->abs_elements);
            }
        }
        // DRITTES FORMULAR
        elseif($this->assign_form->IsSended()) {
            if ($this->assign_form->IsClicked("back"))
                    $stmvis->showElementsInputForm($this->elem_input_form, $this->abs_elements);
            elseif ($this->assign_form->IsClicked("reset")) {
                $this->assign_form->doFormReset();
                $this->assigns = array();
                $_SESSION['stm_data']['assigns'] = $this->assigns;
                $this->setAssignFormObject($_SESSION['stm_data']['cur_abschl'], $_SESSION['stm_data']['cur_stg']);
                $stmvis->showAssignForm($this->assign_form, $this->assigns);
            }
            elseif ($this->assign_form->IsClicked("continue")) {
                $this->msg = $this->abs_stm->checkAssigns();

                $stmvis->showError($this->msg);

                if (count($this->msg) ==0) {
                    $stmvis->showSummaryForm($this->summary_form, $this->abs_stm, $this->abs_input_form, $this->elem_input_form, $this->assign_form);
                }
                else
                    $stmvis->showAssignForm($this->assign_form, $this->assigns);

            }
            elseif ($this->assign_form->IsClicked("add")) {

                if ($this->assign_form->form_values['abschl_list'] == 'asq')
                {
                    $this->assigns[] = array("stm_type_id" => $this->assign_form->form_values['type_list'],
                        "abschl" => $this->assign_form->form_values['abschl_list'],
                        "stg" => '000',
                        "pversion" => '0',
                        "earliest" => $this->assign_form->form_values['earliest'],
                        "latest" => $this->assign_form->form_values['latest'],
                        "recommed" => $this->assign_form->form_values['recommed'],
                        );
                } else
                {
                    $this->assigns[] = array("stm_type_id" => $this->assign_form->form_values['type_list'],
                        "abschl" => $this->assign_form->form_values['abschl_list'],
                        "stg" => $this->assign_form->form_values['stg_list'],
                        "pversion" => $this->assign_form->form_values['pversion'],
                        "earliest" => $this->assign_form->form_values['earliest'],
                        "latest" => $this->assign_form->form_values['latest'],
                        "recommed" => $this->assign_form->form_values['recommed'],
                        );
                }
                    $this->setAssignFormObject($_SESSION['stm_data']['cur_abschl'], $_SESSION['stm_data']['cur_stg']);
                    $stmvis->showAssignForm($this->assign_form, $this->assigns);
            }
            else {
                $shift_others = false;
                foreach($this->assigns as $index => $val) {
                    if ($shift_others) {
                        $this->assigns[$index-1] = $val;
                        unset ($this->assigns[$index]);
                    }

                    if ($this->assign_form->IsClicked("remove_" . $index))
                    {
                            $shift_others = true;
                            unset($this->assigns[$index]);
                    }
                }
                if (!$shift_others) {// Select hat sich geändert
                    if ($_SESSION['stm_data']['cur_abschl'] != $this->assign_form->form_values['abschl_list']) {
                        $_SESSION['stm_data']['cur_abschl'] = $this->assign_form->form_values['abschl_list'];
                        $_SESSION['stm_data']['cur_stg'] = false;
                    } else
                        $_SESSION['stm_data']['cur_stg'] = $this->assign_form->form_values['stg_list'];

                }
                else // assigns haben sich geändert => Session aktualisieren
                    $_SESSION['stm_data']['assigns'] = $this->assigns;
                $this->setAssignFormObject($_SESSION['stm_data']['cur_abschl'], $_SESSION['stm_data']['cur_stg']);
                $stmvis->showAssignForm($this->assign_form, $this->assigns);
            }
        }
        // VIERTES FORMULAR
        elseif ($this->summary_form->IsSended()) {
            if ($this->summary_form->IsClicked("back"))
                    $stmvis->showAssignForm($this->assign_form, $this->assigns);
            else { // speichern
                $this->abs_stm->store($_SESSION['stm_data']['is_edit']);

                if (count($this->abs_stm->msg) != 0) {
                    $stmvis->showError($this->abs_stm->msg);
                    $stmvis->showSummaryForm($this->summary_form, $this->abs_stm, $this->abs_input_form, $this->elem_input_form, $this->assign_form);
                }
                else {
                    //$GLOBALS['sess']->unregister('stm_data');
                    $_SESSION['stm_data'] = '';
                    $stmvis->showError(array(array('msg', sprintf(_("Das allgemeine Modul wurde erfolgreich gespeichert")))));
                    $this->setSelAbsStmForm();
                    $stmvis->showSelAbsStmForm($this->sel_abs_stm_form);
                    return;
                }
            }
        }
        // sonst ANFANG
        else
            $stmvis->showSelAbsStmForm($this->sel_abs_stm_form);

        // Sessionvariablen setzen
        $_SESSION['stm_data']['assigns'] = $this->assigns;
        foreach ($this->abs_elements as $index => $val_list) {
            $elems_vals[$index] = array();
            foreach ($val_list as $val)
                $elems_vals[$index][] = $val->getValues();
        }
        $_SESSION['stm_data']['abs_elements_vals'] = $elems_vals;
        $_SESSION['stm_data']['cur_abs_stm'] = $this->abs_stm->getValues();

//      var_dump($this->abs_stm); echo "<br><br>";
//      var_dump($this->abs_elements); echo "<br><br>";
//      var_dump($this->assigns); echo "<br><br>";

    }
}

$perm->check("root");
// Start of Output
PageLayout::setTitle(_("Allgemeine Studienmodule bearbeiten"));
Navigation::activateItem('/admin/config/abstract_modules');
include ("lib/include/html_head.inc.php"); // Output of html head
include ("lib/include/header.php");   // Output of Stud.IP head

if(get_config('STM_ENABLE')){
    $stm_class = new AbstractStmControl();
    $stm_class->show();
}
page_close();
?>
