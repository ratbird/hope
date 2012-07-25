<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter003: TEST
# Lifter005: TODO - form validation and password encryption
# Lifter007: TODO
# Lifter010: TODO
/**
* admin_admission.php
*
* edit the settings for the admission system
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @module       admin_admission.php
* @modulegroup  admin
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_admission.php
// Zugangsberechtigungen fuer Veranstaltungen verwalten
// Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("tutor");

require_once('lib/msg.inc.php');    //Ausgaben
require_once('config.inc.php'); //Settings....
require_once 'lib/functions.php';   //basale Funktionen
require_once('lib/visual.inc.php'); //Darstellungsfunktionen
require_once('lib/messaging.inc.php');  //Nachrichtenfunktionen
require_once('lib/admission.inc.php');  //load functions from admission system
require_once('lib/classes/StudipAdmissionGroup.class.php'); //htmlReady
require_once('lib/classes/UserDomain.php'); // Nutzerdomänen

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once 'lib/admin_search.inc.php';
require_once('lib/statusgruppe.inc.php');

PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenZugangsberechtigungen");
PageLayout::setTitle(_("Verwaltung von Zugangsberechtigungen"));

if ($perm->have_perm('admin')) {
    Navigation::activateItem('/admin/course/admission');
} else {
    Navigation::activateItem('/course/admin/admission');
}

$cssSw = new cssClassSwitcher;
$admin_admission_data = unserialize(base64_decode($_REQUEST['admin_admission_data']));
$admin_admission_data_original = unserialize(base64_decode($_REQUEST['admin_admission_data']));
$seminar_id = Request::option('seminar_id',$SessSemName[1]);

if(!$seminar_id && $admin_admission_data["sem_id"]) {
    $seminar_id = $admin_admission_data["sem_id"];
}

//Change header_line if open object
$header_line = getHeaderLine($seminar_id);
if ($header_line)
    PageLayout::setTitle($header_line." - ".PageLayout::getTitle());

//Output starts here
include ('lib/include/html_head.inc.php'); // Output of html head

// -- here you have to put initialisations for the current page
?>
    <script type="text/javascript" language="javascript">
    <!--
    function doCrypt() {
        var $form = $('form[name=Formular]');
        if ($(':radio[name=read_level]:checked', $form).val()==2 || $(':radio[name=write_level]:checked', $form).val()==2) {
            if(checkpasswordenabled() && checkpassword() && checkpassword2()){
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    function checkpasswordenabled(){
        var checked = true;
        if (document.Formular.sem_passwd.value.length == 0){
            alert("<?= _("Sie haben Lese- oder Schreibzugriff nur mit Passwort gewählt. Bitte geben Sie ein Passwort ein.") ?>");
            document.Formular.sem_passwd.focus();
            checked = false;
        }
        return checked;
    }

    function checkpassword(){
        var checked = true;
        if ((document.Formular.sem_passwd.value.length<4) && (document.Formular.sem_passwd.value.length != 0)) {
            alert("<?= _("Das Passwort ist zu kurz. Es sollte mindestens 4 Zeichen lang sein.") ?>");
            document.Formular.sem_passwd.focus();
            checked = false;
        }
        return checked;
    }

    function checkpassword2(){
    var checked = true;
    if (document.Formular.sem_passwd.value != document.Formular.sem_passwd2.value) {
        alert("<?=_("Das Passwort stimmt nicht mit dem Wiederholungspasswort überein!") ?>");
        document.Formular.sem_passwd2.focus();
        checked = false;
        }
        return checked;
    }
    // -->
    </script>
<?

include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
include 'lib/include/admin_search_form.inc.php';

$messaging = new messaging;

if (Request::quotedArray('delete_studg')) {
    $delete_studg = array_pop(array_keys(Request::quotedArray('delete_studg')));
    Request::set('delete_studg', $delete_studg);
}

if (Request::quotedArray('delete_domain')) {
    $delete_domain = array_pop(array_keys(Request::quotedArray('delete_domain')));
    Request::set('delete_domain', $delete_domain);
}
/**
* This function creates a snapshot for all the values the admin_admission script uses
*
* The function serializes all the data which is used on this page. So you can
* compare an old and a new state of the whole set. It is used to inform the user,
* that the data isn't saved yet.
*
* @param        string  all the data in serialized form
*
*/
function get_snapshot() {
    global $admin_admission_data, $seminar_id;

    return  md5($admin_admission_data["admission_turnout"].
        $admin_admission_data["admission_type"].
        $admin_admission_data["admission_endtime"].
        $admin_admission_data["admission_binding"].
        $admin_admission_data["passwort"].
        $admin_admission_data["read_level"].
        $admin_admission_data["write_level"].
        serialize($admin_admission_data["studg"]).
        $admin_admission_data["all_ratio"].
        $admin_admission_data["admission_prelim"].
        $admin_admission_data["admission_prelim_txt"].
        $admin_admission_data["sem_admission_start_date"].
        $admin_admission_data["sem_admission_end_date"].
        $admin_admission_data["admission_disable_waitlist"].
        $admin_admission_data["admission_enable_quota"]);
}

$errormsg = '';

//check, if seminar is grouped
$group_obj = StudipAdmissionGroup::GetAdmissionGroupBySeminarId($seminar_id);
if (is_object($group_obj)) { //if so, do not allow to change admission_type
    $is_grouped = TRUE;
} else {
    $is_grouped = FALSE;
}
// user domain handling
if (isset($seminar_id) && !LockRules::check($seminar_id, 'user_domain') && Request::quoted('add_domain'))
{
    $domain = new UserDomain(Request::quoted('add_domain'));
    $domain->addSeminar($seminar_id);
}

if (isset($seminar_id) && !LockRules::check($seminar_id, 'user_domain') && Request::quoted('delete_domain'))
{
    $domain = new UserDomain(Request::quoted('delete_domain'));
    $domain->removeSeminar($seminar_id);
}

// new stuff start

if (isset($seminar_id)) {

    $lockdata = LockRules::getObjectRule($seminar_id);

    $query = "SELECT * FROM seminare WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    $seminar = $statement->fetch(PDO::FETCH_ASSOC);

    $fields = array(
        'admission_turnout'          => 'admission_turnout',
        'admission_type'             => 'admission_type',
        'admission_endtime'          => 'admission_endtime',
        'admission_binding'          => 'admission_binding',
        'Passwort'                   => 'passwort',
        'Lesezugriff'                => 'read_level',
        'Schreibzugriff'             => 'write_level',
        'admission_prelim'           => 'admission_prelim',
        'admission_prelim_txt'       => 'admission_prelim_txt',
        'admission_starttime'        => 'admission_starttime',
        'admission_endtime_sem'      => 'admission_endtime_sem',
        'admission_disable_waitlist' => 'admission_disable_waitlist',
    );
    
    foreach ($fields as $key => $field) {
        if (LockRules::Check($seminar_id, $key)) {
            $admin_admission_data[$field] = $seminar[$key];
        }
    }
}
// end new stuff

//wenn wir frisch reinkommen, werden benoetigte Daten eingelesen
if ($seminar_id
    && !Request::submittedSome(
        "uebernehmen",
        "adm_null", "adm_los", "adm_chrono", "adm_gesperrt",
        "add_studg", "toggle_admission_quota")
    && !$delete_studg
    && !$delete_domain
    && !Request::get('add_domain')) {

    $query = "SELECT * FROM seminare WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    $seminar = $statement->fetch(PDO::FETCH_ASSOC);

    $admin_admission_data = array(
        'admission_turnout'              => $seminar['admission_turnout'],
        'admission_turnout_org'          => $seminar['admission_turnout'],
        'admission_type'                 => $seminar['admission_type'],
        'admission_type_org'             => $seminar['admission_type'],
        'admission_selection_take_place' => $seminar['admission_selection_take_place'],
        'admission_endtime'              => $seminar['admission_endtime'],
        'admission_binding'              => (int)$seminar['admission_binding'],
        'sem_id'                         => $seminar_id,
        'heimat_inst_id'                 => $seminar['Institut_id'],
        'passwort'                       => $seminar['Passwort'],
        'name'                           => $seminar['Name'],
        'status'                         => $seminar['status'],
        'start_time'                     => $seminar['start_time'],
        'read_level'                     => $seminar['Lesezugriff'],
        'write_level'                    => $seminar['Schreibzugriff'],
        'admission_prelim'               => $seminar['admission_prelim'],
        'admission_prelim_txt'           => $seminar['admission_prelim_txt'],
        'sem_admission_start_date'       => $seminar['admission_starttime'],
        'sem_admission_end_date'         => $seminar['admission_endtime_sem'],
        'admission_disable_waitlist'     => $seminar['admission_disable_waitlist'],
        'admission_disable_waitlist_org' => $seminar['admission_disable_waitlist'],
        'admission_enable_quota'         => $seminar['admission_enable_quota'],
        'admission_enable_quota_org'     => $seminar['admission_enable_quota'],
    );
    if ($admin_admission_data['admission_endtime'] <= 0){
        $admin_admission_data['admission_endtime'] = veranstaltung_beginn($seminar_id, 'int');
        if (!$admin_admission_data['admission_endtime']) {
            $admin_admission_data['admission_endtime'] = -1;
        }
    }
    
    $query = "SELECT ass.studiengang_id, name, quota, COUNT(asu.user_id) + COUNT(su.user_id) AS count
              FROM admission_seminar_studiengang AS ass
              LEFT JOIN studiengaenge USING (studiengang_id)
              LEFT JOIN admission_seminar_user AS asu USING (seminar_id, studiengang_id)
              LEFT JOIN seminar_user AS su
                ON (ass.seminar_id = su.seminar_id AND admission_studiengang_id = ass.studiengang_id)
              WHERE ass.seminar_id = ?
              GROUP BY ass.studiengang_id
              ORDER BY ass.studiengang_id != 'all', name";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $name = $row['studiengang_id'] == 'all' ? _('Alle Studiengänge') : $row['name'];
        $admin_admission_data['studg'][$row['studiengang_id']] = array(
            'name'  => $name,
            'ratio' => $row['quota'],
            'count' => $row['count'],
        );
    }
    $admin_admission_data["original"]=get_snapshot();
    if (Request::submitted("reset_admission_time")){
        $admin_admission_data["sem_admission_end_date"]=-1;
        $admin_admission_data["sem_admission_start_date"]=-1;
    }

    // save the values of the admin_admission_data
    $admin_admission_data_original = $admin_admission_data;

//nur wenn wir schon Daten haben kann was zurueckkommen
} else {
    //Sicherheitscheck ob ueberhaupt was zum Bearbeiten gewaehlt ist.
    if (!$admin_admission_data["sem_id"]) {
        echo "</td></tr></table>";
        die;
    }

    //check start / enddate
    if (!check_and_set_date(Request::quoted('adm_s_tag'), Request::quoted('adm_s_monat'), Request::quoted('adm_s_jahr'),
            Request::quoted('adm_s_stunde'), Request::quoted('adm_s_minute'), $admin_admission_data, "sem_admission_start_date")) {
        $errormsg=$errormsg."error§"._("Bitte geben Sie g&uuml;ltige Zeiten f&uuml;r das Startdatum ein!")."§";
    }
    if (!check_and_set_date(Request::quoted('adm_e_tag'), Request::quoted('adm_e_monat'), Request::quoted('adm_e_jahr')
            , Request::quoted('adm_e_stunde'), Request::quoted('adm_e_minute'), $admin_admission_data, "sem_admission_end_date")) {
        $errormsg=$errormsg."error§"._("Bitte geben Sie g&uuml;ltige Zeiten f&uuml;r das Enddatum ein!")."§";
    }
    if ($admin_admission_data["sem_admission_end_date"] != "-1") {
        if ($admin_admission_data["sem_admission_end_date"] < time()) {
            $errormsg=$errormsg."error§"._("Das Enddatum liegt in der Vergangenheit. Bitte geben Sie ein g&uuml;ltiges Enddatum ein!")."§";
        }
        if ($admin_admission_data["sem_admission_end_date"] <= $admin_admission_data["sem_admission_start_date"]) {
            $errormsg=$errormsg."error§"._("Das Startdatum muss vor dem Enddatum liegen!")."§";
        }
    }

    //Umschalter zwischen den Typen
    if (Request::submittedSome("adm_null", "adm_los", "adm_chrono", "adm_gesperrt")) {
        if ($is_grouped){
            $errormsg = $errormsg."error§"._("Gruppierte Veranstaltungen m&uuml;ssen ein einheitliches Anmeldeverfahren haben! Bei gruppierten Veranstaltungen können Sie das Anmeldeverfahren an dieser Stelle nicht mehr ändern.")."§";
        } else {
            $admin_admission_data["sem_admission_end_date"]=-1;
            $admin_admission_data["sem_admission_start_date"]=-1;
            $admin_admission_data["admission_endtime"]=-1;
            $admin_admission_data["admission_selection_take_place"] = 0;

            if (Request::submitted("adm_null")) {
                $admin_admission_data["admission_type"]=0;
            }

            if (Request::submitted("adm_los")) {
                $admin_admission_data["admission_type"]=1;
                if(!is_array($admin_admission_data["studg"]) || !count($admin_admission_data["studg"])) $admin_admission_data["studg"]['all'] = array('name' => _("Alle Studiengänge"), 'ratio' => 100);
            }
            if (Request::submitted("adm_chrono")) {
                $admin_admission_data["admission_type"]=2;
                if(!is_array($admin_admission_data["studg"]) || !count($admin_admission_data["studg"])) $admin_admission_data["studg"]['all'] = array('name' => _("Alle Studiengänge"), 'ratio' => 100);
            }
            if (Request::submitted("adm_gesperrt")) {
                $admin_admission_data["admission_type"] = 3;
            }
        }
    }

    //Aenderungen ubernehmen
    $admin_admission_data["admission_binding"]=Request::option('admission_binding');
    if ($admin_admission_data["admission_binding"])
        $admin_admission_data["admission_binding"]=TRUE;
    settype($admin_admission_data["admission_binding"], 'integer');

    if(Request::quoted('admission_turnout')) $admin_admission_data["admission_turnout"] = Request::quoted('admission_turnout');

  if (Request::quoted('admission_prelim_txt'))
  {
    $admin_admission_data["admission_prelim_txt"]=Request::quoted('admission_prelim_txt');
  }

  if (Request::submitted('uebernehmen') && Request::option('admission_waitlist')) {
      $admin_admission_data["admission_disable_waitlist"] = (int)(!Request::int("admission_waitlist"));
  }


  if (!$admin_admission_data["admission_type"]) {
    if (strlen(Request::option('read_level'))) // we need strlen to check for $read_level = 0
        $admin_admission_data["read_level"]=Request::option('read_level');
    if (strlen(Request::option('write_level'))) // we need strlen to check for $write_level = 0
        $admin_admission_data["write_level"]=Request::option('write_level');
    if($admin_admission_data["write_level"] < 2 && $admin_admission_data["read_level"] < 2) $admin_admission_data["passwort"] = "";

    //Alles was mit der Anmeldung zu tun hat ab hier
    } elseif (!$delete_studg) {


      if (Request::submitted('toggle_admission_quota')){
            $admin_admission_data["admission_enable_quota"] = (int)(Request::quoted("admission_enable_quota"));
            if(!$admin_admission_data["admission_enable_quota"]){
                $admin_admission_data["admission_endtime"] = -1;
                $admin_admission_data["admission_selection_take_place"] = 0;
            }

        }

        //Studienbereiche entgegennehmen
        if (Request::optionArray('studg_id')) {
            $studg_ratio_old = Request::optionArray('studg_ratio_old');
            $studg_ratio = Request::optionArray('studg_ratio');
            $studg_id = Request::optionArray('studg_id');
            foreach ($studg_id as $key=>$val)
                if ($studg_ratio_old[$key] != $studg_ratio[$key])
                    $admin_admission_data["admission_ratios_changed"]=TRUE;
            if ($admin_admission_data["admission_ratios_changed"]) {
                $admin_admission_data["studg"]='';
                $studg_name = Request::getArray('studg_name');
                foreach ($studg_id as $key=>$val)
                    $admin_admission_data["studg"][$val]=array("name"=>$studg_name[$key], "ratio"=>$studg_ratio[$key]);
            }
        }

    }
    $add_ratio = Request::quoted('add_ratio');
    //Studiengang hinzufuegen
    if (Request::submitted("add_studg")) {
        $add_studg = Request::option('add_studg');
        if ($add_studg && $add_studg != 'all') {
            $query = "SELECT name FROM studiengaenge WHERE studiengang_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($add_studg));
            $name = $statement->fetchColumn();

            $admin_admission_data['studg'][$add_studg] = array(
                'name'  => $name,
                'ratio' => $add_ratio
            );
        } else if (Request::option('add_studg') == 'all'){
            $admin_admission_data["studg"][Request::option('add_studg')]=array("name"=>_("Alle Studiengänge"), "ratio"=>$add_ratio);
        }
    }

    //Studiengang loeschen
    if ($delete_studg)
        if (count($admin_admission_data["studg"])>1) {
            unset($admin_admission_data["studg"][$delete_studg]);
        } else {
            $errormsg=$errormsg."error§"._("Es muss mindestens ein Studiengang eingetragen bleiben.")."§";
        }

    //Checks performen
    if (!$admin_admission_data["admission_type"]) {
        if (($admin_admission_data["write_level"]) <($admin_admission_data["read_level"]))
            $errormsg=$errormsg."error§"._("Es macht keinen Sinn, die Sicherheitsstufe f&uuml;r den Lesezugriff h&ouml;her zu setzen als f&uuml;r den Schreibzugriff!")."§";

        if (($admin_admission_data["read_level"] == 2 || $admin_admission_data["write_level"] == 2) && !(LockRules::Check($seminar_id, 'Passwort')) ) {
            //Password bei Bedarf dann doch noch verschlusseln
            if (!Request::quoted('sem_passwd'))
                $admin_admission_data["passwort"] = "";
            elseif(Request::quoted('sem_passwd') != "*******") {
                $admin_admission_data["passwort"] = md5(Request::quoted('sem_passwd'));
                if(Request::quoted('sem_passwd2') != "*******")
                    $check_pw = md5(Request::quoted('sem_passwd2'));
            }

            if ($admin_admission_data["passwort"]=="")
                    $errormsg=$errormsg."error§"._("Sie haben kein Passwort eingegeben! Bitte geben Sie ein Passwort ein!")."§";
                elseif (isset($check_pw) AND $admin_admission_data["passwort"] != $check_pw) {
                    $errormsg=$errormsg."error§"._("Das eingegebene Passwort und das Wiederholungspasswort stimmen nicht &uuml;berein!")."§";
                        $admin_admission_data["passwort"] = "";
            }
        }

    //Checks bei Anmeldeverfahren
    } elseif (!Request::submittedSome("adm_chrono", "adm_los", "adm_gesperrt"))  {
        //max. Teilnehmerzahl checken
        if (Request::submitted('uebernehmen') && ($admin_admission_data["admission_type"] > 0) && ($admin_admission_data["admission_type"] < 3)) {
            if ($admin_admission_data["admission_turnout"] < 1) {
                $errormsg=$errormsg."error§"._("Wenn Sie die Teilnahmebeschr&auml;nkung benutzen wollen, m&uuml;ssen Sie wenigstens einen Teilnehmer zulassen.")."§";
                $admin_admission_data["admission_turnout"] =1;
            }

            //we have to perform some checks more, if we change the turnout-parameter from an already saved admission
            if ($admin_admission_data["admission_type_org"]) {
                if ($admin_admission_data["admission_turnout"] < $admin_admission_data["admission_turnout_org"])
                    $infomsg.= "info§" . _("Diese Veranstaltung ist teilnahmebeschr&auml;nkt. Wenn Sie die Anzahl der Teilnehmenden verringern, m&uuml;ssen Sie evtl. NutzerInnen, die bereits einen Platz in der Veranstaltung erhalten haben, manuell entfernen!") . "§";

                if ($admin_admission_data["admission_turnout"] > $admin_admission_data["admission_turnout_org"])
                    $do_update_admission=TRUE;
            }
        }

        //Prozentangabe checken/berechnen wenn neueer Studiengang, einer geloescht oder Seite abgeschickt
        if (Request::submittedSome("add_studg", "uebernehmen", "toggle_admission_quota") || $delete_studg) {
            if ($admin_admission_data["admission_type"] && $admin_admission_data["admission_enable_quota"]) {
                if ((!$admin_admission_data["admission_ratios_changed"]) && (!$add_ratio) && (!$admin_admission_data["admission_type_org"])) {//User hat nichts veraendert oder neuen Studiengang mit Wert geschickt, wir koennen automatisch rechnen
                    if (is_array($admin_admission_data["studg"])){
                        foreach ($admin_admission_data["studg"] as $key=>$val){
                            $admin_admission_data["studg"][$key]["ratio"] = round(100 / (sizeof ($admin_admission_data["studg"]) ));
                        }
                    }
                } else {
                    $cnt = 0;
                    if (is_array($admin_admission_data["studg"]) && count($admin_admission_data["studg"]) > 1){
                        foreach ($admin_admission_data["studg"] as $key => $val){
                            $cnt+=$val["ratio"];
                        }
                        if ($cnt <= 100)
                            $admin_admission_data["studg"][$key]["ratio"] = (100 - $cnt + $val["ratio"]);
                        else
                            $errormsg.= "error§". _("Die Werte der einzelnen Kontigente &uuml;bersteigen 100%. Bitte &auml;ndern Sie die Kontigente!") . "§";
                    } else {
                        reset($admin_admission_data["studg"]);
                        $admin_admission_data["studg"][key($admin_admission_data["studg"])]["ratio"] = 100;
                    }
                }
            }
        }

        //Ende der Anmeldung checken
        if (Request::submitted("uebernehmen") && (!in_array($admin_admission_data["admission_type_org"], array(1,2)) || $perm->have_perm("admin")) ) {
            if (!check_and_set_date(Request::get('adm_tag'), Request::get('adm_monat'), Request::get('adm_jahr'), Request::get('adm_stunde'), Request::get('adm_minute'), $admin_admission_data, "admission_endtime") || !$admin_admission_data["admission_endtime"]) {
                $admin_admission_data["admission_endtime"] = -1;
                if ($admin_admission_data["admission_type"] == 1) {
                    $errormsg=$errormsg."error§"._("Bitte geben Sie g&uuml;ltige Zeiten f&uuml;r das Losdatum ein!")."§";
                } else {
                    $errormsg=$errormsg."error§"._("Bitte geben Sie g&uuml;ltige Zeiten f&uuml;r das Enddatum der Kontingentierung ein!")."§";
                }
            }
           
            if($admin_admission_data['admission_endtime'] <
                   $admin_admission_data['sem_admission_start_date'])
            {
                $errormsg=$errormsg."error§"._("Das Losdatum darf nicht vor dem Start des Anmeldezeitraums liegen!")."§";
            }
            if (($admin_admission_data["admission_type"]) && ($admin_admission_data["admission_endtime"]) && ($admin_admission_data["admission_type"]!=3)) {
                if ($admin_admission_data["admission_type"] == 1)
                    $end_date_name=_("Losdatum");
                else
                    $end_date_name=_("Enddatum der Kontingentierung");
                if ($admin_admission_data["admission_endtime"] == -1 && ($admin_admission_data["admission_enable_quota"] || $admin_admission_data["admission_type"] == 1))
                    $errormsg.="error§". sprintf(_("Bitte geben Sie einen Termin für das %s an!"),$end_date_name)."§";
                $tmp_first_date = veranstaltung_beginn($admin_admission_data['sem_id'], 'int');
                if($admin_admission_data["admission_type"] == 1){
                    if ($admin_admission_data["admission_endtime"] > $tmp_first_date)
                        if ($tmp_first_date > 0) {
                            if ($admin_admission_data["admission_type"] == 1)
                                $errormsg.= sprintf ("error§"._("Das Losdatum liegt nach dem ersten Veranstaltungstermin am %s. Bitte &auml;ndern Sie das Losdatum!")."§", date ("d.m.Y", $tmp_first_date));
                            else
                                $errormsg.= sprintf ("error§"._("Das Enddatum der Kontingentierung liegt nach dem ersten Veranstaltungstermin am %s. Bitte &auml;ndern Sie das Enddatum!")."§", date ("d.m.Y", $tmp_first_date));
                        }
                    if (!$admin_admission_data["admission_selection_take_place"]) {
                        if (($admin_admission_data["admission_endtime"] < time()) && ($admin_admission_data["admission_endtime"] != -1)) {
                            if ($admin_admission_data["admission_type"] == 1)
                                $errormsg.=sprintf ("error§"._("Das Losdatum liegt in der Vergangenheit. Bitte &auml;ndern Sie das Losdatum!")."§");
                            else
                                $errormsg.=sprintf ("error§"._("Das Enddatum der Kontingentierung liegt in der Vergangenheit. Bitte &auml;ndern Sie das Datum!")."§");
                        } elseif (($admin_admission_data["admission_endtime"] < (time() + (24 * 60 *60))) && ($admin_admission_data["admission_endtime"] != -1)) {
                            if ($admin_admission_data["admission_type"] == 1)
                                $errormsg.=sprintf ("error§"._("Das Losdatum muss mindestens einen Tag in der Zukunft liegen!")."§");
                            else
                                $errormsg.=sprintf ("error§"._("Das Enddatum der Kontingentierung muss mindestens einen Tag in der Zukunft liegen!")."§");
                        }
                    }
                } else {
                    if($admin_admission_data["admission_endtime"] > time() && $admin_admission_data["admission_selection_take_place"]){
                        $admin_admission_data["admission_selection_take_place"] = 0;
                    }
                }
            }
    }
    }

    //Meldung beim Wechseln des Modis
    if ((Request::option('adm_type_old') != $admin_admission_data["admission_type"]) && (!Request::option('commit_no_admission_data')))
        if ($admin_admission_data["admission_type"] > 0 && $admin_admission_data["admission_type"] != 3 )
            $infomsg.=sprintf ("info§"._("Sie haben ein Anmeldeverfahren vorgesehen. Beachten Sie bitte, dass nach dem &Uuml;bernehmen dieser Einstellung alle bereits eingetragenen Nutzerinnen und Nutzer aus der Veranstaltung entfernt werden und das Anmeldeverfahren anschließend nicht mehr abgeschaltet werden kann!")."§");


    //Daten speichern
    if (Request::submitted("uebernehmen") && (!$errormsg)) {

    if (!LockRules::Check($seminar_id, 'admission_disable_waitlist') || $perm->have_perm('admin'))
        {
        //Warteliste aktivieren / deaktivieren
            if($admin_admission_data["admission_disable_waitlist"] != $admin_admission_data["admission_disable_waitlist_org"]){
                if($admin_admission_data["admission_disable_waitlist_org"] == 0){ //Warteliste war eingeschaltet
                    // Prepare statement that deletes user from the awaiting list
                    $query = "DELETE FROM admission_seminar_user
                              WHERE user_id = ? AND seminar_id = ? AND status = 'awaiting'";
                    $delete_statement = DBManager::get()->prepare($query);

                    // Prepare and execute statement that reads all awaiting
                    // users for a given seminar
                    $query = "SELECT user_id, username
                              FROM admission_seminar_user
                              LEFT JOIN auth_user_md5 USING (user_id)
                              WHERE seminar_id = ? AND status = 'awaiting'";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($admin_admission_data['sem_id']));

                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        $delete_statement->execute(array(
                            $row['user_id'],
                            $admin_admission_data['sem_id'],
                        ));
                        if ($delete_statement->rowCount()) {
                            setTempLanguage($row['user_id']);
                            $message= sprintf(_('Die Warteliste der Veranstaltung **%s** wurde von einem/r DozentIn oder AdministratorIn deaktiviert, Sie sind damit __nicht__ zugelassen worden.'), $admin_admission_data['name']);
                            $messaging->insert_message(addslashes($message), $row['username'], '____%system%____', FALSE, FALSE, '1', FALSE, _('Systemnachricht:').' '._('nicht zugelassen in Veranstaltung'), TRUE);
                            restoreLanguage();
                        }
                    }
                }
            }
        }

        if (!LockRules::Check($seminar_id, 'admission_prelim'))
        {
        //for admission it have to be always 3
        if (Request::option('admission_prelim') == 1) {
            if ($admin_admission_data["admission_prelim"] == 0) { //we have to move the students to status "temporaly accepted", if put on
                // Prepare statement that deletes a given user from a given seminar
                $query = "DELETE FROM seminar_user
                          WHERE user_id = ? AND Seminar_id = ?";
                $delete_statement = DBManager::get()->prepare($query);

                // Prepare statement that inserts a given user into a seminar
                // with the status "temporarily accepted"
                $query = "INSERT INTO admission_seminar_user
                            (user_id, seminar_id, studiengang_id, mkdate, status)
                          VALUES (?, ?, ?, ?, 'accepted')";
                $insert_statement = DBManager::get()->prepare($query);

                // Prepare and execute statement that obtains all users with
                // the status 'autor' for a given seminar
                $query = "SELECT user_id, username, Seminar_id, admission_studiengang_id, mkdate
                          FROM seminar_user AS su
                          LEFT JOIN auth_user_md5 USING (user_id)
                          WHERE Seminar_id = ? AND su.status = 'autor'";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($admin_admission_data['sem_id']));
                
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $insert_statement->execute(array(
                        $row['user_id'],
                        $row['Seminar_id'],
                        $row['admission_studiengang_id'],
                        $row['mkdate'],
                    ));
                    $delete_statement->execute(array($row['user_id'], $row['Seminar_id']));

                    $message=sprintf(_('Sie wurden in der Veranstaltung **%s** in den Status **vorläufig akzeptiert** befördert, da das Anmeldeverfahren geändert wurde.'), $admin_admission_data['name']);
                    $messaging->insert_message(addslashes($message), $row['username'], '____%system%____', FALSE, FALSE, '1', FALSE, _('Systemnachricht:').' '._('vorläufig akzeptiert'), TRUE);
                    RemovePersonStatusgruppeSeminar($row['username'], $admin_admission_data['sem_id']);
                }

                // Prepare and execute statement that obtains all users
                // from a given seminar with the status 'user'
                $query = "SELECT username
                          FROM seminar_user AS su
                          LEFT JOIN auth_user_md5 USING (user_id)
                          WHERE Seminar_id = ? AND su.status = 'user'";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($admin_admission_data['sem_id']));
                $usernames = $statement->fetchAll(PDO::FETCH_ASSOC);

                // Prepare and execute statement that deletes all users with
                // the status 'user' from a given seminar
                $query = "DELETE FROM seminar_user
                          WHERE Seminar_id = ? AND status = 'user'";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($admin_admission_data['sem_id']));
                $deleted = $statement->rowCount();
                
                if ($deleted > 0) {
                    foreach ($usernames as $username) {
                        $message = sprintf(_('Ihr Abonnement der Veranstaltung **%s** wurde aufgehoben, da die Veranstaltung mit einem teilnahmebeschränkten Anmeldeverfahren versehen wurde. \nWenn Sie einen Platz in der Veranstaltung bekommen wollen, melden Sie sich bitte erneut an.'), $admin_admission_data['name']);
                        $messaging->insert_message(addslashes($message), $username, '____%system%____', FALSE, FALSE, '1', FALSE, _('Systemnachricht:').' '._('Abonnement aufgehoben'), TRUE);
                         RemovePersonStatusgruppeSeminar($username, $admin_admission_data['sem_id']);
                    }
                }

                // Prepare and execute statement that updates the admission
                // setting for a given seminar
                $query = "UPDATE seminar SET admission_prelim = 1 WHERE Seminar_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($admin_admission_data['sem_id']));

                $admin_admission_data['admission_prelim'] = 1;
            }
        } elseif (!Request::option('commit_no_admission_data') && Request::option('admission_prelim') == 0) {
            if ($admin_admission_data["admission_prelim"] == 1) { //we have to move the students again
                if (!$perm->have_perm("admin")) {
                    $errormsg.=sprintf ("error§"._("Sie dürfen den Anmeldemodus nicht mehr verändern! Wenden Sie sich ggf. an den zuständigen Admin.")."§");
                } else {
                    // Prepare statement that inserts a given user into a
                    // given seminar with the status 'autor'
                    $query = "INSERT INTO seminar_user
                                (user_id, Seminar_id, admission_studiengang_id, mkdate, status)
                              VALUES (?, ?, ?, ?, 'autor')";
                    $insert_statement = DBManager::get()->prepare($query);

                    // Prepare statement that deletes a given user from the
                    // admission list for a given seminar
                    $query = "DELETE FROM admission_seminar_user
                              WHERE user_id = ? AND seminar_id = ?";
                    $delete_statement = DBManager::get()->prepare($query);

                    // Prepare and execute statement that obtains all users
                    // with the status 'accepted' from an admission list for 
                    // a given seminar
                    $query = "SELECT user_id, seminar_id, studiengang_id, mkdate, username
                              FROM admission_seminar_user AS asu
                              LEFT JOIN auth_user_md5 USING (user_id)
                              WHERE asu.seminar_id = ? AND asu.status = 'accepted'";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($admin_admission_data['sem_id']));
                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        $insert_statement->execute(array(
                            $row['user_id'],
                            $row['seminar_id'],
                            $row['studiengang_id'],
                            $row['mkdate'],
                        ));
                        $delete_statement->execute(array(
                            $row['user_id'],
                            $row['seminar_user'],
                        ));

                        $message = sprintf(_('Sie wurden in der Veranstaltung **%s** in den Status **Autor** versetzt, da das Anmeldeverfahren geändert wurde.'), $admin_admission_data['name']);
                        $messaging->insert_message(addslashes($message), $row['username'], '____%system%____', FALSE, FALSE, '1', FALSE, _('Systemnachricht:').' '._('Statusänderung'), TRUE);
                    }

                    // Prepare and execute statement that updates the admission
                    // setting for a given seminar
                    $query = "UPDATE seminar SET admission_prelim = 0 WHERE Seminar_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($admin_admission_data['sem_id']));

                    $admin_admission_data['admission_prelim'] = 0;
                }
            }
        }
        }

        if ($admin_admission_data["admission_type"]) {
            $admin_admission_data["read_level"]=3;
            $admin_admission_data["write_level"]=3;
        }


        // find out what has been changed
        $log_message = get_readable_admission_difference( $admin_admission_data_original, $admin_admission_data );

        // LOGGING
        log_event('SEM_CHANGED_ACCESS', $admin_admission_data['sem_id'], NULL, implode("<br>", $log_message) );


        $data_mapping['admission_turnout'] = 'admission_turnout';
        $data_mapping['admission_type'] = 'admission_type';
        $data_mapping['admission_endtime'] = 'admission_endtime';
        $data_mapping['admission_binding'] = 'admission_binding';
        $data_mapping['admission_starttime'] = 'sem_admission_start_date';
        $data_mapping['admission_endtime_sem'] = 'sem_admission_end_date';
        $data_mapping['admission_prelim'] = 'admission_prelim';
        $data_mapping['admission_prelim_txt'] = 'admission_prelim_txt';

        $data_mapping['Passwort'] = 'passwort';
        $data_mapping['Lesezugriff'] = 'read_level';
        $data_mapping['Schreibzugriff'] = 'write_level';
        $data_mapping['admission_disable_waitlist'] = 'admission_disable_waitlist';
        $data_mapping['admission_selection_take_place'] = 'admission_selection_take_place';
        $data_mapping['admission_enable_quota'] = 'admission_enable_quota';

        $update_data = array();

        foreach($data_mapping as $db_field => $form_field)
        {
            if ( !LockRules::Check($seminar_id,$db_field))
            {
                $update_data[$db_field] = $admin_admission_data[$form_field];
            }
        }

        if (sizeof($update_data) > 0)
        {
            $query = "UPDATE seminare SET ";
            $columns = $parameters = array();

            $count = 0;
            foreach($update_data as $db_key => $value) {
                if ($count > 0) {
                    $query .= ', ';
                }

                $query .= ":column{$count} = :parameter{$count}";
                $columns[':column' . $count]       = $db_key;
                $parameters[':parameter' . $count] = $value;

                $count += 1;
            }

            $query .= " WHERE seminar_id = :seminar_id";

            $statement = DBManager::get()->prepare($query);
            foreach ($columns as $key => $column) {
                $statement->bindValue($key, $column, StudipPDO::PARAM_COLUMN);
            }
            foreach ($parameters as $key => $parameter) {
                $statement->bindValue($key, $parameter);
            }
            $statement->bindValue(':seminar_id', $admin_admission_data['sem_id']);
            $statement->execute();

            //check, if we need to update the admission data after saving new settings
            if ($do_update_admission) {
                update_admission($admin_admission_data['sem_id']);
            }

            if ($statement->rowCount()) {
                $errormsg.="msg§"._("Die Berechtigungseinstellungen f&uuml;r die Veranstaltung wurden aktualisiert")."§";
                
                $query = "UPDATE seminare SET chdate = UNIX_TIMESTAMP() WHERE Seminar_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($admin_admission_data['sem_id']));
            }

        }

        if (!LockRules::Check($seminar_id, 'admission_type'))
        {


        //Variante nachtraeglich Anmeldeverfahren starten, alle alten Teilnehmer muessen raus
        if (($admin_admission_data["admission_type"] >$admin_admission_data["admission_type_org"]) && ($admin_admission_data["admission_type_org"]==0) && $admin_admission_data["admission_type"]!=3) {
            $query = "SELECT username
                      FROM seminar_user
                      LEFT JOIN auth_user_md5 USING (user_id)
                      WHERE Seminar_id = ? AND status IN ('user', 'autor')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($admin_admission_data['sem_id']));
            $usernames = $statement->fetchAll(PDO::FETCH_COLUMN);

            $query = "DELETE FROM seminar_user
                      WHERE Seminar_id = ? AND status IN ('user', 'autor')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($admin_admission_data['sem_id']));

            if ($statement->rowCount()) {
                foreach ($usernames as $username) {
                    $message = sprintf(_("Ihr Abonnement der Veranstaltung **%s** wurde aufgehoben, da die Veranstaltung mit einem teilnahmebeschränkten Anmeldeverfahren versehen wurde. \nWenn Sie einen Platz in der Veranstaltung bekommen wollen, melden Sie sich bitte erneut an."), $admin_admission_data['name']);
                    $messaging->insert_message (addslashes($message), $username, '____%system%____', FALSE, FALSE, '1', FALSE, _('Systemnachricht:').' '._('Abonnement aufgehoben'), TRUE);
                    RemovePersonStatusgruppeSeminar($username, $admin_admission_data['sem_id']);
                }
            }

            //Kill old data
            $query = "DELETE FROM admission_seminar_studiengang WHERE seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($admin_admission_data['sem_id']));

            $admin_admission_data['write_level'] = '';
            $admin_admission_data['read_level']  = '';
            $admin_admission_data['passwort']    = '';
        }

        //Variante nachtraeglich Anmeldeverfahren beenden, alle aus Warteliste kommen in die Veranstaltung
        if (($admin_admission_data["admission_type"] == 0) && ($admin_admission_data["admission_type_org"] > 0)) {
            $query = "INSERT INTO seminar_user 
                        (user_id, Seminar_id, status, gruppe, mkdate)
                      VALUES (?, ?, 'autor', ?, UNIX_TIMESTAMP())";
            $insert_statement = DBManager::get()->prepare($query);

            $query = "SELECT user_id, username
                       FROM admission_seminar_user
                       LEFT JOIN auth_user_md5 USING (user_id)
                       WHERE seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($admin_admission_data['sem_id']));

            $inserted = 0;
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $group = select_group($admin_admission_data['start_time'], $row['user_id']);

                $insert_statement->execute(array(
                    $row['user_id'],
                    $admin_admission_data['sem_id'],
                    $group,
                ));

                $message = sprintf(_('Sie wurden in die Veranstaltung **%s** eingetragen, da das Anmeldeverfahren aufgehoben wurde. Damit sind Sie als Teilnehmer der Präsenzveranstaltung zugelassen.'), $admin_admission_data['name']);
                $messaging->insert_message(addslashes($message), $username, '____%system%____', FALSE, FALSE, '1', FALSE, _('Systemnachricht:').' '._('Eintragung in Veranstaltung'), TRUE);
                
                $inserted += 1;
            }
            
            if ($inserted) {
                $query = "DELETE FROM admission_seminar_user WHERE seminar_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($admin_admission_data['sem_id']));
            }

            //Kill old Studiengang entries and data
            $query = "DELETE FROM admission_seminar_studiengang WHERE seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($admin_admission_data['sem_id']));

            $admin_admission_data['studg']                    = '';
            $admin_admission_data['all_ratio']                = '';
            $admin_admission_data['admission_ratios_changed'] = '';
            $admin_admission_data['admission_endtime']        = '';
        }

        //Eintrag der zugelassen Studienbereiche
        if ($admin_admission_data["admission_type"]) {
            // Alle Eintraege rauswerfen
            $query = "DELETE FROM admission_seminar_studiengang WHERE seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($admin_admission_data['sem_id']));

            if (is_array($admin_admission_data['studg'])) {
                $query = "INSERT INTO admission_seminar_studiengang
                            (seminar_id, studiengang_id, quota)
                          VALUES (?, ?, ?)";
                $statement = DBManager::get()->prepare($query);

                foreach($admin_admission_data['studg'] as $key => $val) {
                    // Studiengang eintragen
                    $statement->execute(array(
                        $admin_admission_data['sem_id'],
                        $key,
                        $val['ratio'],
                    ));
                }
            }

            //Save the current state as snapshot to compare with current data
            $admin_admission_data["original"]=get_snapshot();

            // save the values of the admin_admission_data
            $admin_admission_data_original = $admin_admission_data;
        }

        //Save the current state as snapshot to compare with current data
        $admin_admission_data["original"] = get_snapshot();
        $admin_admission_data["admission_turnout_org"] = $admin_admission_data["admission_turnout"];
        $admin_admission_data["admission_type_org"] = $admin_admission_data["admission_type"];

        // save the values of the admin_admission_data
        $admin_admission_data_original = $admin_admission_data;
    }
}
}

if ($lockdata['description'] && LockRules::CheckLockRulePermission($seminar_id, $lockdata['permission'])){
    $infomsg .= "info§" . formatLinks($lockdata['description']);
}
//Beim Umschalten keine Fehlermeldung
if ($errormsg && !Request::submittedSome("uebernehmen", "adm_null", "adm_los", "adm_chrono", "adm_gesperrt", "add_studg") && !$delete_studg)
    $errormsg='';

//check, ob Warteliste gefüllt.
$query = "SELECT COUNT(*)
          FROM admission_seminar_user
          WHERE seminar_id = ? AND status = 'awaiting'";
$statement = DBManager::get()->prepare($query);
$statement->execute(array($seminar_id));
$num_waitlist = 0 + $statement->fetchColumn();

$num_all = $admin_admission_data["admission_turnout"];

if (is_array($admin_admission_data["studg"]) && $admin_admission_data["admission_turnout"]){
    foreach ($admin_admission_data["studg"] as $key => $val){
        if ($val["ratio"]) {
            $num_stg[$key] = round($admin_admission_data["admission_turnout"] * $val["ratio"] / 100);
            $num_all -= $num_stg[$key];
        }
    }
    if ($num_all < 0) $num_all = 0;
}
?>
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
    <?
    $errormsg.=$infomsg;
    if (isset($errormsg)) {
    ?>
    <tr>
        <td class="blank" colspan=2><br>
        <?parse_msg($errormsg);?>
        </td>
    </tr>
    <? } ?>
    <? if ($admin_admission_data["original"] != get_snapshot()) { ?>
    <tr>
        <td class="blank" colspan="2">
            <?= MessageBox::info(_("Diese Daten sind noch nicht gespeichert.")) ?>
        </td>
    </tr>
    <? } ?>
    <tr>
        <td class="blank" valign="top">
            <br>
            <blockquote>
            <b><?=_("Zugangsberechtigungen der Veranstaltung bearbeiten") ?></b><br><br>
            <?=_("Sie k&ouml;nnen hier die Zugangsberechtigungen bearbeiten.")?> <br>
            <?=_("Sie haben auf dieser Seite ebenfalls die M&ouml;glichkeit, ein Anmeldeverfahren f&uuml;r die Veranstaltung festzulegen.")?><br>
            </blockquote>
        </td>
        <td class="blank" align="right">
             <?= Assets::img("infobox/board2.jpg") ?>
        </td>
    </tr>
    <tr>
    <td class="blank" colspan="2">
    <form method="POST" name="Formular" action="<?=URLHelper::getLink()?>"
    <? if (!$admin_admission_data["admission_type"] && !(LockRules::Check($seminar_id, 'Passwort'))) echo " onSubmit=\"return doCrypt();\" "; ?>>
    <?= CSRFProtection::tokenTag() ?>
        <input type="hidden" name="admin_admission_data" value="<?= base64_encode(serialize($admin_admission_data)) ?>">
        <input type="hidden" name="admin_admission_data_original" value="<?= base64_encode(serialize($admin_admission_data_original)) ?>">
        <table width="99%" border="0" cellpadding="2" cellspacing="0" align="center">
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" align="center" colspan="3">
                <?= Button::createAccept(_("Übernehmen"), "uebernehmen") ?>
            </td>
        </tr>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">&nbsp;
                    
            </td>
            <td class="<? echo $cssSw->getClass() ?>"  colspan=2 align="left">
                <font size=-1><b><?=_("Anmeldeverfahren:")?></b><br></font>

                <? 
                    $admission_type_name = get_admission_description('admission_type', $admin_admission_data["admission_type_org"]);

                    if (($admin_admission_data["admission_type_org"] && $admin_admission_data["admission_type_org"] != 3) && (!$perm->have_perm("admin"))) {
                        $query = "SELECT username, {$_fullname_sql['full']} AS fullname
                                  FROM user_inst
                                  LEFT JOIN auth_user_md5 USING (user_id)
                                  LEFT JOIN user_info USING (user_id)
                                  WHERE institut_id = ? AND perms = 'admin'
                                  ORDER BY Nachname, Vorname";
                        $statement = DBManager::get()->prepare($query);
                        $statement->execute(array($admin_admission_data['heimat_inst_id']));
                        $users = $statement->fetchAll(PDO::FETCH_ASSOC);

                    if  (!count($user)) {
                        printf ("<font size=-1>"._("Sie haben ein Anmeldeverfahren aktiviert (%s). Dieser Schritt kann %s nicht %s r&uuml;ckg&auml;ngig gemacht werden! Bei Problemen wenden Sie sich bitte an eine Administratorin oder einen Administrator.")."<br></font>", $admission_type_name, "</font><font size=-1 color=\"red\"><b>", "</b></font><font size=-1>");
                    } else {
                        printf ("<font size=-1>"._("Sie haben ein Anmeldeverfahren aktiviert (%s). Dieser Schritt kann %s nicht %s r&uuml;ckg&auml;ngig gemacht werden! Bei Problemen wenden Sie sich bitte an eineN der hier aufgef&uuml;hrten AdministratorInnen.")."<br></font>", $admission_type_name, "</font><font size=-1 color=\"red\"><b>", "</b></font><font size=-1>");
                    }
                    printf ("<input type=\"HIDDEN\" name=\"commit_no_admission_data\" value=\"TRUE\">");
                    foreach ($users as $one_user) {
                        echo "<li><font size=-1><a href=\"". URLHelper::getLink('about.php?username='.$one_user['username']) ."\">". htmlReady($one_user['fullname']) ."</a></font></li>";
                    }
                } else { ?>
          <? if (LockRules::Check($seminar_id, 'admission_type')) : ?>
                <br>
                <? foreach(array(_("keins"), _("los"), _("chronologisch"), _("gesperrt")) as $type => $value) { ?>
                    <? if  ($admin_admission_data["admission_type"] == $type) { ?>
                        <?= Button::createAccept($value, array("disabled" => "disabled")) ?>
                    <? } else { ?>
                        <?= Button::create($value, array("disabled" => "disabled")) ?>
                    <? } ?>
                <? } ?>
               <br>&nbsp;&nbsp;<?= $lock_text ?>
          <? elseif(is_object($group_obj)) :
                        ?>
                        <font size="-1">
                        <?=_("Diese Veranstaltung ist Mitglied einer Gruppe. Die Art des Anmeldeverfahrens können Sie nur für die Gruppe insgesamt ändern.")?>
                        <br>
                        <a href="<?=URLHelper::getLink('show_admission.php?group_sem_x=1&group_id='.$group_obj->getId())?>">
                        <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/black/schedule.png" border="0"> <?=_("Gruppenverwaltung")?></a>
                        <div style="margin-top:5px;">
                        <?=_("Veranstaltungsgruppe:")?>&nbsp;<?=htmlReady($group_obj->getValue('name'))?>
                        <ol>
                        <?foreach($group_obj->getMemberIds() as $m_id){?>
                            <li><a href="<?=URLHelper::getLink('admin_admission.php?select_sem_id='.$m_id)?>"><?=htmlReady($group_obj->members[$m_id]->getName())?></a></li>
                        <?}?>
                        </ol>
                        </div>
                        </font>
                        <?
                    else : ?>
                        <font size=-1><?=_("Sie k&ouml;nnen hier eine Teilnahmebeschr&auml;nkung per Anmeldeverfahren festlegen. Sie k&ouml;nnen per Losverfahren beschr&auml;nken oder Anmeldungen in der Reihenfolge ihres Eintreffens (chronologische Anmeldung) zulassen. Wenn Sie eine Veranstaltung sperren, kann sich niemand zu dieser Veranstaltung anmelden. Bestehende Teilnehmer- und Wartelisteneintr&auml;ge bleiben bei einem Wechsel von <b>keins</b> auf <b>gesperrt</b> unber&uuml;hrt.")?><br></font>
                        <br>
                        <div class="button-group">
                          <?= Button::create(_("Keins"), "adm_null", array("class" => ($admin_admission_data["admission_type"] == 0 ? "accept" : "" ))) ?>
                          <?= Button::create(_("Los"), "adm_los", array("class" => ($admin_admission_data["admission_type"] == 1 ? "accept" : "" ))) ?>
                          <?= Button::create(_("Chronologisch"), "adm_chrono", array("class" => ($admin_admission_data["admission_type"] == 2 ? "accept" : "" ))) ?>
                          <?= Button::create(_("Gesperrt"), "adm_gesperrt", array("class" => ($admin_admission_data["admission_type"] == 3 ? "accept" : "" ))) ?>
                        </div>
                <? endif; ?>

                <input type="hidden" name="adm_type_old" value="<? echo $admin_admission_data["admission_type"] ?>"><br>

                <? } ?>

            </td>
        </tr>
        <? if ($admin_admission_data["admission_type"] != 3) : ?>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" width="4%"></td>
            <td class="<? echo $cssSw->getClass() ?>" colspan="2">
                    <font size=-1><b>&nbsp;<?= _("Start- und Endzeit:") ?></b><br></font>
                    <font size=-1>&nbsp;<?= _("Sie k&ouml;nnen hier angeben, in welchem Zeitraum eine Anmeldung f&uuml;r die Veranstaltung m&ouml;glich ist.") ?><br></font>
            </td>
        </tr>
        <tr>
            <td class="<? echo $cssSw->getClass() ?>" width="4%"></td>
            <td class="<? echo $cssSw->getClass() ?>" colspan="2">
                <table border=0 cellpadding=2 cellspacing=0 align="center" width="100%">
                <tr>
                    <td class="<? echo $cssSw->getClass() ?>" valign="top" align="right" width="10%">
                        <font size=-1><? echo _("Startdatum f&uuml;r Anmeldungen");?>:</font>
                    </td>
                    <td class="<? echo $cssSw->getClass() ?>" valign="top" width="30%">
            <? if (! LockRules::Check($seminar_id, 'admission_starttime')) :?>
                            <font size=-1>&nbsp; <input type="text" name="adm_s_tag" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_start_date"]<>-1) echo date("d",$admin_admission_data["sem_admission_start_date"]); else echo _("tt") ?>">.
                                <input type="text" name="adm_s_monat" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_start_date"]<>-1) echo date("m",$admin_admission_data["sem_admission_start_date"]); else echo _("mm") ?>">.
                <input type="text" name="adm_s_jahr" size=4 maxlength=4 value="<? if ($admin_admission_data["sem_admission_start_date"]<>-1) echo date("Y",$admin_admission_data["sem_admission_start_date"]); else echo _("jjjj") ?>"><?="&nbsp;"._("um");?>&nbsp;</font><br>
                <font size=-1>&nbsp; <input type="text" name="adm_s_stunde" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_start_date"]<>-1) echo date("H",$admin_admission_data["sem_admission_start_date"]); else echo _("hh") ?>">:
                <input type="text" name="adm_s_minute" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_start_date"]<>-1) echo date("i",$admin_admission_data["sem_admission_start_date"]); else  echo _("mm") ?>">&nbsp;<?=_("Uhr");?>
              </font>
                            <?=Termin_Eingabe_javascript(20,0,($admin_admission_data["sem_admission_start_date"] != -1 ? $admin_admission_data["sem_admission_start_date"] : 0));?>
            <? else: ?>
                            <font size=-1>&nbsp;
                                <input disabled readonly type="text" name="adm_s_tag" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_start_date"]<>-1) echo date("d",$admin_admission_data["sem_admission_start_date"]); else echo _("tt") ?>">.
                                <input disabled readonly type="text" name="adm_s_monat" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_start_date"]<>-1) echo date("m",$admin_admission_data["sem_admission_start_date"]); else echo _("mm") ?>">.
                  <input disabled readonly type="text" name="adm_s_jahr" size=4 maxlength=4 value="<? if ($admin_admission_data["sem_admission_start_date"]<>-1) echo date("Y",$admin_admission_data["sem_admission_start_date"]); else echo _("jjjj") ?>"><?="&nbsp;"._("um");?>&nbsp;
                </font><br>
                            <font size=-1>&nbsp;
                    <input disabled readonly type="text" name="adm_s_stunde" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_start_date"]<>-1) echo date("H",$admin_admission_data["sem_admission_start_date"]); else echo _("hh") ?>">:
                    <input disabled readonly type="text" name="adm_s_minute" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_start_date"]<>-1) echo date("i",$admin_admission_data["sem_admission_start_date"]); else  echo _("mm") ?>">&nbsp;<?=_("Uhr");?>
                </font>
              <br>
              <?= $lock_text ?>
            <? endif; ?>
                    </td>
                    <td class="<? echo $cssSw->getClass() ?>" valign="top" align="right" width="10%">
                        <font size=-1><? echo _("Enddatum f&uuml;r Anmeldungen");?>:</font>
                    </td>
                    <td class="<? echo $cssSw->getClass() ?>" valign="top" width="30%">
            <? if (! LockRules::Check($seminar_id, 'admission_starttime')) :?>
              <font size=-1>&nbsp;
                  <input type="text" name="adm_e_tag" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_end_date"]<>-1) echo date("d",$admin_admission_data["sem_admission_end_date"]); else echo _("tt") ?>">.
                  <input type="text" name="adm_e_monat" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_end_date"]<>-1) echo date("m",$admin_admission_data["sem_admission_end_date"]); else echo _("mm") ?>">.
                  <input type="text" name="adm_e_jahr" size=4 maxlength=4 value="<? if ($admin_admission_data["sem_admission_end_date"]<>-1) echo date("Y",$admin_admission_data["sem_admission_end_date"]); else echo _("jjjj") ?>"><?="&nbsp;"._("um");?>&nbsp;
                </font><br>
                            <font size=-1>&nbsp;
                    <input type="text" name="adm_e_stunde" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_end_date"]<>-1) echo date("H",$admin_admission_data["sem_admission_end_date"]); else echo "23" ?>">:
                    <input type="text" name="adm_e_minute" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_end_date"]<>-1) echo date("i",$admin_admission_data["sem_admission_end_date"]); else echo "59" ?>">&nbsp;<?=_("Uhr");?>
                </font>
              <?=Termin_Eingabe_javascript(21,0,($admin_admission_data["sem_admission_end_date"] != -1 ? $admin_admission_data["sem_admission_end_date"] : 0));?>
                    </td>
                    <td class="<? echo $cssSw->getClass() ?>" >
                      <?= Button::create(_("Löschen"), "reset_admission_time",
                                         array("title" => _("Start- und Enddatum zurücksetzen"))) ?>
            <? else: ?>
              <font size=-1>&nbsp;
                  <input disabled readonly type="text" name="adm_e_tag" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_end_date"]<>-1) echo date("d",$admin_admission_data["sem_admission_end_date"]); else echo _("tt") ?>">.
                  <input disabled readonly type="text" name="adm_e_monat" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_end_date"]<>-1) echo date("m",$admin_admission_data["sem_admission_end_date"]); else echo _("mm") ?>">.
                  <input disabled readonly type="text" name="adm_e_jahr" size=4 maxlength=4 value="<? if ($admin_admission_data["sem_admission_end_date"]<>-1) echo date("Y",$admin_admission_data["sem_admission_end_date"]); else echo _("jjjj") ?>"><?="&nbsp;"._("um");?>&nbsp;
                </font><br>
                            <font size=-1>&nbsp;
                    <input disabled readonly type="text" name="adm_e_stunde" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_end_date"]<>-1) echo date("H",$admin_admission_data["sem_admission_end_date"]); else echo "23" ?>">:
                    <input disabled readonly type="text" name="adm_e_minute" size=2 maxlength=2 value="<? if ($admin_admission_data["sem_admission_end_date"]<>-1) echo date("i",$admin_admission_data["sem_admission_end_date"]); else echo "59" ?>">&nbsp;<?=_("Uhr");?>
                </font>
              <br>
              <?= $lock_text ?>
            <? endif; ?>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <? endif ?>

        <? if ($admin_admission_data["admission_type"] != 3) : ?>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" width="4%"></td>
            <td class="<? echo $cssSw->getClass() ?>" colspan="2">
                <font size=-1>
                    <?
                    if ((!$perm->have_perm("admin")) && ($admin_admission_data["admission_prelim"] == 1)) {
                        $query = "SELECT username, {$_fullname_sql['full']} AS fullname
                                  FROM user_inst
                                  LEFT JOIN auth_user_md5 USING (user_id)
                                  LEFT JOIN user_info USING (user_id)
                                  WHERE institut_id = ? AND perms = 'admin'";
                        $statement = DBManager::get()->prepare($query);
                        $statement->execute(array($admin_admission_data['heimat_inst_id']));
                        $users = $statement->fetchAll(PDO::FETCH_ASSOC);

                        echo "<b>"._("Anmeldemodus:")."</b><br>";
                        echo _("Sie haben den Anmeldemodus \"Vorl&auml;ufiger Eintrag\" aktiviert. ");
                        printf ("<font size=-1>"._("Dieser Schritt kann %s nicht %s r&uuml;ckg&auml;ngig gemacht werden! ")."</font>", "</font><font size=-1 color=\"red\"><b>", "</b></font><font size=-1>");
                        if (!count($users)) {
                            echo _("Bei Problemen wenden Sie sich bitte an eine Administratorin oder einen Administrator.");
                        } else {
                            echo _("Bei Problemen wenden Sie sich bitte an eineN der hier aufgef&uuml;hrten AdministratorInnen.");
                        }
                        printf ("<input type=\"HIDDEN\" name=\"commit_no_admission_data\" value=\"TRUE\">");
                        foreach ($users as $one_user) {
                            echo "<li><font size=-1><a href=\"". URLHelper::getLink('about.php?username='.$one_user['username']) ."\">". htmlReady($one_user['fullname']) ."</a></font></li>";
                        }
                    } else { ?>
                        <b><?=_("Anmeldemodus:")?></b><br>
            <? if (!LockRules::Check($seminar_id, 'admission_prelim')) : ?>
                        <? echo _("Bitte wählen Sie hier einen Anmeldemodus aus:"); ?><br>
              <input type="radio"  name="admission_prelim" <?if (LockRules::Check($seminar_id, 'admission_prelim')) {echo " disabled ";} ?>value="0" <? if ($admin_admission_data["admission_prelim"] == 0) echo "checked"; ?>><?=_("Direkter Eintrag")?>&nbsp;
              <input type="radio"  name="admission_prelim" <?if (LockRules::Check($seminar_id, 'admission_prelim')) {echo " disabled ";} ?>value="1" <? if ($admin_admission_data["admission_prelim"] == 1) echo "checked"; ?>><?=_("Vorl&auml;ufiger Eintrag")?>
            <? else: ?>
              <input disabled readonly type="radio"  name="admission_prelim" <?if (LockRules::Check($seminar_id, 'admission_prelim')) {echo " disabled ";} ?>value="0" <? if ($admin_admission_data["admission_prelim"] == 0) echo "checked"; ?>><?=_("Direkter Eintrag")?>&nbsp;
              <input disabled readonly type="radio"  name="admission_prelim" <?if (LockRules::Check($seminar_id, 'admission_prelim')) {echo " disabled ";} ?>value="1" <? if ($admin_admission_data["admission_prelim"] == 1) echo "checked"; ?>><?=_("Vorl&auml;ufiger Eintrag")?>
            <? endif; ?>
                    <? } ?>
                </font>

            </td>
        </tr>
        <? endif ?>
        <? if ($admin_admission_data["admission_prelim"] == 1) { ?>
            <tr>
            <td class="<? echo $cssSw->getClass() ?>" width="4%"></td>
            <td class="<? echo $cssSw->getClass() ?>" colspan="2">
        <font size=-1>
          <? echo _("Hinweistext bei vorl&auml;ufigen Eintragungen:"); ?>
        </font><br>
        <? if (! LockRules::Check($seminar_id, 'admission_prelim_txt')) : ?>
          <textarea name="admission_prelim_txt" cols=58 rows=4 ><?php echo htmlReady($admin_admission_data["admission_prelim_txt"]) ?></textarea>
        <? else : ?>
          <p>
          <textarea disabled readonly name="admission_prelim_txt" cols=58 rows=4 ><?php echo htmlReady($admin_admission_data["admission_prelim_txt"]) ?></textarea>
          </p>
          <br> <?= $lock_text; ?>
        <? endif; ?>
       </td>
        </tr>
        <?
        }
        if (!$admin_admission_data["admission_type"]  || $admin_admission_data["admission_type"] == 3) {
        ?>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">&nbsp;
                
            </td>
            <td class="<? echo $cssSw->getClass() ?>" colspan=2 align="left">
                <font size=-1><b><?=_("Berechtigungen:")?></b><br></font>
                <font size=-1><?=_("Legen Sie hier fest, welche Teilnehmer Zugriff auf die Veranstaltung haben.")?><br></font>
                <input type="hidden" name="admission_turnout" value="<? echo $admin_admission_data["admission_turnout"] ?>">
            </td>
        </tr>
        <tr>
            <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">&nbsp;
                
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="20%" align="left">
            <?
            if (!isset($admin_admission_data["read_level"]) || $admin_admission_data["read_level"]==3)
                $admin_admission_data["read_level"]= "1";   //Vorgabe: nur angemeldet oder es war Teilnahmebegrenzung gesetzt
                ?>
                <font size=-1><u><?=("Lesezugriff:")?></u> </font><br>
        <?if (! LockRules::Check($seminar_id, 'Lesezugriff')) : ?>
                <font size=-1>
                <?if (get_config('ENABLE_FREE_ACCESS')) {?>
                    <input type="radio" name="read_level" value="0" <?= $admin_admission_data["read_level"] == 0 ? "checked" : ""?>> <?=_("freier Zugriff")?><br>
                <?} else {?>
                    <input type="radio" name="read_level" disabled> <span class="quiet"><?=_("freier Zugriff")?></span><br>
                <?}?>
                <input type="radio" name="read_level" value="1" <?= $admin_admission_data["read_level"] == 1 ? "checked" : ""?>> <?=_("in Stud.IP angemeldet")?><br>
                <? if ($admin_admission_data["admission_type"] == 0 ) : ?>
                    <input type="radio" name="read_level" value="2" <?= $admin_admission_data["read_level"] == 2 ? "checked" : ""?>> <?=_("nur mit Passwort")?><br>
                <? else: ?>
                    <input type="radio" name="read_level" disabled> <span class="quiet"><?=_("Nur mit Passwort") ?></span><br>
                <? endif ?>
                </font>
        <? else: ?>
          <font size=-1>
          <b>
            <? if($admin_admission_data["read_level"] == 0) : ?>
              <?= _("freier Zugriff") ?><br>
            <? elseif ($admin_admission_data["read_level"] == 1) : ?>
              <?= _("in Stud.IP angemeldet") ?><br>
            <? elseif ($admin_admission_data["read_level"] == 2) : ?>
              <?=_("nur mit Passwort")?><br>
            <? endif; ?>
          </b>
          </font><br>
                <?= $lock_text ?>
        <? endif; ?>
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="76%" align="left">
        <font size=-1><u><?=_("Schreibzugriff:")?></u> </font><br>
            <?
            if (!isset($admin_admission_data["write_level"]) || $admin_admission_data["write_level"]==3)
                $admin_admission_data["write_level"] = "1"; //Vorgabe: nur angemeldet
                if (! LockRules::Check($seminar_id, 'Schreibzugriff')) :

                if (get_config('ENABLE_FREE_ACCESS') && $SEM_CLASS[$SEM_TYPE[$admin_admission_data["status"]]["class"]]["write_access_nobody"]) {
                ?>
                <input type="radio" name="write_level" value="0" <?= $admin_admission_data["write_level"] == 0 ? "checked" : ""?>> <?=_("freier Zugriff")?><br>
                <?
          } else { ?>
                <input type="radio" name="write_level" disabled> <span class="quiet"><?=_("freier Zugriff")?></span><br>
                <?
                }
                ?>
                <input type="radio" name="write_level" value="1" <?= $admin_admission_data["write_level"] == 1 ? "checked" : ""?>> <?=_("in Stud.IP angemeldet")?><br>
                <? if($admin_admission_data["admission_type"] == 0) : ?>
                    <input type="radio" name="write_level" value="2" <?= $admin_admission_data["write_level"] == 2 ? "checked" : ""?>> <?=_("nur mit Passwort")?><br>
               <? else : ?>
                    <input type="radio" name="write_level" disabled> <span class="quiet"><?=_("Nur mit Passwort") ?></span><br>
               <? endif ?>
        <? else : ?>
          <font size=-1>
            <b>
              <? if($admin_admission_data["write_level"] == 0 ) : ?>
                <?=_("freier Zugriff")?><br>
              <? elseif($admin_admission_data["write_level"] == 1 ) : ?>
                <?=_("in Stud.IP angemeldet")?><br>
              <? elseif ($admin_admission_data["write_level"] == 2 ) : ?>
                <?=_("nur mit Passwort")?><br>
              <? endif; ?>
            </b>
          </font><br>
          <?= $lock_text; ?>
        <? endif; ?>
            </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
                    
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan=2>
                    <font size=-1><b><?=_("Passwort:")?> </b></font><br>
          <? if ($admin_admission_data["admission_type"] == 3) :?>
                    <span><?=_("Diese Veranstaltung ist gesperrt. Es kann kein Passwort vergeben werden.") ?></span><br>
          <? elseif (! LockRules::Check($seminar_id, 'Passwort')) : ?>
                    <font size=-1><?=_("Bitte geben Sie hier ein Passwort ein, wenn Sie <b>Zugriff nur mit Passwort</b> gew&auml;hlt haben.")?></font><br><br>
                    <?
                    if ($admin_admission_data["passwort"]!="") {
                        echo "<font size=-1><input type=\"password\" ";
                        echo "name=\"sem_passwd\"  onchange=\"checkpassword()\" size=12 maxlength=31 value=\"*******\">&nbsp; "._("Passwort-Wiederholung:")."&nbsp; <input type=\"password\" ";
                        echo "name=\"sem_passwd2\" onchange=\"checkpassword2()\" size=12 maxlength=31 value=\"*******\"></font>";
                    }
                    else {
                        echo "<font size=-1><input type=\"password\" name=\"sem_passwd\" ";
                        echo "onchange=\"checkpassword()\" size=12 maxlength=31> &nbsp; "._("Passwort-Wiederholung:")."&nbsp; <input type=\"password\" name=\"sem_passwd2\" ";
                        echo "onchange=\"checkpassword2()\" size=12 maxlength=31></font>";
            } ?>
          <? else: ?>
            <? if ($admin_admission_data["passwort"]!="") : ?>
              <font size=-1>
                <b>
                  ********
                </b>
                </font>
            <? else: ?>
              <font size=-1>
                <?=_("Kein Passwort gesetzt")?>
              </font>
            <? endif; ?>
            <br>
            <?= $lock_text; ?>
          <? endif; ?>
                </td>
            </tr>
        <?
        } else {
        ?>
            <tr <? $cssSw->switchClass() ?>>
                <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
                    
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan=2>
                    <font size=-1><b><?=_("maximale Teilnehmeranzahl:")?> </b></font><br>
                    <font size=-1><?=_("Diese Teilnehmeranzahl dient als Grundlage zur Berechnung der Pl&auml;tze pro Kontingent.")?></font><br><br>
                    <? if(! LockRules::Check($seminar_id, 'admission_binding')) : ?>
                        <font size=-1><input type="text" name="admission_turnout" size=2 maxlength=5 value="<? echo $admin_admission_data["admission_turnout"]; ?>"> <?=_("Teilnehmende")?></font>
                    <? else : ?>
                        <font size=-1><input disabled readonly type="text" name="admission_turnout" size=2 maxlength=5 value="<? echo $admin_admission_data["admission_turnout"]; ?>"> <?=_("Teilnehmende")?></font>
                    <?endif; ?>
                    </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
                    
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan=2>
                    <font size=-1><b><?=_("zugelassenene Studieng&auml;nge:")?> </b></font><br>
                    <table border=0 cellpadding=2 cellspacing=0>
                        <tr>
                            <td class="<? echo $cssSw->getClass() ?>" colspan=3 >
                                <font size=-1><?=_("Bitte geben Sie hier ein, welche Studieng&auml;nge im Anmeldeverfahren zugelassen sind.")?></font>
                            </td>
                        </tr>
                        <tr>
                        <?if (!LockRules::Check($seminar_id, 'admission_studiengang') && !$admin_admission_data['admission_selection_take_place'] && (!in_array($admin_admission_data["admission_type_org"], array(1,2)) || $perm->have_perm("admin"))){
                            ?><td class="<? echo $cssSw->getClass() ?>" colspan="2" >
                            <input style="vertical-align:middle;" type="checkbox" name="admission_enable_quota" <?=($admin_admission_data["admission_enable_quota"] ? 'checked' : '')?> value="1">
                                <font size=-1><?=_("Prozentuale Kontingentierung aktivieren.")?></font>
                            </td>
                            <td>
                              <?= Button::createAccept(_("OK"), "toggle_admission_quota", array("title" => "Kontingentierung aktivieren/deaktivieren")) ?>
                            </td>
                        <?} else {?>
                            <td class="<? echo $cssSw->getClass() ?>" colspan="3">
                            <font size="-1">
                            <?=($admin_admission_data["admission_enable_quota"] ? _("Prozentuale Kontingentierung ist aktiviert.") : _("Prozentuale Kontingentierung ist nicht aktiviert."))?>
                            </font>
                            </td>
                        <?}?>
                        </tr>
                        <tr>
                            <td class="<? echo $cssSw->getClass() ?>" colspan="4">&nbsp;
                            </td>
                        </tr>
                        <tr>
                        <td width="30%"><font size=-1><b><?=_("Studiengang")?>:</b></font></td>
                        <?
                            if ($admin_admission_data["admission_enable_quota"] == 1) {
                            ?>
                            <td colspan="2"><font size=-1><b><?=_("Kontingent")?>:</b></font><br></td>
                            <?
                            }
                            ?>
                        </tr>

                            <?
                            if ($admin_admission_data["studg"]) {
                                foreach ($admin_admission_data["studg"] as $key=>$val) {
                            ?>
                            <tr>
                                <td class="<? echo $cssSw->getClass() ?>" nowrap>
                                <font size=-1>
                                <?
                                echo (htmlReady($val["name"]));
                                ?>
                                </font>
                                </td>
                                <td class="<? echo $cssSw->getClass() ?>" nowrap colspan=2 >
                                <input type="hidden" name="studg_id[]" value="<? echo $key ?>">
                                <input type="hidden" name="studg_name[]" value="<? echo $val["name"] ?>">
                                <?
                                if($admin_admission_data["admission_enable_quota"]){
                                    if (LockRules::Check($seminar_id, 'admission_studiengang') || $val['count'] > 0 || (in_array($admin_admission_data["admission_type_org"], array(1,2)) && !$perm->have_perm("admin"))) {
                                        printf ("&nbsp; &nbsp; <font size=-1>%s %% (%s Teilnehmer)</font>", $val["ratio"], $num_stg[$key]);
                                        if ($val['count'] > 0) {
                                            echo ' <font size=-1>'.sprintf(_("(%s Einträge vorhanden)"), $val['count']) . '</font>';
                                        }
                                    } else {
                                        printf ("<input type=\"HIDDEN\" name=\"studg_ratio_old[]\" value=\"%s\">", $val["ratio"]);
                                        printf ("<input type=\"TEXT\" name=\"studg_ratio[]\" size=5 maxlength=5 value=\"%s\"><font size=-1> %% (%s Teilnehmer)</font>", $val["ratio"], $num_stg[$key]);
                                        echo Button::createCancel(_("Löschen"), "delete_studg[$key]", array("title" => _("Den Studiengang aus der Liste löschen")));
                                    }
                                } elseif (!LockRules::Check($seminar_id, 'admission_studiengang') && (!(in_array($admin_admission_data["admission_type_org"], array(1,2)) && !$perm->have_perm("admin")))) {
                                    if ($val['count'] == 0) {
                                        echo '<input type="image" name="delete_studg['. $key .']" src="'. Assets::image_path('icons/16/blue/trash.png') .'" '. tooltip(_("Den Studiengang aus der Liste löschen")) .'>';
                                    } else {
                                        echo '<font size=-1>'.sprintf(_("(%s Einträge vorhanden)"), $val['count']) . '</font>';
                                    }
                                }
                                ?>
                                </td>
                            </tr>
                            <?
                                }
                            }
                            $query = "SELECT *
                                      FROM studiengaenge
                                      WHERE studiengang_id NOT IN (?)
                                      ORDER BY name";
                            $statement = DBManager::get()->prepare($query);
                            $statement->execute(array(
                                array_keys($admin_admission_data['studg']) ?: ''
                            ));
                            $stg = $statement->fetchAll(PDO::FETCH_ASSOC);

                            if(!isset($admin_admission_data["studg"]['all'])){
                                array_unshift($stg, array(
                                    'name'           => _('Alle Studiengänge'),
                                    'studiengang_id' => 'all',
                                ));
                            }
                            if (count($stg) && !LockRules::Check($seminar_id, 'admission_studiengang') && (!in_array($admin_admission_data["admission_type_org"], array(1,2)) || $perm->have_perm("admin"))) {
                                ?>
                            <tr>
                                <td class="<? echo $cssSw->getClass() ?>" >
                                <font size=-1>
                                <select name="add_studg">
                                <option value="">-- <?=_("bitte ausw&auml;hlen")?> --</option>
                                <?
                                foreach($stg as $s) {
                                    printf ("<option value=%s>%s</option>", $s["studiengang_id"], htmlReady(my_substr($s["name"], 0, 100)));
                                }
                                ?>
                                </select>
                                </font>
                                </td>
                                <td class="<? echo $cssSw->getClass() ?>" nowrap >
                                <?if($admin_admission_data["admission_enable_quota"]){
                                    ?><input type="text" name="add_ratio" size=5 maxlength=5><font size=-1> %</font><?
                                } else {
                                    echo '&nbsp;';
                                }?>
                                </td>
                                <td class="<? echo $cssSw->getClass() ?>">
                                  <?= Button::create("Hinzufügen", array("title" => _("Ausgewählten Studiengang hinzufügen"))) ?>
                                </td>

                            </tr>
                                <?
                                }
                            ?>
                    </table>
                </td>
            </tr>
            <?if($admin_admission_data["admission_enable_quota"] || $admin_admission_data["admission_type"] == 1){
                ?>
                <tr  <? $cssSw->switchClass() ?>>
                    <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
                        
                    </td>
                    <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan=2>
                        <font size=-1><b><? if ($admin_admission_data["admission_type"] == 1) echo _("Losdatum"); else echo _("Enddatum der Kontingentierung");?>:</b></font><br>
                        <?
                        if ($admin_admission_data["admission_type"] == 1 && !LockRules::Check($seminar_id, 'admission_endtime')) {
                            ?>
                            <font size=-1><?=_("Bitte geben Sie hier ein, wann die Wartenden auf der Anmeldeliste in die Veranstaltung gelost werden sollen.")?></font><br><br>
                            <?
                        } else {
                            ?>
                            <font size=-1><?=_("Bitte geben Sie hier ein, wann das Anmeldeverfahren die prozentuale Kontingentierung aufheben soll.")?> </font><br><br>
                            <?
                        }
                        ?>
                        <? if (LockRules::Check($seminar_id, 'admission_endtime') || $admin_admission_data['admission_selection_take_place'] || (in_array($admin_admission_data["admission_type_org"], array(1,2)) && !$perm->have_perm("admin"))) {
                            printf ("<font size=-1>%s um %s Uhr </font>", date("d.m.Y",$admin_admission_data["admission_endtime"]), date("H:i",$admin_admission_data["admission_endtime"]));
                            if($admin_admission_data['admission_selection_take_place']) {
                                echo '<br>';
                                echo $admin_admission_data["admission_type"] == 1 ? _("Das Losverfahren wurde bereits durchgeführt.") : _("Die Kontingentierung wurde bereits aufgehoben.");
                            }
                        } else { ?>
                            <font size=-1><input type="text" name="adm_tag" size=2 maxlength=2 value="<? if ($admin_admission_data["admission_endtime"]<>-1) echo date("d",$admin_admission_data["admission_endtime"]); else echo _("tt") ?>">.
                            <input type="text" name="adm_monat" size=2 maxlength=2 value="<? if ($admin_admission_data["admission_endtime"]<>-1) echo date("m",$admin_admission_data["admission_endtime"]); else echo"mm" ?>">.
                            <input type="text" name="adm_jahr" size=4 maxlength=4 value="<? if ($admin_admission_data["admission_endtime"]<>-1) echo date("Y",$admin_admission_data["admission_endtime"]); else echo _("jjjj") ?>"><?=_("um")?>&nbsp;
                            <input type="text" name="adm_stunde" size=2 maxlength=2 value="<? if ($admin_admission_data["admission_endtime"]<>-1) echo date("H",$admin_admission_data["admission_endtime"]); else echo"23" ?>">:
                            <input type="text" name="adm_minute" size=2 maxlength=2 value="<? if ($admin_admission_data["admission_endtime"]<>-1) echo date("i",$admin_admission_data["admission_endtime"]); else echo"59" ?>">&nbsp;<?=_("Uhr")?></font>&nbsp;
                            <?=Termin_Eingabe_javascript(22,0,($admin_admission_data["admission_endtime"] != -1 ? $admin_admission_data["admission_endtime"] : 0));?>
                        <? } ?>
                        </td>
                </tr>
            <?}?>
            <?if (get_config('ADMISSION_ALLOW_DISABLE_WAITLIST')) {?>
            <tr <? $cssSw->switchClass() ?>>
                <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
                    
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan=2>
                    <font size=-1><b><?=_("Warteliste:")?> </b></font><br>

                    <? if (!LockRules::Check($seminar_id, 'admission_disable_waitlist')) : ?>
                        <font size=-1><?=_("Bitte aktivieren Sie diese Einstellung, wenn eine Warteliste erstellt werden soll, falls die Anmeldungen die maximale Teilnehmeranzahl überschreiten:")?></font><br>
                        <? if ($num_waitlist && !$admin_admission_data["admission_disable_waitlist"]){
                            ?>
                            <font size=-1 color="red"><b><?=_("Achtung:")?></b></font>&nbsp;
                            <font size=-1>
                            <?=sprintf(_("Es existiert eine Warteliste mit %s Einträgen. Wenn Sie die Warteliste ausschalten, werden diese Einträge gelöscht."), $num_waitlist)?>
                            </font><br><br>
                        <?}?>
                        <input type="hidden" name="admission_waitlist" value="0">
                        <font size=-1><input type="CHECKBOX" name="admission_waitlist" value="1" <? if (!$admin_admission_data["admission_disable_waitlist"]) echo "checked"; ?>><?=_("Warteliste aktivieren")?></font>
                    <? else : ?>
                        <? if (!$admin_admission_data["admission_disable_waitlist"]){
                            ?>
                            <font size=-1>
                                <? if ($num_waitlist > 0) : ?>
                                    <?=sprintf(_("Warteliste aktiv, %s Einträge."), $num_waitlist)?>
                                <? else : ?>
                                    <?= _("Warteliste aktiv.")?>
                                <? endif; ?>
                            </font><br><br>
                        <?} else { ?>
                            <font size=-1>
                                <?= sprintf(_("Warteliste deaktiviert."))?>
                            </font><br><br>
                        <?} ?>
                    <? endif; ?>
                </td>
            </tr>
            <?}?>
            <tr <? $cssSw->switchClass() ?>>
                <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
                    
                </td>
                <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan=2>
                    <font size=-1><b><?=_("verbindliche Anmeldung:")?> </b></font><br>
          <? if(! LockRules::Check($seminar_id, 'admission_binding')) : ?>
                    <font size=-1><?=_("Bitte aktivieren Sie diese Einstellung, wenn die Anmeldung f&uuml;r Veranstaltungen verbindlich erfolgen soll:")?></font><br>
                    <font size=-1 color="red"><b><?=_("Achtung:")?></b></font>&nbsp;<font size=-1><?=_("Verwenden Sie diese Option nur bei entsprechendem Bedarf, etwa nach erfolgter Teilnehmerauswahl durch Losen!")?></font><br><br>
                    <font size=-1><input type="checkbox" name="admission_binding" <? if ($admin_admission_data["admission_binding"]) echo "checked"; ?>><?=_("Anmeldung ist <u>verbindlich</u>. (Teilnehmer k&ouml;nnen sich nicht austragen.)")?></font>
          <? else :?>
            <font size=-1>
              <? if ($admin_admission_data["admission_binding"]) ?>
              <?=_("Anmeldung ist <u>verbindlich</u>. (Teilnehmer k&ouml;nnen sich nicht austragen.)")?></font>
          <? endif; ?>
                </td>
            </tr>
        <?
        }
        ?>
        <!-- Hier Änderungen zur Nutzerdomäne -->
        <? if (count(($all_domains = UserDomain::getUserDomains()))): ?>
        <tr <? $cssSw->switchClass() ?>>
            <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
                
            </td>
            <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan="2">
                <font size=-1><b><?=_("Zugelassenene Nutzerdomänen:")?> </b></font><br>
                <table border="0" cellpadding="2" cellspacing="0">
                    <tr>
                        <td class="<? echo $cssSw->getClass() ?>" colspan="3">
                            <font size=-1>
                            <?
                            if (!LockRules::check($seminar_id, 'user_domain')) echo _("Bitte geben Sie hier ein, welche Nutzerdomänen zugelassen sind.");
                            ?>
                            </font>
                        </td>
                    </tr>
                        <?
                        $seminar_domains = UserDomain::getUserDomainsForSeminar($seminar_id);

                        foreach ($seminar_domains as $domain) { ?>

                                <tr>
                                    <td class="<? echo $cssSw->getClass() ?>" >
                                    <font size=-1>
                                    <?= htmlReady($domain->getName()) ?>
                                    </font>
                                    </td>
                                    <td class="<?= $cssSw->getClass() ?>" nowrap colspan=2 >
                                    <?if (!LockRules::check($seminar_id, 'user_domain')){?>
                                        <input type="image" name="delete_domain[<?= $domain->getID() ?>]" src="<?= Assets::image_path('icons/16/blue/trash.png') ?>" <?= tooltip(_("Nutzerdomäne aus der Liste löschen")) ?>>
                                    <?}?>
                                    </td>
                                </tr>
                        <?
                        }

                        // get all user domains that can be added
                        $domains = array_diff($all_domains, $seminar_domains);
                        if (!LockRules::check($seminar_id, 'user_domain') && count($domains)) {
                            ?>
                        <tr>
                            <td class="<? echo $cssSw->getClass() ?>" >
                            <font size=-1>
                            <select name="add_domain">
                            <option value="">-- <?=_("bitte auswählen")?> --</option>
                            <?

                            foreach ($domains as $domain) {
                                printf ("<option value=\"%s\">%s</option>", $domain->getID(), htmlReady(my_substr($domain->getName(), 0, 40)));
                            }
                            ?>
                            </select>
                            </font>
                            </td>

                            <td class="<? echo $cssSw->getClass() ?>">
                              <?= Button::create(_("Hinzufügen")) ?>
                            </td>

                        </tr>
                            <?
                            }
                        ?>
                </table>
            </td>
        </tr>
        <? endif; ?>
        <!-- Hier gehts normal weiter -->
        <tr>
            <td class="steel2" align="center" colspan="3">
                <?= Button::createAccept(_("Übernehmen"), "uebernehmen") ?>
            </td>
        </tr>
    </table>
    </form>
        </td>
    </tr>
</table>

<?php
    include ('lib/include/html_end.inc.php');
    page_close();
?>
