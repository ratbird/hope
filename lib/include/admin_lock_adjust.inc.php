<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

use Studip\Button, Studip\LinkButton;

require_once("lib/classes/DataFieldStructure.class.php");
require_once("lib/classes/LockRules.class.php");
require_once("lib/classes/ZebraTable.class.php");

function show_lock_rules() {
    $lock_rules = new LockRules;
    $all_lock_data = $lock_rules->getAllLockRules($GLOBALS['perm']->have_perm('root'));
    return $all_lock_data;
}

function check_empty_lock_rule($lock_id) {
    $stmt = DBManager::get()->prepare("SELECT COUNT(*) as count FROM seminare ".
                                      "WHERE lock_rule=?");
    $result = $stmt->execute(array($lock_id));
    if (!$result) {
        return 0;
    }
    $row = $stmt->fetch();
    return $row['count'];
}

function delete_lock_rule($lock_id) {
    $lock_rules = new LockRules;
    return $lock_rules->deleteLockRule($lock_id);
}

function show_content() {
    $data = "<br><a href=\"".URLHelper::getLink("?action=new")."\">"._("<b>Neue Sperrebene anlegen</b>")."</a>";
    $zt = new ZebraTable(array("width"=>"100%", "padding"=>"5"));
    $data .= $zt->openHeaderRow();
    $data .= $zt->cell("<b>"._("Name")."</b>",array("width"=>"25%"));
    $data .= $zt->cell("<b>"._("Beschreibung")."</b>",array("width"=>"55%"));
    $data .= $zt->cell("<b>".""."</b>",array("width"=>"10%"));
    $data .= $zt->cell("<b>".""."</b>",array("width"=>"10%"));
    $data .= $zt->closeRow();
    $all_lock_data = show_lock_rules();
    if (is_array($all_lock_data)) {
        for ($i=0; $i < count($all_lock_data); $i++) {
            $data .= $zt->row(array(htmlReady($all_lock_data[$i]["name"]),
                        htmlReady($all_lock_data[$i]["description"]),
                        LinkButton::create(_("Bearbeiten"), URLHelper::getURL('', array('action' => 'edit', 'lock_id' => $all_lock_data[$i]["lock_id"]))),
                        LinkButton::create(_("Löschen"), URLHelper::getURL('', array('action' => 'confirm_delete', 'lock_id' => $all_lock_data[$i]["lock_id"])))));
        }
    }
    $data .= $zt->close();
    return $data;
}

function show_lock_rule_form($lockdata="",$edit=0) {
    global $perm;
    if(!$lockdata['permission']){
        $lockdata['permission'] = 'dozent'; 
    }
    if ($edit) {
        $form = "<h3>".sprintf("Sperrebene \"%s\" ändern", htmlready($lockdata["name"]))."</h3>";
    } else {
        $form = "<h3>"._("Neue Sperrebene eingeben")."</h3>";
    }
    $zt2 = new ZebraTable(array("width"=>"100%","padding"=>"5"));
    $form .= "<form action=\"".URLHelper::getLink()."\" method=\"POST\">";
    $form .= CSRFProtection::tokenTag();
    $form .= "<input type=\"hidden\" name=\"lockdata[lock_id]\" value=\"".$lockdata["lock_id"]."\">";
    $form .= $zt2->openRow();
    $form .= $zt2->cell(_("Name"),array("width"=>"30%"));
    $form .= $zt2->cell("<input type=\"text\" style=\"width:90%\" name=\"lockdata[name]\" value=\"".htmlReady($lockdata["name"])."\">",array("width"=>"70%","colspan"=>"2"));
    $form .= $zt2->row(array(_("Beschreibung") .'<br><span style="font-size:80%">'._("(dieser Text wird auf allen Seiten mit gesperrtem Inhalt angezeigt)").'</span>',"<textarea name=\"lockdata[description]\" rows=5 style=\"width:90%\">".htmlReady($lockdata["description"])."</textarea>",""));
    $form .= $zt2->cell(_("Nutzerstatus").'<br><span style="font-size:80%">'._("(die Einstellungen dieser Sperrebene gelten für Nutzer bis zu dieser Berechtigung)").'</span>', array("width"=>"30%"));
    $select = "\n" . '<select name="lockdata[permission]">';
    foreach(($perm->have_perm('root') ? array('tutor','dozent','admin','root') : array('tutor','dozent')) as $p){
        $select .= "\n" . '<option ' . ($lockdata['permission'] == $p ? 'selected' : '') . '>'.$p.'</option>';
    }
    $select .= "\n" . '</select>';
    $form .= $zt2->cell($select , array("width"=>"70%","colspan"=>"2"));
    $form .= $zt2->close();
    $form .= "<br>";
    $zt = new ZebraTable(array("width"=>"100%","padding"=>"5"));
    $form .= $zt->openHeaderRow();
    $form .= $zt->cell("<font size=4><b>"._("Attribute")."</b></font>",array("width"=>"73%"));
    $form .= $zt->cell("<b>".""."</b>",array("width"=>"14%","align"=>"left"));
    $form .= $zt->cell("<b>".""."</b>",array("width"=>"13%","align"=>"left"));
    $form .= $zt->closeRow();
    $form .= $zt->headerRow(array("&nbsp;<b>"._("Grunddaten")."</b>", "<b>"._("gesperrt")."</b>", "<b>"._("nicht gesperrt")."</b>"));
    $form .= $zt->closeRow();
    $form .= $zt->openRow();
    if ($lockdata["attributes"]["VeranstaltungsNummer"]) {
        $form .= $zt->row(array(_("Veranstaltungsnummer"),"<input type=\"radio\" name=\"lockdata[attributes][VeranstaltungsNummer]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][VeranstaltungsNummer]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Veranstaltungsnummer"),"<input type=\"radio\" name=\"lockdata[attributes][VeranstaltungsNummer]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][VeranstaltungsNummer]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["seminar_inst"]) {
        $form .= $zt->row(array(_("beteiligte Einrichtungen"),"<input type=\"radio\" name=\"lockdata[attributes][seminar_inst]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][seminar_inst]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("beteiligte Einrichtungen"),"<input type=\"radio\" name=\"lockdata[attributes][seminar_inst]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][seminar_inst]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["Name"]) {
        $form .= $zt->row(array(_("Name"),"<input type=\"radio\" name=\"lockdata[attributes][Name]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][Name]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Name"),"<input type=\"radio\" name=\"lockdata[attributes][Name]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][Name]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["Untertitel"]) {
        $form .= $zt->row(array(_("Untertitel"),"<input type=\"radio\" name=\"lockdata[attributes][Untertitel]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][Untertitel]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Untertitel"),"<input type=\"radio\" name=\"lockdata[attributes][Untertitel]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][Untertitel]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["status"]) {
        $form .= $zt->row(array(_("Status"),"<input type=\"radio\" name=\"lockdata[attributes][status]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][status]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Status"),"<input type=\"radio\" name=\"lockdata[attributes][status]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][status]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["Beschreibung"]) {
        $form .= $zt->row(array(_("Beschreibung"),"<input type=\"radio\" name=\"lockdata[attributes][Beschreibung]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][Beschreibung]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Beschreibung"),"<input type=\"radio\" name=\"lockdata[attributes][Beschreibung]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][Beschreibung]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["Ort"]) {
        $form .= $zt->row(array(_("Ort"),"<input type=\"radio\" name=\"lockdata[attributes][Ort]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][Ort]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Ort"),"<input type=\"radio\" name=\"lockdata[attributes][Ort]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][Ort]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["art"]) {
        $form .= $zt->row(array(_("Veranstaltungstyp"),"<input type=\"radio\" name=\"lockdata[attributes][art]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][art]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Veranstaltungstyp"),"<input type=\"radio\" name=\"lockdata[attributes][art]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][art]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["ects"]) {
        $form .= $zt->row(array(_("ECTS-Punkte"),"<input type=\"radio\" name=\"lockdata[attributes][ects]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][ects]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("ECTS-Punkte"),"<input type=\"radio\" name=\"lockdata[attributes][ects]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][ects]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["admission_turnout"]) {
        $form .= $zt->row(array(_("Teilnehmerzahl"),"<input type=\"radio\" name=\"lockdata[attributes][admission_turnout]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][admission_turnout]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Teilnehmerzahl"),"<input type=\"radio\" name=\"lockdata[attributes][admission_turnout]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][admission_turnout]\" value=0 checked>"));
    }
    if ($edit) {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"confirm_edit\">";
        $form .= $zt->openRow();
        $form .= $zt->cell("&nbsp;",array("colspan" => "1"));
        $form .= $zt->cell(Button::create(_("Übernehmen")), array("colspan"=>"3","align"=>"center"));
    } else {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"insert\">";
        $form .= $zt->openRow();
        $form .= $zt->cell("&nbsp;",array("colspan" => "1"));
        $form .= $zt->cell(Button::create(_("Anlegen")), array("colspan"=>"3","align"=>"center"));
    }
    $form .= $zt->closeRow();
    $form .= $zt->headerRow(array("&nbsp;<b>"._("Personen und Einordnung")."</b>", "<b>"._("gesperrt")."</b>", "<b>"._("nicht gesperrt")."</b>"));
    $form .= $zt->closeRow();
    $form .= $zt->openRow();
    if ($lockdata["attributes"]["dozent"]) {
        $form .= $zt->row(array(_("DozentInnen"),"<input type=\"radio\" name=\"lockdata[attributes][dozent]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][dozent]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("DozentInnen"),"<input type=\"radio\" name=\"lockdata[attributes][dozent]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][dozent]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["tutor"]) {
        $form .= $zt->row(array(_("TutorInnen"),"<input type=\"radio\" name=\"lockdata[attributes][tutor]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][tutor]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("TutorInnen"),"<input type=\"radio\" name=\"lockdata[attributes][tutor]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][tutor]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["Institut_id"]) {
        $form .= $zt->row(array(_("Heimateinrichtung"),"<input type=\"radio\" name=\"lockdata[attributes][Institut_id]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][Institut_id]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Heimateinrichtung"),"<input type=\"radio\" name=\"lockdata[attributes][Institut_id]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][Institut_id]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["sem_tree"]) {
        $form .= $zt->row(array(_("Studienbereiche"),"<input type=\"radio\" name=\"lockdata[attributes][sem_tree]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][sem_tree]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Studienbereiche"),"<input type=\"radio\" name=\"lockdata[attributes][sem_tree]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][sem_tree]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["participants"]) {
        $form .= $zt->row(array(_("Teilnehmer hinzufügen/löschen"),"<input type=\"radio\" name=\"lockdata[attributes][participants]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][participants]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Teilnehmer hinzufügen/löschen"),"<input type=\"radio\" name=\"lockdata[attributes][participants]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][participants]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["groups"]) {
        $form .= $zt->row(array(_("Gruppen hinzufügen/löschen"),"<input type=\"radio\" name=\"lockdata[attributes][groups]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][groups]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Gruppen hinzufügen/löschen"),"<input type=\"radio\" name=\"lockdata[attributes][groups]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][groups]\" value=0 checked>"));
    }
    if ($edit) {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"confirm_edit\">";
        $form .= $zt->openRow();
        $form .= $zt->cell("&nbsp;",array("colspan" => "1"));
        $form .= $zt->cell(Button::create(_("Übernehmen")), array("colspan"=>"3","align"=>"center"));
    } else {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"insert\">";
        $form .= $zt->openRow();
        $form .= $zt->cell("&nbsp;",array("colspan" => "1"));
        $form .= $zt->cell(Button::create(_("Anlegen")), array("colspan"=>"3","align"=>"center"));
    }
    $form .= $zt->closeRow();
    $form .= $zt->headerRow(array("&nbsp;<b>"._("weitere Daten")."</b>", "<b>"._("gesperrt")."</b>", "<b>"._("nicht gesperrt")."</b>"));
    $form .= $zt->closeRow();
    $form .= $zt->openRow();
    if ($lockdata["attributes"]["Sonstiges"]) {
        $form .= $zt->row(array(_("Sonstiges"),"<input type=\"radio\" name=\"lockdata[attributes][Sonstiges]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][Sonstiges]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Sonstiges"),"<input type=\"radio\" name=\"lockdata[attributes][Sonstiges]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][Sonstiges]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["teilnehmer"]) {
        $form .= $zt->row(array(_("Beschreibung des Teilnehmerkreises"),"<input type=\"radio\" name=\"lockdata[attributes][teilnehmer]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][teilnehmer]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Beschreibung des Teilnehmerkreises"),"<input type=\"radio\" name=\"lockdata[attributes][teilnehmer]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][teilnehmer]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["voraussetzungen"]) {
        $form .= $zt->row(array(_("Teilnahmevoraussetzungen"),"<input type=\"radio\" name=\"lockdata[attributes][voraussetzungen]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][voraussetzungen]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Teilnahmevoraussetzungen"),"<input type=\"radio\" name=\"lockdata[attributes][voraussetzungen]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][voraussetzungen]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["lernorga"]) {
        $form .= $zt->row(array(_("Lernorganisation"),"<input type=\"radio\" name=\"lockdata[attributes][lernorga]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][lernorga]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Lernorganisation"),"<input type=\"radio\" name=\"lockdata[attributes][lernorga]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][lernorga]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["leistungsnachweis"]) {
        $form .= $zt->row(array(_("Leistungsnachweis"),"<input type=\"radio\" name=\"lockdata[attributes][leistungsnachweis]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][leistungsnachweis]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Leistungsnachweis"),"<input type=\"radio\" name=\"lockdata[attributes][leistungsnachweis]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][leistungsnachweis]\" value=0 checked>"));
    }
    $datafields = get_all_seminars_generic_datafields();

  $datafields_list = DataFieldStructure::getDataFieldStructures("sem");

    foreach ($datafields_list as $key=>$val) {
        if ($lockdata["attributes"][$key]) {
            $form .= $zt->row(array($val->data["name"],"<input type=\"radio\" name=\"lockdata[attributes][".$val->data["datafield_id"]."]\" value=\"1\" checked>","<input type=\"radio\" name=\"lockdata[attributes][".$val->data["datafield_id"]."]\" value=\"0\">"));
        } else {
            $form .= $zt->row(array($val->data["name"],"<input type=\"radio\" name=\"lockdata[attributes][".$val->data["datafield_id"]."]\" value=\"1\">","<input type=\"radio\" name=\"lockdata[attributes][".$val->data["datafield_id"]."]\" value=\"0\" checked>"));
        }
    }
    if ($edit) {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"confirm_edit\">";
        $form .= $zt->openRow();
        $form .= $zt->cell("&nbsp;",array("colspan" => "1"));
        $form .= $zt->cell(Button::create(_("Übernehmen")), array("colspan"=>"3","align"=>"center"));
    } else {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"insert\">";
        $form .= $zt->openRow();
        $form .= $zt->cell("&nbsp;",array("colspan" => "1"));
        $form .= $zt->cell(Button::create(_("Anlegen")), array("colspan"=>"3","align"=>"center"));
    }
    $form .= $zt->closeRow();
    
    $form .= $zt->headerRow(array("&nbsp;<b>"._("Zeiten/Räume")."</b>", "<b>"._("gesperrt")."</b>", "<b>"._("nicht gesperrt")."</b>"));
    $form .= $zt->closeRow();
    $form .= $zt->openRow();
    if ($lockdata["attributes"]["room_time"]) {
        $form .= $zt->row(array(_("Zeiten/Räume"),"<input type=\"radio\" name=\"lockdata[attributes][room_time]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][room_time]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Zeiten/Räume"),"<input type=\"radio\" name=\"lockdata[attributes][room_time]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][room_time]\" value=0 checked>"));
    }
    if ($edit) {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"confirm_edit\">";
        $form .= $zt->openRow();
        $form .= $zt->cell("&nbsp;",array("colspan" => "1"));
        $form .= $zt->cell(Button::create(_("Übernehmen")),array("colspan"=>"3","align"=>"center"));
    } else {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"insert\">";
        $form .= $zt->openRow();
        $form .= $zt->cell("&nbsp;",array("colspan" => "1"));
        $form .= $zt->cell(Button::create(_("Anlegen")),array("colspan"=>"3","align"=>"center"));
    }
    $form .= $zt->closeRow();
    $form .= $zt->headerRow(array("&nbsp;<b>"._("Zugangsberechtigungen")."</b>", "<b>"._("gesperrt")."</b>", "<b>"._("nicht gesperrt")."</b>"));
    $form .= $zt->closeRow();
    $form .= $zt->openRow();
    if ($lockdata["attributes"]["admission_endtime"]) {
        $form .= $zt->row(array(_("Zeit/Datum des Losverfahrens/Kontingentierung"),"<input type=\"radio\" name=\"lockdata[attributes][admission_endtime]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][admission_endtime]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Zeit/Datum des Losverfahrens/Kontingentierung"),"<input type=\"radio\" name=\"lockdata[attributes][admission_endtime]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][admission_endtime]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["admission_disable_waitlist"]) {
        $form .= $zt->row(array(_("Aktivieren/Deaktivieren der Warteliste"),"<input type=\"radio\" name=\"lockdata[attributes][admission_disable_waitlist]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][admission_disable_waitlist]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Aktivieren/Deaktivieren der Warteliste"),"<input type=\"radio\" name=\"lockdata[attributes][admission_disable_waitlist]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][admission_disable_waitlist]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["admission_binding"]) {
        $form .= $zt->row(array(_("Verbindlichkeit der Anmeldung"),"<input type=\"radio\" name=\"lockdata[attributes][admission_binding]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][admission_binding]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Verbindlichkeit der Anmeldung"),"<input type=\"radio\" name=\"lockdata[attributes][admission_binding]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][admission_binding]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["admission_type"]) {
        $form .= $zt->row(array(_("Typ des Anmeldeverfahrens"),"<input type=\"radio\" name=\"lockdata[attributes][admission_type]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][admission_type]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Typ des Anmeldeverfahrens"),"<input type=\"radio\" name=\"lockdata[attributes][admission_type]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][admission_type]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["admission_studiengang"]) {
        $form .= $zt->row(array(_("zugelassenene Studiengänge"),"<input type=\"radio\" name=\"lockdata[attributes][admission_studiengang]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][admission_studiengang]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("zugelassenene Studiengänge"),"<input type=\"radio\" name=\"lockdata[attributes][admission_studiengang]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][admission_studiengang]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["admission_prelim"]) {
        $form .= $zt->row(array(_("Vorl&auml;ufigkeit der Anmeldungen"),"<input type=\"radio\" name=\"lockdata[attributes][admission_prelim]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][admission_prelim]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Vorl&auml;ufigkeit der Anmeldungen"),"<input type=\"radio\" name=\"lockdata[attributes][admission_prelim]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][admission_prelim]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["admission_prelim_txt"]) {
        $form .= $zt->row(array(_("Hinweistext bei Anmeldungen"),"<input type=\"radio\" name=\"lockdata[attributes][admission_prelim_txt]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][admission_prelim_txt]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Hinweistext bei Anmeldungen"),"<input type=\"radio\" name=\"lockdata[attributes][admission_prelim_txt]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][admission_prelim_txt]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["admission_starttime"]) {
        $form .= $zt->row(array(_("Startzeitpunkt der Anmeldem&ouml;glichkeit"),"<input type=\"radio\" name=\"lockdata[attributes][admission_starttime]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][admission_starttime]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Startzeitpunkt der Anmeldem&ouml;glichkeit"),"<input type=\"radio\" name=\"lockdata[attributes][admission_starttime]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][admission_starttime]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["admission_endtime_sem"]) {
        $form .= $zt->row(array(_("Endzeitpunkt der Anmeldem&ouml;glichkeit"),"<input type=\"radio\" name=\"lockdata[attributes][admission_endtime_sem]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][admission_endtime_sem]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Endzeitpunkt der Anmeldem&ouml;glichkeit"),"<input type=\"radio\" name=\"lockdata[attributes][admission_endtime_sem]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][admission_endtime_sem]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["Lesezugriff"]) {
        $form .= $zt->row(array(_("Lesezugriff"),"<input type=\"radio\" name=\"lockdata[attributes][Lesezugriff]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][Lesezugriff]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Lesezugriff"),"<input type=\"radio\" name=\"lockdata[attributes][Lesezugriff]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][Lesezugriff]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["Schreibzugriff"]) {
        $form .= $zt->row(array(_("Schreibzugriff"),"<input type=\"radio\" name=\"lockdata[attributes][Schreibzugriff]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][Schreibzugriff]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Schreibzugriff"),"<input type=\"radio\" name=\"lockdata[attributes][Schreibzugriff]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][Schreibzugriff]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["Passwort"]) {
        $form .= $zt->row(array(_("Passwort"),"<input type=\"radio\" name=\"lockdata[attributes][Passwort]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][Passwort]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Passwort"),"<input type=\"radio\" name=\"lockdata[attributes][Passwort]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][Passwort]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["user_domain"]) {
        $form .= $zt->row(array(_("zugelassenene Nutzerdomänen"),"<input type=\"radio\" name=\"lockdata[attributes][user_domain]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][user_domain]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("zugelassenene Nutzerdomänen"),"<input type=\"radio\" name=\"lockdata[attributes][user_domain]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][user_domain]\" value=0 checked>"));
    }
    if ($edit) {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"confirm_edit\">";
        $form .= $zt->openRow();
        $form .= $zt->cell("&nbsp;",array("colspan" => "1"));
        $form .= $zt->cell(Button::create(_("Übernehmen")).">", array("colspan"=>"3","align"=>"center"));
    } else {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"insert\">";
        $form .= $zt->openRow();
        $form .= $zt->cell("&nbsp;",array("colspan" => "1"));
        $form .= $zt->cell(Button::create(_("Anlegen")), array("colspan"=>"3","align"=>"center"));
    }
    $form .= $zt->closeRow();
    $form .= $zt->headerRow(array("&nbsp;<b>"._("Spezielle Aktionen")."</b>", "<b>"._("gesperrt")."</b>", "<b>"._("nicht gesperrt")."</b>"));
    $form .= $zt->closeRow();
    $form .= $zt->openRow();
    if ($lockdata["attributes"]["seminar_copy"]) {
        $form .= $zt->row(array(_("Veranstaltung kopieren"),"<input type=\"radio\" name=\"lockdata[attributes][seminar_copy]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][seminar_copy]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Veranstaltung kopieren"),"<input type=\"radio\" name=\"lockdata[attributes][seminar_copy]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][seminar_copy]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["seminar_archive"]) {
        $form .= $zt->row(array(_("Veranstaltung archivieren"),"<input type=\"radio\" name=\"lockdata[attributes][seminar_archive]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][seminar_archive]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Veranstaltung archivieren"),"<input type=\"radio\" name=\"lockdata[attributes][seminar_archive]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][seminar_archive]\" value=0 checked>"));
    }
    if ($lockdata["attributes"]["seminar_visibility"]) {
        $form .= $zt->row(array(_("Veranstaltung sichtbar/unsichtbar schalten"),"<input type=\"radio\" name=\"lockdata[attributes][seminar_visibility]\" value=1 checked>","<input type=\"radio\" name=\"lockdata[attributes][seminar_visibility]\" value=0>"));
    } else {
        $form .= $zt->row(array(_("Veranstaltung sichtbar/unsichtbar schalten"),"<input type=\"radio\" name=\"lockdata[attributes][seminar_visibility]\" value=1>","<input type=\"radio\" name=\"lockdata[attributes][seminar_visibility]\" value=0 checked>"));
    }
    
    $form .= $zt->closeRow();
    if ($edit) {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"confirm_edit\">";
        $form .= $zt->openRow();
        $form .= $zt->cell(Button::create(_("Übernehmen")) . "&nbsp;" . LinkButton::create(_("Abbrechen"), 
                 URLHelper::getLink()), array("colspan"=>"3","align"=>"center"));
    } else {
        $form .= "<input type=\"hidden\" name=\"action\" value=\"insert\">";
        $form .= $zt->openRow();
        $form .= $zt->cell(Button::create(_("Anlegen")) . "&nbsp;" . LinkButton::create(_("Abbrechen"), 
                 URLHelper::getLink()), array("colspan"=>"3","align"=>"center"));
    }
    $form .= "</form>";
    $form .= $zt->close();
    return $form;
}

function update_existing_rule($updatedata) {
    $lock_rules = new LockRules;
    $success = $lock_rules->updateExistingLockRule($updatedata);
    return $success;
}

function parse_lockdata($lockdata) {
    $insertdata = array();
    $insertdata["name"] = $lockdata["name"];
    $insertdata["permission"] = $lockdata["permission"];
    $insertdata["lock_id"] = $lockdata["lock_id"];
    $insertdata["description"] = $lockdata["description"];
    while (list($key,$val)=each($lockdata["attributes"])) {
        if ($val==1) {
            $insertdata["attributes"][$key] = $val;
        }
    }
    return $insertdata;
}

function insert_lock_rule($insertdata) {
    $lock_rule = new LockRules;
    return $lock_rule->insertNewLockRule($insertdata);
}

function get_all_seminars_generic_datafields() {
    $i++;
    $db = DBManager::get();

    $datafields = array();
    foreach ($db->query("SELECT * FROM datafields ".
                        "WHERE object_class=1 ".
                        "ORDER BY priority") as $row) {
        $datafields[$i]["name"] = $row["name"];
        $datafields[$i]["id"]   = $row["datafield_id"];
        $i++;
    }

    if (!sizeof($datafields)) {
        return 0;
    }

    return $datafields;
}

function get_lock_rule($lock_id) {
    $lock_rules = new LockRules;
    $lockdata = $lock_rules->getLockRule($lock_id);
    return $lockdata;
}

function get_lock_rule_by_name($name) {
    $lock_rule = new LockRules;
    return $lock_rule->getLockRuleByName($name);
}

?>
