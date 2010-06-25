<?php

/*
 * Copyright (C) 2010 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/classes/Seminar.class.php';
require_once 'lib/trails/AuthenticatedController.php';
require_once 'lib/classes/Institute.class.php';

class Course_BasicdataController extends AuthenticatedController {
    public $msg = array();
    
    public function view_action() {
        global $SessSemName, $user, $perm, $HELP_KEYWORD, $CURRENT_PAGE, $_fullname_sql,
        $SEM_CLASS, $SEM_TYPE;
        
        Request::set('new_doz_parameter', $this->flash['new_doz_parameter']);
        Request::set('new_tut_parameter', $this->flash['new_tut_parameter']);
        
        $this->course_id = $SessSemName[1];
        
        if ((Request::get('section') === 'details') || ($this->flash['section'] === 'details')) {
            $this->section = 'details';
            UrlHelper::bindLinkParam('section', $section);
            Navigation::activateItem('/course/admin/details');
        } else {
            Navigation::activateItem('/admin/course/details');
            $this->section = 'admin';
        }
        
        if (!$this->course_id) {
            $GLOBALS['CURRENT_PAGE'] = _('Studienbereichsauswahl');
            $GLOBALS['view_mode'] = "sem";

            require_once 'lib/admin_search.inc.php';

            include 'lib/include/html_head.inc.php';
            include 'lib/include/header.php';
            include 'lib/include/admin_search_form.inc.php';  // will not return
        }
        
        //Berechtigungscheck:
        if (!$perm->have_studip_perm("tutor",$SessSemName[1])) {
            throw new AccessDeniedException(_("Sie haben keine Berechtigung diese " .
                    "Veranstaltung zu verändern."));
        }
        
        $HELP_KEYWORD = "Basis.VeranstaltungenVerwaltenGrunddaten";
        $GLOBALS['CURRENT_PAGE'] .= _("Verwaltung der Grunddaten");
        if (getHeaderLine($this->course_id)) {
            $GLOBALS['CURRENT_PAGE'] = getHeaderLine($this->course_id)." - ".$CURRENT_PAGE;
        }
        $sem = new Seminar($this->course_id);
        
        $data = $sem->getData();
        
        $this->attributes = array();
        $this->attributes[] = array(
            'title' => _("Name der Veranstaltung"),
            'name' => "course_name",
            'bold' => true,
            'type' => 'text',
            'value' => htmlReady($data['name']),
            'locked' => LockRules::Check($this->course_id, 'Name')
        );
        $this->attributes[] = array(
            'title' => _("Untertitel der Veranstaltung"),
            'name' => "course_subtitle",
            'type' => 'text',
            'value' => htmlReady($data['subtitle']),
            'locked' => LockRules::Check($this->course_id, 'Untertitel')
        );
        $sem_types = array();
        foreach ($GLOBALS['SEM_TYPE'] as $key => $type) {
            $sem_types[$key] = $type['name'];
        }
        $this->attributes[] = array(
            'title' => _("Typ der Veranstaltung"),
            'name' => "course_status",
            'bold' => true,
            'type' => 'select',
            'value' => htmlReady($data['status']),
            'locked' => LockRules::Check($this->course_id, 'status'),
            'choices' => $sem_types
        );
        $this->attributes[] = array(
            'title' => _("Art der Veranstaltung"),
            'name' => "course_form",
            'type' => 'text',
            'value' => htmlReady($data['form']),
            'locked' => LockRules::Check($this->course_id, 'art')
        );
        $this->attributes[] = array(
            'title' => _("Veranstaltungs-Nummer"),
            'name' => "course_seminar_number",
            'bold' => false,
            'type' => 'text',
            'value' => htmlReady($data['seminar_number']),
            'locked' => LockRules::Check($this->course_id, 'VeranstaltungsNummer')
        );
        $this->attributes[] = array(
            'title' => _("ECTS-Punkte"),
            'name' => "course_ects",
            'bold' => false,
            'type' => 'text',
            'value' => htmlReady($data['ects']),
            'locked' => LockRules::Check($this->course_id, 'ects')
        );
        $this->attributes[] = array(
            'title' => _("max. Teilnehmerzahl"),
            'name' => "course_admission_turnout",
            'bold' => true,
            'type' => 'text',
            'value' => htmlReady($data['admission_turnout']),
            'locked' => LockRules::Check($this->course_id, 'admission_turnout')
        );
        $this->attributes[] = array(
            'title' => _("Beschreibung"),
            'name' => "course_description",
            'type' => 'textarea',
            'value' => htmlReady($data['description']), 
            'locked' => LockRules::Check($this->course_id, 'Beschreibung')
        );
        
        
        $this->institutional = array();
        $institute = Institute::getMyInstitutes();
        $choices = array();
        foreach ($institute as $inst) {
            //$choices[$inst['Institut_id']] = $inst['Name'];
            $choices[$inst['Institut_id']] = 
                ($inst['is_fak'] ? "<span style=\"font-weight: bold\">" : "&nbsp;&nbsp;&nbsp;&nbsp;") .
                htmlReady($inst['Name']) .
                ($inst['is_fak'] ? "</span>" : "");
        }
        $this->institutional[] = array(
            'title' => _("Heimat-Einrichtung"),
            'name' => "course_institut_id",
            'bold' => true,
            'type' => 'select',
            'value' => $data['institut_id'],
            'choices' => $choices,
            'locked' => LockRules::Check($this->course_id, 'Institut_id')
        );
        $institute = Institute::getInstitutes();
        $choices = array();
        foreach ($institute as $inst) {
            $choices[$inst['Institut_id']] = 
                ($inst['is_fak'] ? "<span style=\"font-weight: bold\">" : "&nbsp;&nbsp;&nbsp;&nbsp;") .
                htmlReady($inst['Name']) .
                ($inst['is_fak'] ? "</span>" : "");
        }
        $inst = array_flip($sem->getInstitutes());
        unset($inst[$sem->institut_id]);
        $inst = array_flip($inst);
        $this->institutional[] = array(
            'title' => _("beteiligte Einrichtungen"),
            'name' => "related_institutes[]",
            'type' => 'multiselect',
            'value' => $inst,
            'choices' => $choices,
            'locked' => LockRules::Check($this->course_id, 'seminar_inst')
        );
        
        
        if ($SEM_CLASS[$SEM_TYPE[$sem->status]["class"]]["only_inst_user"]) {
            $clause="AND user_inst.Institut_id IN (". 
                    "SELECT institut_id FROM seminar_inst " .
                    "WHERE seminar_id = '".$this->course_id."' " . 
                ") ";
        }
        $query = "SELECT DISTINCT auth_user_md5.user_id, " .
                            $_fullname_sql['full_rev'] ." AS fullname " .
                        "FROM user_inst " .
                                "LEFT JOIN auth_user_md5 ON (user_inst.user_id = auth_user_md5.user_id) " .
                                "LEFT JOIN user_info ON (user_info.user_id = auth_user_md5.user_id) " .
                        "WHERE (auth_user_md5.username LIKE :input " .
                                "OR auth_user_md5.Vorname LIKE :input " .
                                "OR auth_user_md5.Nachname LIKE :input) " .
                            "AND auth_user_md5.perms IN %s " .
                            $clause .
                        "ORDER BY auth_user_md5.Nachname DESC ";
        $this->dozenten = $sem->getMembers('dozent');
        $Dozentensuche = new SQLSearch(
                        sprintf($query, "('dozent')"), 
                        sprintf(_("Name %s"), get_title_for_status('dozent', 1, $seminar_type)), 
                        "user_id");
        $this->dozentensuche = QuickSearch::get("new_doz", $Dozentensuche)
                                    ->withButton()
                                    ->render();
        $this->tutoren = $sem->getMembers('tutor');
        $Tutorensuche = new SQLSearch(
                        sprintf($query, "('tutor', 'dozent')"), 
                        sprintf(_("Name %s"), get_title_for_status('tutor', 1, $seminar_type)), 
                        "user_id");
        $this->tutorensuche = QuickSearch::get("new_tut", $Tutorensuche)
                                    ->withButton()
                                    ->render();
                                
        $this->descriptions[] = array(
            'title' => _("Teilnehmer/-innen"),
            'name' => "course_participants",
            'type' => 'textarea',
            'value' => $data['participants'], 
            'locked' => LockRules::Check($this->course_id, 'teilnehmer')
        );
        $this->descriptions[] = array(
            'title' => _("Voraussetzungen"),
            'name' => "course_requirements",
            'type' => 'textarea',
            'value' => $data['requirements'], 
            'locked' => LockRules::Check($this->course_id, 'voraussetzungen')
        );
        $this->descriptions[] = array(
            'title' => _("Lernorganisation"),
            'name' => "course_orga",
            'type' => 'textarea',
            'value' => $data['orga'], 
            'locked' => LockRules::Check($this->course_id, 'lernorga')
        );
        $this->descriptions[] = array(
            'title' => _("Leistungsnachweis"),
            'name' => "course_leistungsnachweis",
            'type' => 'textarea',
            'value' => $data['leistungsnachweis'], 
            'locked' => LockRules::Check($this->course_id, 'leistungsnachweis')
        );
        $this->descriptions[] = array(
            'title' => _("Ort") .
                "<br><span style=\"font-size: 0.8em\"><b>" .
                _("Achtung:") .
                "&nbsp;</b>" .
                _("Diese Ortsangabe wird nur angezeigt, wenn keine " .
                  "Angaben aus Zeiten oder Sitzungsterminen gemacht werden k&ouml;nnen.") .
                "</span>",
            'name' => "course_location",
            'type' => 'textarea',
            'value' => $data['location'], 
            'locked' => LockRules::Check($this->course_id, 'Ort')
        );
        
        $datenfelder = DataFieldEntry::getDataFieldEntries($this->course_id, 'sem', $data["status"]);
        if ($datenfelder) {
            foreach($datenfelder as $datenfeld) {
                if ($datenfeld->isVisible()) {
                    $this->descriptions[] = array(
                        'title' => $datenfeld->getName(),
                        'name' => "datafield_".$datenfeld->getID(),
                        'type' => "datafield",
                        'value' => $datenfeld->getHTML("datafields"), 
                        'locked' => !$datenfeld->isEditable() 
                            || LockRules::Check($this->course_id, $datenfeld->getID())
                    );
                }
            }
        }
        $this->descriptions[] = array(
            'title' => _("Sonstiges"),
            'name' => "course_misc",
            'type' => 'textarea',
            'value' => $data['misc'], 
            'locked' => LockRules::Check($this->course_id, 'Sonstiges')
        );
        
        $this->perm_dozent = $perm->have_studip_perm("dozent",$SessSemName[1]);
        $this->mkstring = $data['mkdate'] ? date("d.m.Y, G:i", $data['mkdate']) : _("unbekannt");
        $this->chstring = $data['chdate'] ? date("d.m.Y, G:i", $data['chdate']) : _("unbekannt");
        $this->flash->discard();
    }
    
    /**
     * 
     */
    public function set_action() {
        global $SessSemName, $user, $perm;
        $sem = new Seminar($SessSemName[1]);
        $this->msg = array();
        //Seminar-Daten:
        if ($perm->have_studip_perm("tutor",$SessSemName[1])) {
            $changemade = false;
            foreach ($_POST as $req_name => $req_value) {
                if (substr($req_name, 0, 7) === "course_") {
                    $varname = substr($req_name, 7);
                    if ($sem->{$varname} != $req_value) {
                        $sem->{$varname} = $req_value;
                        $changemade = true;
                        if (in_array($varname, array("participants", 
                                "requirements", 
                                "orga", 
                                "leistungsnachweis", 
                                "location", 
                                "misc"))) {
                        }
                    }
                }
            }
            //seminar_inst:
            if ($sem->setInstitutes($_POST['related_institutes'] ? $_POST['related_institutes'] : array())) {
                $changemade = true;
            }
            //Datenfelder:
            $all_fields_types = DataFieldEntry::getDataFieldEntries($sem->id, 'sem', $sem->status);
            foreach ($_POST['datafields'] as $datafield_id => $datafield_value) {
                $datafield = $all_fields_types[$datafield_id];
                $valueBefore = $datafield->getValue();
                //noch klären, dass nicht nutzlose Datenfelder angelegt werden:
                if ($datafield->getType() === "bool" && !$valueBefore) {
                    $valueBefore = "0";
                }
                if ($datafield->getType() === "selectbox" && !$valueBefore) {
                    $valueBefore = "0";
                }
                if ($valueBefore != $datafield_value) {
                    $datafield->setValue($datafield_value);
                    $datafield->store();
                    $changemade = true;
                }
            }
            $sem->store();
            if ($changemade) {
                $this->msg[] = array("msg", _("Die Grunddaten der Veranstaltung wurden verändert."));
            } else {
                $this->msg[] = array("info", _("An den Grunddaten wurde nichts geändert."));
            }

            //Dozenten hinzufügen:
            if ($_POST['new_doz'] && $_POST['add_dozent_x']
                   && $perm->have_studip_perm("dozent",$SessSemName[1])) {
                if ($sem->addMember($_POST['new_doz'], "dozent")) {
                    $this->msg[] = array("msg", sprintf(_("%s wurde hinzugefügt."),
                            get_title_for_status('dozent', 1, $sem->status)));
                }
            }
            //Tutoren hinzufügen:
            if ($_POST['new_tut'] && $_POST['add_tutor_x']
                    && $perm->have_studip_perm("tutor",$SessSemName[1])) {
                if ($sem->addMember($_POST['new_tut'], "tutor")) {
                    $this->msg[] = array("msg", sprintf(_("%s wurde hinzugefügt."), 
                            get_title_for_status('tutor', 1, $sem->status)));
                }
            }
        } else {
            $this->msg[] = array("error", _("Sie haben keine Berechtigung diese " .
                    "Veranstaltung zu ver&auml;ndern."));
        }
        foreach($sem->getStackedMessages() as $key => $messages) {
            foreach($messages['details'] as $message) {
                $this->msg[] = array(($key !== "success" ? $key : "msg"), $message);
            }
        }
        $this->flash['msg'] = $this->msg;
        
        if (($_POST["new_doz_parameter"]
                && !$_POST["add_dozent_x"]
                && $_POST["new_doz_parameter"] !== sprintf(_("Name %s"), get_title_for_status('dozent', 1, $sem->status)))
            || ($_POST["new_tut_parameter"]
                && !$_POST["add_tutor_x"]
                && $_POST["new_tut_parameter"] !== sprintf(_("Name %s"), get_title_for_status('tutor', 1, $sem->status)))) {
            if (!$changemade) {
                unset($this->flash['msg']);
            }
        }
        $this->flash['new_doz_parameter'] = $_POST['new_doz'] ? null : Request::get('new_doz_parameter');
        $this->flash['new_tut_parameter'] = $_POST['new_tut'] ? null : Request::get('new_tut_parameter');
        $this->flash['open'] = Request::get("open");
        $this->flash['section'] = Request::get("section");
        $this->redirect('course/basicdata/view?cid='.$SessSemName[1]);
    }
    
    public function deletedozent_action($dozent) {
        global $SessSemName, $user, $perm;
        $this->msg = array();
        if ($perm->have_studip_perm("dozent",$SessSemName[1])) {
            if ($dozent !== $user->id) {
                $sem = new Seminar($SessSemName[1]);
                $sem->deleteMember($dozent);
                foreach($sem->getStackedMessages() as $key => $messages) {
                    foreach($messages['details'] as $message) {
                        $this->msg[] = array(($key !== "success" ? $key : "msg"), $message);
                    }
                }
            } else {
                $this->msg[] = array("error", _("Sie d&uuml;rfen sich nicht selbst aus " .
                        "der Veranstaltung austragen."));
            }
        } else {
            $this->msg[] = array("error", _("Sie haben keine Berechtigung diese " .
                    "Veranstaltung zu ver&auml;ndern."));
        }
        $this->flash['msg'] = $this->msg;
        $this->flash['open'] = "bd_personal";
        $this->flash['section'] = Request::get("section");
        $this->redirect('course/basicdata/view?cid='.$SessSemName[1]);
    }
    
    public function deletetutor_action($tutor) {
        global $SessSemName, $user, $perm;
        $this->msg = array();
        if ($perm->have_studip_perm("dozent",$SessSemName[1])) {
            $sem = new Seminar($SessSemName[1]);
            $sem->deleteMember($tutor);
            $this->messages = array_merge($this->messages, $sem->getStackedMessages());
            foreach($sem->getStackedMessages() as $key => $messages) {
                foreach($messages['details'] as $message) {
                    $this->msg[] = array(($key !== "success" ? $key : "msg"), $message);
                }
            }
        } else {
            $this->msg[] = array("error", _("Sie haben keine Berechtigung diese " .
                    "Veranstaltung zu ver&auml;ndern."));
        }
        $this->flash['msg'] = $this->msg;
        $this->flash['open'] = "bd_personal";
        $this->flash['section'] = Request::get("section");
        $this->redirect('course/basicdata/view?cid='.$SessSemName[1]);
    }
    
    public function priorityupfor_action($user_id, $status = "dozent") {
        global $SessSemName, $user, $perm;
        $this->msg = array();
        if ($perm->have_studip_perm("dozent",$SessSemName[1])) {
            $sem = new Seminar($SessSemName[1]);
            $teilnehmer = $sem->getMembers($status);
            $members = array();
            foreach($teilnehmer as $key => $member) {
                $members[] = $member["user_id"];
            }
            foreach($members as $key => $member) {
                if ($key > 0 && $member == $user_id) {
                    $temp_member = $members[$key-1];
                    $members[$key-1] = $member;
                    $members[$key] = $temp_member;
                }
            }
            $sem->setMemberPriority($members, $status);
        } else {
            $this->msg[] = array("error", _("Sie haben keine Berechtigung diese " .
                    "Veranstaltung zu ver&auml;ndern."));
        }
        $this->flash['msg'] = $this->msg;
        $this->flash['open'] = "bd_personal";
        $this->flash['section'] = Request::get("section");
        $this->redirect('course/basicdata/view?cid='.$SessSemName[1]);
    }
    
    public function prioritydownfor_action($user_id, $status = "dozent") {
        global $SessSemName, $user, $perm;
        $this->msg = array();
        if ($perm->have_studip_perm("dozent",$SessSemName[1])) {
            $sem = new Seminar($SessSemName[1]);
            $teilnehmer = $sem->getMembers($status);
            $members = array();
            foreach($teilnehmer as $key => $member) {
                $members[] = $member["user_id"];
            }
            foreach($members as $key => $member) {
                if ($key < count($members)-1 && $member == $user_id) {
                    $temp_member = $members[$key+1];
                    $members[$key+1] = $member;
                    $members[$key] = $temp_member;
                }
            }
            $sem->setMemberPriority($members, $status);
        } else {
            $this->msg[] = array("error", _("Sie haben keine Berechtigung diese " .
                    "Veranstaltung zu ver&auml;ndern."));
        }
        $this->flash['msg'] = $this->msg;
        $this->flash['open'] = "bd_personal";
        $this->flash['section'] = Request::get("section");
        $this->redirect('course/basicdata/view?cid='.$SessSemName[1]);
    }
    
}