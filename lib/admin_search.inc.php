<?
# Lifter001: TODO - in progress (session variables)
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/*
admin_search_form.inc.php - Suche fuer die Verwaltungsseiten von Stud.IP.
Copyright (C) 2001 Stefan Suchi <suchi@gmx.de>, Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <ckater@gwdg.de

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

# necessary if you want to include admin_search_form.inc.php in function/method scope
global  $auth, $perm, $sess, $user;

global  $admin_dates_data,
        $archiv_assi_data,
        $archive_kill,
        $i_page,
        $i_view,
        $links_admin_data,
        $list,
        $new_inst,
        $new_sem,
        $sem_create_data,
        $SessSemName,
        $view_mode;

if ($perm->have_perm("tutor")) {    // Navigationsleiste ab status "Tutor"

    require_once 'config.inc.php';
    require_once 'lib/dates.inc.php';
    require_once 'lib/functions.php';

    $sess->register("links_admin_data");
    $sess->register("sem_create_data");
    $sess->register("admin_dates_data");

    $userConfig = UserConfig::get($GLOBALS['user']->id); // tic #650

    /**
    * We use this helper-function, to reset all the data in the adminarea
    *
    * There are much pages with an own temporary set of data. Please use
    * only this function to add defaults or clear data.
    */
    function reset_all_data($reset_search_fields = false)
    {
        global $links_admin_data, $sem_create_data, $admin_dates_data, $admin_admission_data,
        $archiv_assi_data, $term_metadata;

        if($reset_search_fields) $_SESSION['links_admin_data']='';
        $_SESSION['sem_create_data']='';
        $admin_dates_data='';
        $admin_admission_data='';
        $admin_rooms_data='';
        $archiv_assi_data='';
        $term_metadata='';
        $_SESSION['links_admin_data']["select_old"]=TRUE;
        $_SESSION['links_admin_data']['srch_sem'] =& $_SESSION['_default_sem'];
    }


    //a Veranstaltung was selected in the admin-search kann viellecht weg
    if (isset($_REQUEST['select_sem_id'])) {
        reset_all_data();
        closeObject();
        openSem($_REQUEST['select_sem_id']);
    //a Veranstaltung which was already open should be administrated
    } elseif (($SessSemName[1]) && ($new_sem)) {
        reset_all_data();
        $_SESSION['links_admin_data']["referred_from"]="sem";
    }

    //a Einrichtung was selected in the admin-search
    if ($_REQUEST['admin_inst_id'] && $_REQUEST['admin_inst_id'] != "NULL") {
        reset_all_data();
        closeObject();
        openInst($_REQUEST['admin_inst_id']);
    //a Einrichtung which was already open should be administrated
    } elseif (($SessSemName[1]) && ($new_inst)) {
        reset_all_data();
        $_SESSION['links_admin_data']["referred_from"]="inst";
    }

    //Veranstaltung was selected but it is on his way to hell.... we close it at this point
    if (($archive_kill) && ($SessSemName[1] == $archiv_assi_data["sems"][$archiv_assi_data["pos"]]["id"])) {
        //reset_all_data();
        closeObject();
    }

    $list = $_REQUEST['list'];

    //a new session in the adminarea...
    if (($i_page == "adminarea_start.php" && $list) || $_REQUEST['quit']) {
        reset_all_data();
        closeObject();
    } elseif ($i_page== "adminarea_start.php")
        $list=TRUE;

    // start tic #650, sortierung in der userconfig merken
    if ($_REQUEST['adminarea_sortby']) {
        $_SESSION['links_admin_data']["sortby"] = Request::option('adminarea_sortby');
        $list=TRUE;
    }
    if (!isset($_SESSION['links_admin_data']["sortby"])) {
        $_SESSION['links_admin_data']["sortby"]=$userConfig->getValue('LINKS_ADMIN');

        if ($_SESSION['links_admin_data']["sortby"]=="" || $_SESSION['links_admin_data']["sortby"]==false) {
            $_SESSION['links_admin_data']["sortby"]="VeranstaltungsNummer";
        }
    } else {
        $userConfig->store('LINKS_ADMIN', $_SESSION['links_admin_data']["sortby"]);
    }

    if (!Request::submitted('srch_send')) {
        $show_rooms_check = $userConfig->getValue('LINKS_ADMIN_SHOW_ROOMS');
    } else {
        $show_rooms_check = Request::option('show_rooms_check', 'off');
        $userConfig->store('LINKS_ADMIN_SHOW_ROOMS', $show_rooms_check);
    }
    // end tic #650

    if ($_REQUEST['view'])
        $_SESSION['links_admin_data']["view"] = Request::option('view');

    if ($_REQUEST['srch_send']) {
        $_SESSION['links_admin_data']["srch_sem"] = Request::option('srch_sem');
        $_SESSION['links_admin_data']["srch_doz"] = Request::option('srch_doz');
        $_SESSION['links_admin_data']["srch_inst"]= Request::option('srch_inst');
        $_SESSION['links_admin_data']["srch_fak"] = Request::option('srch_fak');
        $_SESSION['links_admin_data']["srch_exp"] = Request::get('srch_exp');
        $_SESSION['links_admin_data']["select_old"] = Request::int('select_old');
        $_SESSION['links_admin_data']["select_inactive"] = Request::int('select_inactive');
        $_SESSION['links_admin_data']["srch_on"] = true;
        $list = true;
    }

    if(Request::submitted('links_admin_reset_search')){
        reset_all_data(true);
        $view_mode = 'sem';
        $list = true;
    }

    //if the user selected the information field at Einrichtung-selection....
    if ($_REQUEST['admin_inst_id'] == "NULL")
        $list=TRUE;

    //user wants to create a new Einrichtung
    if ($i_view=="new")
        $_SESSION['links_admin_data']='';

    //here are all the pages/views listed, which require the search form for Einrichtungen
    if ($i_page == "admin_institut.php"
            OR ($i_page == "admin_roles.php" AND $_SESSION['links_admin_data']["view"] == "statusgruppe_inst")
            OR ($i_page == "admin_lit_list.php" AND $_SESSION['links_admin_data']["view"] == "literatur_inst")
            OR $i_page == "inst_admin.php"
            OR ($i_page == "admin_news.php" AND $_SESSION['links_admin_data']["view"] == "news_inst")
            OR ($i_page == "admin_modules.php" AND $_SESSION['links_admin_data']["view"] == "modules_inst")
            OR ($i_page == "admin_extern.php" AND $_SESSION['links_admin_data']["view"] == "extern_inst")
            OR ($i_page == "admin_vote.php" AND $_SESSION['links_admin_data']["view"] == "vote_inst")
            OR ($i_page == "admin_evaluation.php" AND $_SESSION['links_admin_data']["view"] == "eval_inst")
            ) {

        $_SESSION['links_admin_data']["topkat"]="inst";
    }

    //here are all the pages/views listed, which require the search form for Veranstaltungen
    if ($i_page == "themen.php"
            OR $i_page == "raumzeit.php"
            OR $i_page == "admin_admission.php"
            OR ($i_page == "admin_statusgruppe.php" AND $_SESSION['links_admin_data']["view"]=="statusgruppe_sem")
            OR ($i_page == "admin_lit_list.php" AND $_SESSION['links_admin_data']["view"]=="literatur_sem")
            OR $i_page == "archiv_assi.php"
            OR $i_page == "admin_visibility.php"
            OR $i_page == "admin_aux.php"
            OR $i_page == "admin_lock.php"
            OR $i_page == "copy_assi.php"
            OR $i_page == "adminarea_start.php"
            OR ($i_page == "admin_modules.php" AND $_SESSION['links_admin_data']["view"] == "modules_sem")
            OR ($i_page == "admin_news.php" AND $_SESSION['links_admin_data']["view"]=="news_sem")
            OR ($i_page == "admin_vote.php" AND $_SESSION['links_admin_data']["view"]=="vote_sem")
            OR ($i_page == "admin_evaluation.php" AND $_SESSION['links_admin_data']["view"]=="eval_sem")
            ) {

        $_SESSION['links_admin_data']["topkat"]="sem";
    }

    //here are all the pages/views listed, which require the search form for Veranstaltungen
    if ($i_page == "admin_extern.php" AND $_SESSION['links_admin_data']["view"] == 'extern_global') {

        $_SESSION['links_admin_data']["topkat"] = 'global';
    }

    //remember the open topkat
    if ($view_mode=="sem")
        $_SESSION['links_admin_data']["topkat"]="sem";
    elseif ($view_mode=="inst")
        $_SESSION['links_admin_data']["topkat"]="inst";
    if (!$_SESSION['links_admin_data']["topkat"])
        $_SESSION['links_admin_data']["topkat"]="global";
    if ($view_mode != 'user')
        $view_mode = $_SESSION['links_admin_data']["topkat"];

    //Wenn nur ein Institut verwaltet werden kann, immer dieses waehlen (Auswahl unterdruecken)
    if ((!$SessSemName[1]) && ($list) && ($view_mode=="inst")) {
        if (!$perm->have_perm("root") && !$perm->is_fak_admin($user->id)) {
            $query = "SELECT Institute.Institut_id "
                   . "FROM Institute "
                   . "LEFT JOIN user_inst USING(Institut_id) "
                   . "WHERE user_id = ? "
                   . "  AND inst_perms IN ('admin', 'dozent', 'tutor') "
                   . "ORDER BY Name";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id));

            if ($institute_id = $statement->fetchColumn()) {
                reset_all_data();
                openInst($institute_id);
            }
        }
    }
}
