<?php
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
/**
 * meine_seminare.php - Anzeige der eigenen Seminare (anhaengig vom Status)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Ralf Stockmann <rstockm@gwdg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require '../lib/bootstrap.php';

unregister_globals();
require_once 'lib/classes/CourseAvatar.class.php';
require_once 'lib/classes/StudygroupAvatar.class.php';
require_once 'lib/classes/InstituteAvatar.class.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");

global $SEM_CLASS,
       $SEM_TYPE;

ob_start(); //Outputbuffering für maximal Performance

/**
 *
 * @param unknown_type $semid
 * @param unknown_type $my_obj_values
 * @param unknown_type $type
 */
function print_seminar_content($semid, $my_obj_values, $type = 'seminar', $sem_class = null)
{
    $slot_mapper = array(
        'files' => "documents",
        'elearning' => "elearning_interface"
    );
    $plugin_navigation = getPluginNavigationForSeminar($semid, $my_obj_values['visitdate']);
    foreach (words('forum participants files news scm schedule wiki vote literature elearning') as $key) {
        if ($sem_class) {
            $slot = isset($slot_mapper[$key]) ? $slot_mapper[$key] : $key;
            $module = $sem_class->getModule($slot);
            if (is_a($module, "StandardPlugin")) {
                $navigation[$key] = $plugin_navigation[get_class($module)];
                unset($plugin_navigation[get_class($module)]);
            } else {
                $navigation[$key] = $my_obj_values[$key];
            }
        } else {
            $navigation[$key] = $my_obj_values[$key];
        }
    }

    $navigation = array_merge($navigation, $plugin_navigation);

    foreach ($navigation as $key => $nav) {
        if (isset($nav) && $nav->isVisible(true)) {
            // need to use strtr() here to deal with seminar_main craziness
            $url = $type.'_main.php?auswahl='.$semid.'&redirect_to='.strtr($nav->getURL(), '?', '&');
            printf('<a %s href="%s"><img ',
                   $nav->hasBadgeNumber()
                   ? 'class="badge" data-badge-number="' . intval($nav->getBadgeNumber())  . '"'
                   : '',
                   htmlspecialchars($url));
            foreach ($nav->getImage() as $key => $value) {
                printf('%s="%s" ', $key, htmlReady($value));
            }
            echo '></a>';
        } else if (is_string($key)) {
            echo Assets::img('blank.gif', array('width' => 16, 'height' => 16));
        }
        echo ' ';
    }
}


include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once ('lib/visual.inc.php');            // htmlReady fuer die Veranstaltungsnamen
require_once ('lib/dates.inc.php');         // Semester-Namen fuer Admins
require_once ('lib/admission.inc.php');     // Funktionen der Teilnehmerbegrenzung
require_once ('lib/messaging.inc.php');
require_once ('lib/classes/Modules.class.php'); // modul-config class
require_once ('lib/classes/ModulesNotification.class.php');
require_once ('lib/statusgruppe.inc.php');      // Funktionen für Statusgruppen
require_once ('lib/object.inc.php');
require_once ('lib/meine_seminare_func.inc.php');
require_once ('lib/classes/LockRules.class.php');

if (get_config('CHAT_ENABLE')){
    include_once $RELATIVE_PATH_CHAT."/chat_func_inc.php";
    $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
    $chatServer->caching = true;
    $sms = new messaging();
}

$deputies_enabled = get_config('DEPUTIES_ENABLE');
$default_deputies_enabled = get_config('DEPUTIES_DEFAULTENTRY_ENABLE');
$Modules = new Modules();
$userConfig = UserConfig::get($GLOBALS['user']->id);

// we are defintely not in an lexture or institute
closeObject();
$_SESSION['links_admin_data']='';    //Auch im Adminbereich gesetzte Veranstaltungen muessen geloescht werden.
//delete all temporary permission changes
if (is_array($_SESSION)) {
    foreach (array_keys($_SESSION) as $key) {
        if (strpos($key, 'seminar_change_view_') !== false) {
            unset($_SESSION[$key]);
        }
    }
}
PageLayout::setHelpKeyword("Basis.MeineVeranstaltungen");
PageLayout::setTitle(_("Meine Veranstaltungen und Einrichtungen"));
if (!$perm->have_perm("root")) {
    Navigation::activateItem('/browse/my_courses/list');
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

if (get_config('CHAT_ENABLE')){
    chat_get_javascript();
}

$cmd = Request::option('cmd');
if(in_array($cmd, words('no_kill suppose_to_kill suppose_to_kill_admission kill kill_admission'))){
    $current_seminar = Seminar::getInstance(Request::option('auswahl'));
    $ticket_check = Seminar_Session::check_ticket(Request::option('studipticket'));
    UrlHelper::addLinkParam('studipticket', Seminar_Session::get_ticket());

    //Ausgabe bei bindenden Veranstaltungen, loeschen nicht moeglich!
    if ($cmd == "no_kill") {
        $meldung = "info§" . sprintf(_("Die Veranstaltung <b>%s</b> ist als <b>bindend</b> angelegt. Wenn Sie sich austragen wollen, m&uuml;ssen Sie sich an die Dozentin oder den Dozenten der Veranstaltung wenden."), htmlReady($current_seminar->getName())) . "<br>";
    }

    //Sicherheitsabfrage fuer abonnierte Veranstaltungen
    if ($cmd == "suppose_to_kill") {
        if(LockRules::Check($current_seminar->getId(), 'participants')){
            $lockdata = LockRules::getObjectRule($current_seminar->getId());
            $meldung = "error§" . sprintf(_("Sie können das Abonnement der Veranstaltung <b>%s</b> nicht aufheben."), htmlReady($current_seminar->getName()));
            if($lockdata['description']) $meldung .= '§info§' . formatLinks($lockdata['description']);
        } else {
            if ($current_seminar->admission_type || $current_seminar->admission_prelim == 1) {
                $meldung = sprintf(_('Wollen Sie das Abonnement der teilnahmebeschränkten Veranstaltung "%s" wirklich aufheben? Sie verlieren damit die Berechtigung für die Veranstaltung und müssen sich ggf. neu anmelden!'), $current_seminar->getName());
            } else if ($current_seminar->admission_endtime_sem != -1 && $current_seminar->admission_endtime_sem < time()) {
                $meldung = sprintf(_('Wollen Sie das Abonnement der Veranstaltung "%s" wirklich aufheben? Der Anmeldzeitraum ist abgelaufen und Sie können sich nicht wieder anmelden!'), $current_seminar->getName());
            } else {
                $meldung = sprintf(_('Wollen Sie das Abonnement der Veranstaltung "%s" wirklich aufheben?'), $current_seminar->getName());
            }
            echo createQuestion($meldung, array('cmd' => 'kill', 'auswahl' => $current_seminar->getId()));
        }
    }

    //Sicherheitsabfrage fuer Wartelisteneintraege
    if ($cmd=="suppose_to_kill_admission") {
        if(admission_seminar_user_get_position($user->id, $current_seminar->getId()) == 'na'){
            $meldung = sprintf(_('Wollen Sie den Eintrag auf der Anmeldeliste der Veranstaltung "%s" wirklich aufheben?'), $current_seminar->getName());
        } else {
            $meldung = sprintf(_('Wollen Sie den Eintrag auf der Warteliste der Veranstaltung "%s" wirklich aufheben? Sie verlieren damit die bereits erreichte Position und müssen sich ggf. neu anmelden!'), $current_seminar->getName());
        }
        echo createQuestion($meldung, array('cmd' => 'kill_admission', 'auswahl' => $current_seminar->getId()));
    }

    //bei Bedarf aus seminar_user austragen
    if ($cmd=="kill"
        && !LockRules::Check($current_seminar->getId(), 'participants')
        && $ticket_check) {

        if ($current_seminar->admission_binding) {
            $meldung = "info§" . sprintf(_("Die Veranstaltung <b>%s</b> ist als <b>bindend</b> angelegt. Wenn Sie sich austragen wollen, m&uuml;ssen Sie sich an die Dozentin oder den Dozenten der Veranstaltung wenden."), htmlReady($current_seminar->getName())) . "<br>";
        } elseif (!$perm->have_studip_perm('tutor', $current_seminar->getId())) {

            // LOGGING
            log_event('SEM_USER_DEL', $current_seminar->getId(), $user->id, 'Hat sich selbst ausgetragen');

            $query = "DELETE FROM seminar_user WHERE user_id = ? AND Seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id, $current_seminar->getId()));
            if ($statement->rowCount() == 0)
                $meldung="error§" . _("Datenbankfehler!");
            else {
                // Löschen aus Statusgruppen
                RemovePersonStatusgruppeComplete (get_username(), $current_seminar->getId());

                //Pruefen, ob es Nachruecker gibt
                update_admission($current_seminar->getId());

                $meldung = "msg§" . sprintf(_("Das Abonnement der Veranstaltung <b>%s</b> wurde aufgehoben. Sie sind nun nicht mehr als TeilnehmerIn dieser Veranstaltung im System registriert."), htmlReady($current_seminar->getName()));
            }
        }
    }

    //bei Bedarf aus admission_seminar_user austragen
    if ($cmd=="kill_admission" && $ticket_check) {

        // LOGGING
        log_event('SEM_USER_DEL', $current_seminar->getId(), $user->id, 'Hat sich selbst aus der Wartliste ausgetragen');

        $query = "DELETE FROM admission_seminar_user WHERE user_id = ? AND seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user->id, $current_seminar->getId()));
        if ($statement->rowCount() == 0)  $meldung="error§" . _("Datenbankfehler!");
        else {
            //Warteliste neu sortieren
            renumber_admission($current_seminar->getId());
            //Pruefen, ob es Nachruecker gibt
            update_admission($current_seminar->getId());
            $meldung="msg§" . sprintf(_("Der Eintrag in der Anmelde- bzw. Warteliste der Veranstaltung <b>%s</b> wurde aufgehoben. Wenn Sie an der Veranstaltung teilnehmen wollen, m&uuml;ssen Sie sich erneut bewerben."), htmlReady($current_seminar->getName()));
        }
    }
}
//bei Bedarf aus seminar_user austragen
if ($cmd=="inst_kill" && $GLOBALS['ALLOW_SELFASSIGN_INSTITUTE']) {
    $query = "DELETE FROM user_inst WHERE user_id = ? AND Institut_id = ? AND inst_perms = 'user'";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id, $auswahl));
    if ($statement->rowCount() == 0)
        $meldung="error§" . _("Datenbankfehler!");
    else {

        $query = "SELECT Name FROM Institute WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($auswahl));
        $name = $statement->fetchColumn();

        $meldung="msg§" . sprintf(_("Die Zuordnung zur Einrichtung %s wurde aufgehoben."), "<b>".htmlReady($name)."</b>");
    }
}


// Update der Gruppen
if (Request::int('gruppesent') == '1'){
    $user->cfg->store('MY_COURSES_GROUPING', Request::get('select_group_field'));
    $gruppe = Request::getArray('gruppe');
    if (!empty($gruppe)){
        $query = "UPDATE seminar_user SET gruppe = ? WHERE Seminar_id = ? AND user_id = ?";
        $user_statement = DBManager::get()->prepare($query);

        $query = "UPDATE deputies SET gruppe = ? WHERE range_id = ? AND user_id = ?";
        $deputy_statement = DBManager::get()->prepare($query);

        foreach($gruppe as $key => $value){
            $user_statement->execute(array($value, $key, $user->id));
            $updated = $user_statement->rowCount();

            if ($deputies_enabled && !$updated) {
                $deputy_statement->execute(array($value, $key, $user->id));
            }
        }
    }
}

//Anzeigemodul fuer eigene Seminare (nur wenn man angemeldet und nicht root oder admin ist!)
if ($auth->is_authenticated() && $user->id != "nobody" && !$perm->have_perm("admin")) {

    //Alle fuer das Losen anstehenden Veranstaltungen bearbeiten (wenn keine anstehen wird hier nahezu keine Performance verbraten!)
    check_admission();
    $_my_sem_group_field = $user->cfg->MY_COURSES_GROUPING;
    $_my_sem_open = $user->cfg->MY_COURSES_OPEN_GROUPS;
    /*
     * Get and check the global configuration for forced grouping.
     * If the global configuration specifies an unknown field, don't
     * force grouping.
     */
    $forced_grouping = in_array(get_config('MY_COURSES_FORCE_GROUPING'), getValidGroupingFields()) ?
        get_config('MY_COURSES_FORCE_GROUPING') :
        'not_grouped';
    if (!$_my_sem_group_field) {
        $_my_sem_group_field = 'not_grouped';
        $_my_sem_open['not_grouped'] = true;
    }
    if ($_my_sem_group_field == 'not_grouped' && $forced_grouping != 'not_grouped') {
        $_my_sem_group_field = $forced_grouping;
        if ($forced_grouping == 'sem_number') {
            $_my_sem_open[SemesterData::GetSemesterIndexById(Semester::findCurrent()->semester_id)] = true;
        }
    }
    $group_field = $_my_sem_group_field;

    if (Request::option('open_my_sem')) {
        $_my_sem_open[Request::option('open_my_sem')] = true;
        $user->cfg->store('MY_COURSES_OPEN_GROUPS', $_my_sem_open);
    }
    if (Request::option('close_my_sem')) {
        unset($_my_sem_open[Request::option('close_my_sem')]);
        $user->cfg->store('MY_COURSES_OPEN_GROUPS', $_my_sem_open);
    }

    $groups = array();

    $all_semester = SemesterData::GetSemesterArray();

    $add_fields = '';
    $add_query = '';

    if($group_field == 'sem_tree_id'){
        $add_fields = ',sem_tree_id';
        $add_query = "LEFT JOIN seminar_sem_tree sst ON (sst.seminar_id=seminare.Seminar_id)";
    }

    if($group_field == 'dozent_id'){
        $add_fields = ', su1.user_id as dozent_id';
        $add_query = "LEFT JOIN seminar_user as su1 ON (su1.seminar_id=seminare.Seminar_id AND su1.status='dozent')";
    }

    $dbv = new DbView();

    $query = "SELECT seminare.VeranstaltungsNummer AS sem_nr, seminare.Name, seminare.Seminar_id,
                     seminare.status as sem_status, seminar_user.status, seminar_user.gruppe,
                     seminare.chdate, seminare.visible, admission_binding,modules,
                     IFNULL(visitdate,0) as visitdate, admission_prelim,
                     {$dbv->sem_number_sql} as sem_number,
                     {$dbv->sem_number_end_sql} as sem_number_end
                     {$add_fields}
              FROM seminar_user LEFT JOIN seminare  USING (Seminar_id)
              LEFT JOIN object_user_visits ouv ON (ouv.object_id=seminar_user.Seminar_id AND ouv.user_id= :user_id AND ouv.type='sem')
              {$add_query}
              WHERE seminar_user.user_id = :user_id";
    if ($deputies_enabled) {
        $query .= " UNION ".getMyDeputySeminarsQuery('meine_sem', $dbv->sem_number_sql, $dbv->sem_number_end_sql, $add_fields, $add_query);
    }
    $query .= " ORDER BY sem_nr ASC";
    $statement = DBManager::get()->prepare($query);
    $statement->bindValue(':user_id', $user->id);
    $statement->execute();
    $seminars = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (count($seminars) == 0) {
        $meldung = "info§" . sprintf(_("Sie haben zur Zeit keine Veranstaltungen abonniert, an denen Sie teilnehmen k&ouml;nnen. Bitte nutzen Sie %s<b>Veranstaltung suchen / hinzuf&uuml;gen</b>%s um neue Veranstaltungen aufzunehmen."), "<a href=\"sem_portal.php\">", "</a>") . "§" . $meldung;
    }

    foreach ($seminars as $seminar) {
        $my_obj[$seminar['Seminar_id']] = array(
            'name'           => $seminar['Name'],
            'semname'        => $seminar['Name'],
            'status'         => $seminar['status'],
            'visible'        => $seminar['visible'],
            'gruppe'         => $seminar['gruppe'],
            'chdate'         => $seminar['chdate'],
            'binding'        => $seminar['admission_binding'],
            'modules'        => $Modules->getLocalModules($seminar['Seminar_id'], 'sem', $seminar['modules'], $seminar['sem_status']),
            'obj_type'       => 'sem',
            'sem_status'     => $seminar['sem_status'],
            'prelim'         => $seminar['admission_prelim'],
            'visitdate'      => $seminar['visitdate'],
            'sem_number'     => $seminar['sem_number'],
            'sem_number_end' => $seminar['sem_number_end']
        );
        if ((get_config('CHAT_ENABLE')) && ($my_obj[$seminar['Seminar_id']]['modules']['chat'])) {
            $chatter = $chatServer->isActiveChat($seminar['Seminar_id']);
            $chat_info[$seminar['Seminar_id']] = array(
                'chatter'    => $chatter,
                'chatuniqid' => $chatServer->chatDetail[$seminar['Seminar_id']]['id'],
                'is_active'  => $chatServer->isActiveUser($user->id, $seminar['Seminar_id'])
            );
            if ($chatter){
                $active_chats[$chatServer->chatDetail[$seminar['Seminar_id']]['id']] = $seminar['Seminar_id'];
            }
        }
        if ($group_field){
            fill_groups($groups, $seminar[$group_field], array(
                'seminar_id' => $seminar['Seminar_id'],
                'name'       => $seminar['Name'],
                'gruppe'     => $seminar['gruppe']
            ));
        }
    }

        if (is_array($my_obj)){
            $num_my_sem = count($my_obj);
            if ($group_field == 'sem_number') {
                correct_group_sem_number($groups, $my_obj);
            } else {
                add_sem_name($my_obj);
            }
        }

    $query = "SELECT b.Name, b.Institut_id, b.type, b.Institut_id = b.fakultaets_id AS is_fak,
                     user_inst.inst_perms, modules, IFNULL(visitdate, 0) AS visitdate
              FROM user_inst
              LEFT JOIN Institute AS b USING (Institut_id)
              LEFT JOIN object_user_visits AS ouv
                ON (ouv.object_id = user_inst.Institut_id AND ouv.user_id = :user_id AND ouv.type = 'inst')
              WHERE user_inst.user_id = :user_id
              GROUP BY Institut_id
              ORDER BY Name";
    $statement = DBManager::get()->prepare($query);
    $statement->bindValue(':user_id', $user->id);
    $statement->execute();

    $num_my_inst = 0;
    while ($institute = $statement->fetch(PDO::FETCH_ASSOC)) {
        $my_obj[$institute['Institut_id']]= array(
            'name'      => $institute['Name'],
            'status'    => $institute['inst_perms'],
            'type'      => $institute['type'] ?: 1,
            'modules'   => $Modules->getLocalModules($institute['Institut_id'], 'inst', $institute['modules'], $institute['type'] ?: 1),
            'obj_type'  => 'inst',
            'visitdate' => $institute['visitdate']
        );
        if ((get_config('CHAT_ENABLE')) && ($my_obj[$institute['Institut_id']]['modules']['chat'])) {
            $chatter = $chatServer->isActiveChat($institute['Institut_id']);
            $chat_info[$institute['Institut_id']] = array(
                'chatter'    => $chatter,
                'chatuniqid' => $chatServer->chatDetail[$institute['Institut_id']]["id"],
                'is_active'  => $chatServer->isActiveUser($user->id, $institute['Institut_id'])
            );
            if ($chatter){
                $active_chats[$chatServer->chatDetail[$institute['Institut_id']]['id']] = $institute['Institut_id'];
            }
        }
        $num_my_inst += 1;
    }
    if (($num_my_sem + $num_my_inst) > 0){
        get_my_obj_values($my_obj, $GLOBALS['user']->id);
    }
    if (get_config('CHAT_ENABLE')){
        if (is_array($active_chats)){
            $chat_invs = $sms->check_list_of_chatinv(array_keys($active_chats));
        }
    }

    // "Mark all as read"
    //
    // Nasty place for an action but since we don't have a model, this is the
    // perfect place to grab all object ids
    if (Request::option('action') === 'tabularasa') {
        // load plugins, so they have a chance to register themselves as observers
        PluginEngine::getPlugins('StandardPlugin');

        NotificationCenter::postNotification('OverviewWillClear', $GLOBALS['user']->id);

        $query = "INSERT INTO object_user_visits "
               .   "(object_id, user_id, type, visitdate, last_visitdate) "
               . "("
               .  "SELECT news_id, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() "
               .   "FROM news_range "
               .   "WHERE range_id = ? "
               . ") UNION ("
               .   "SELECT vote_id, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() "
               .   "FROM vote "
               .   "WHERE range_id = ?"
               . ") UNION ("
               .   "SELECT eval_id, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() "
               .   "FROM eval_range "
               .   "WHERE range_id = ?"
               . ") "
               . "ON DUPLICATE KEY UPDATE visitdate = UNIX_TIMESTAMP()";
        $statement = DBManager::get()->prepare($query);

        foreach ($my_obj as $id => $object) {
            // Update all activated modules
            foreach (words('forum documents schedule participants literature wiki scm elearning_interface') as $type) {
                if ($object['modules'][$type]) {
                    object_set_visit($id, $type);
                }
            }

            // Update news and votes
            $statement->execute(array(
                $GLOBALS['auth']->auth['uid'], 'news', $id,
                $GLOBALS['auth']->auth['uid'], 'vote', $id,
                $GLOBALS['auth']->auth['uid'], 'eval', $id,
             ));

            // Update object itself
            object_set_visit($id, $object['obj_type']);
        }

        NotificationCenter::postNotification('OverviewDidClear', $GLOBALS['user']->id);

        // PageLayout::postMessage(Messagebox::success(_('Alle Markierungen wurden entfernt')));
        page_close();

        header('Location: ' . URLHelper::getURL());
        die;
    }

    // Anzeige der Wartelisten

    $stmt = DBManager::get()->prepare(
        "SELECT admission_seminar_user.*, seminare.status as sem_status, ".
        "seminare.Name, seminare.admission_endtime, ".
        "seminare.admission_turnout, quota ".
        "FROM admission_seminar_user ".
        "LEFT JOIN seminare USING(seminar_id) ".
        "LEFT JOIN admission_seminar_studiengang ".
        "ON (admission_seminar_user.studiengang_id = admission_seminar_studiengang.studiengang_id ".
        "AND seminare.seminar_id = admission_seminar_studiengang.seminar_id) ".
        "WHERE user_id = ? ".
        "ORDER BY admission_type, name");
    $stmt->execute(array($user->id));

    $waitlists = $stmt->fetchAll();

    // Berechnung der uebrigen Seminare und Einrichtungen
    // (wird für 5 Minuten im Cache gehalten)

    $cache = StudipCacheFactory::getCache();

    $institute_count = unserialize($cache->read('/meine_seminare/count/institute'));
    if ($institute === false) {
        $query = "SELECT COUNT(*) FROM Institute";
        $institute_count = DBManager::get()->query($query)->fetchColumn();
        $cache->write('/meine_seminare/count/institute', $institute_count, 5 * 60);
    }
    $anzahlinst = $institute_count - $num_my_inst;

    $seminar_count = unserialize($cache->read('/meine_seminare/count/seminare'));
    if ($seminar_count === false) {
        $query = "SELECT COUNT(*) FROM seminare";
        $seminar_count = DBManager::get()->query($query)->fetchColumn();
        $cache->write('/meine_seminare/count/seminare', $seminar_count, 5 * 60);
    }
    $anzahltext = sprintf(_('Es sind noch %s weitere Veranstaltungen sowie %s weitere Einrichtungen vorhanden.'),
                          $seminar_count - $num_my_sem,
                          $anzahlinst);

    // View for Teachers

    if ($perm->have_perm("dozent")) {
        $infobox = array    (
            array  ("kategorie"  => _("Information:"),
                "eintrag" => array  (
                    array ( "icon" => 'icons/16/black/info.png',
                                    "text"  => $anzahltext
                    )
                )
            ),
            array  ("kategorie" => _("Veranstaltungen:"),
                "eintrag" => array  (
                    array    (  "icon" => 'icons/16/black/search.png',
                                        "text"  => sprintf(_("Um weitere Veranstaltungen in Ihre pers&ouml;nliche Auswahl aufzunehmen, nutzen Sie bitte die %sSuchfunktion%s."), "<a href=\"sem_portal.php\">", "</a>")
                    ),
                    array    (  "icon" => 'icons/16/black/add/seminar.png',
                                        "text"  => sprintf(_("Um Veranstaltungen anzulegen, nutzen Sie bitte den %sVeranstaltungs-Assistenten%s."), "<a href=\"admin_seminare_assi.php?new_session=TRUE\">", "</a>")
                    )
                )
            ),
            array  ("kategorie" => _("Einrichtungen:"),
                "eintrag" => array  (
                    array    (  "icon" => 'icons/16/black/institute.png',
                                        "text"  => sprintf(_("Um Einrichtungen zu suchen und sich Informationen anzeigen zu lassen, nutzen Sie die %sEinrichtungssuche%s."), "<a href=\"institut_browse.php\">", "</a>")
                    )
                )
            )
        );
        $sem_create_perm = (in_array(get_config('SEM_CREATE_PERM'), array('root','admin','dozent')) ? get_config('SEM_CREATE_PERM') : 'dozent');
        if ($sem_create_perm != 'dozent') unset($infobox[1]['eintrag'][1]);
    }   else {

    // View for Students

        $infobox = array    (
            array  ("kategorie"  => _("Information:"),
                "eintrag" => array  (
                    array ( "icon" => 'icons/16/black/info.png',
                                    "text"  => $anzahltext
                    )
                )
            ),
            array  ("kategorie" => _("Aktionen:"),
                "eintrag" => array  (
                    array    (  "icon" => 'icons/16/black/search.png',
                                        "text"  => sprintf(_("Um weitere Veranstaltungen in Ihre pers&ouml;nliche Auswahl aufzunehmen, nutzen Sie bitte die %sSuchfunktion%s."), "<a href=\"sem_portal.php\">", "</a>")
                    ),
                    array    (  "icon" => 'icons/16/black/institute.png',
                                        "text"  => sprintf(_("Um Einrichtungen zu suchen und sich Informationen anzeigen zu lassen, nutzen Sie die %sEinrichtungssuche%s."), "<a href=\"institut_browse.php\">", "</a>")
                    ),
                    array    (  "icon" => 'icons/16/black/institute.png',
                                        "text"  => sprintf(_("Wenn Sie weitere Einrichtungen in Ihre pers&ouml;nliche Auswahl aufnehmen m&ouml;chten, k&ouml;nnen Sie sich hier %szuordnen%s."), "<a href=\"dispatch.php/settings/studies#einrichtungen\">", "</a>")
                    )
                )
            )
        );
    }

    // Determine whether there is anything new
    $temp = array();
    foreach ($groups as $group_members) {
        $temp[] = check_group_new($group_members, $my_obj);
    }

    // Only display link to "mark all as read" if there is anything new
    if (count(array_filter($temp))) {
        $infobox[count($infobox) - 1]['eintrag'][] = array(
            'icon' => 'icons/16/black/refresh.png',
            'text' => '<a href="' . URLHelper::getURL('?action=tabularasa') . '">'
                    . _('Alles als gelesen markieren')
                    . '</a>',
        );
    }

    $infobox[] = array('kategorie' => _("Einstellungen:"),
                    'eintrag' => array(array("icon" => 'icons/16/black/group.png',
                                                "text"  => sprintf(
                                                _("Gruppierung der angezeigten Veranstaltungen %s&auml;ndern%s."),
                                                "<a href=\"gruppe.php\">", "</a>")
                                                )));
    if (get_config('MAIL_NOTIFICATION_ENABLE')){
        $infobox[count($infobox)-1]['eintrag'][] = array(   'icon' => 'icons/16/black/mail.png',
                                                            'text' => sprintf(_("Benachrichtigung über neue Inhalte %sanpassen%s."),
                                                                    '<a href="' . URLHelper::getLink('dispatch.php/settings/notification'). '">', '</a>'));
    }


    $template = $GLOBALS["template_factory"]->open("meine_seminare/index_autor");

    $my_bosses = $default_deputies_enabled ? getDeputyBosses($user->id) : array();

    echo $template->render(compact(words("num_my_sem meldung group_field groups my_obj view _my_sem_open meldung chat_info chat_invs waitlists ".($deputies_enabled && $default_deputies_enabled && $perm->have_perm(getValidDeputyPerms(true)) ? "my_bosses " : "")."num_my_inst infobox")));
}


elseif ($auth->auth["perm"]=="admin") {

    if(Request::option('select_sem')){
        $_SESSION['_default_sem'] = Request::option('select_sem');
    }
    if ($_SESSION['_default_sem']){
        $semester = SemesterData::GetInstance();
        $one_semester = $semester->getSemesterData($_SESSION['_default_sem']);
        $sem_condition = "AND seminare.start_time <=".$one_semester["beginn"]." AND (".$one_semester["beginn"]." <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1) ";
    } else {
        $sem_condition = '';
    }

    // Prepare inner statement which obtains all institutes of a faculty
    $query = "SELECT a.Institut_id, a.Name, COUNT(seminar_id) AS num_sem
              FROM Institute AS a
              LEFT JOIN seminare ON (seminare.Institut_id = a.Institut_id {$sem_condition})
              WHERE fakultaets_id = ? AND a.Institut_id != a.fakultaets_id
              GROUP BY a.Institut_id
              ORDER BY a.Name, num_sem DESC";
    $institute_statement = DBManager::get()->prepare($query);

    // Prepare and execute main query which obtains all institutes
    // (regardless whether it's an institute or a faculty)
    $query = "SELECT a.Institut_id, b.Name, b.Institut_id = b.fakultaets_id AS is_fak,
                     COUNT(seminar_id) AS num_sem
              FROM user_inst AS a
              LEFT JOIN Institute AS b USING (Institut_id)
              LEFT JOIN seminare ON (seminare.Institut_id = b.Institut_id {$sem_condition})
              WHERE a.user_id = :user_id AND a.inst_perms = 'admin'
              GROUP BY a.Institut_id
              ORDER BY is_fak, num_sem DESC";
    $statement = DBManager::get()->prepare($query);
    $statement->bindValue(':user_id', $user->id);
    $statement->execute();

    while ($institute = $statement->fetch(PDO::FETCH_ASSOC)) {
        $_my_inst[$institute['Institut_id']] = array(
            'name'    => $institute['Name'],
            'is_fak'  => $institute['is_fak'],
            'num_sem' => $institute['num_sem']
        );
        if ($institute['is_fak']) {
            $num_inst = 0;

            $institute_statement->execute(array($institute['Institut_id']));
            while ($inst = $institute_statement->fetch(PDO::FETCH_ASSOC)) {
                if(!$_my_inst[$inst['Institut_id']]){
                    ++$num_inst;
                }
                $_my_inst[$inst['Institut_id']] = array(
                    'name'    => $inst['Name'],
                    'is_fak'  => 0 ,
                    'num_sem' => $inst['num_sem']
                );
            }
            $institute_statement->closeCursor();

            $_my_inst[$institute['Institut_id']]['num_inst'] = $num_inst;
        }
    }

    if (!is_array($_my_inst))
        $meldung="info§" . sprintf(_("Sie wurden noch keinen Einrichtungen zugeordnet. Bitte wenden Sie sich an einen der zust&auml;ndigen %sAdministratoren%s."), "<a href=\"dispatch.php/siteinfo/show\">", "</a>") . "§".$meldung;
    else {
        $_my_inst_arr = array_keys($_my_inst);
        if(Request::option('institut_id')){
            $user->cfg->store('MY_INSTITUTES_DEFAULT', isset($_my_inst[Request::option('institut_id')]) ? Request::option('institut_id') : $_my_inst_arr[0]);
        }
        $_my_admin_inst_id = $user->cfg->MY_INSTITUTES_DEFAULT ? : $_my_inst_arr[0];
        $sortby = Request::quoted('sortby');
        //tic #650 sortierung in der userconfig merken
        if (!empty($sortby) && in_array($sortby, words('VeranstaltungsNummer Name status teilnehmer'))) {
            $userConfig->store('MEINE_SEMINARE_SORT', $sortby);
        } else {
            $sortby = $userConfig->getValue('MEINE_SEMINARE_SORT');

            if ($sortby=="" || $sortby==false) {
                $sortby = "VeranstaltungsNummer ASC, Name ASC";
            }
        }
        if ($sortby == "teilnehmer") {
            $sortby = "teilnehmer DESC";
        } elseif ($sortby == "status") {
            $sortby = "status ASC, VeranstaltungsNummer ASC, Name ASC";
        }

        // Prepare teacher statement
        $query = "SELECT username, Nachname
                  FROM seminar_user
                  LEFT JOIN auth_user_md5 USING (user_id)
                  WHERE Seminar_id = ? AND status='dozent'
                  ORDER BY position, Nachname ASC";
        $teacher_statement = DBManager::get()->prepare($query);

        // Prepare and execute seminar statement
        $query = "SELECT Institute.Name AS Institut, seminare.VeranstaltungsNummer,
                         seminare.Seminar_id, seminare.Name, seminare.status, seminare.chdate,
                         seminare.start_time, seminare.admission_binding, seminare.visible,
                         seminare.modules, COUNT(seminar_user.user_id) AS teilnehmer,
                         IFNULL(visitdate, 0) AS visitdate,
                         sd1.name AS startsem, IF (duration_time = -1, :unlimited, sd2.name) AS endsem
                  FROM Institute
                  INNER JOIN seminare ON (seminare.Institut_id = Institute.Institut_id {$sem_condition})
                  STRAIGHT_JOIN seminar_user ON (seminare.seminar_id = seminar_user.seminar_id)
                  LEFT JOIN object_user_visits AS ouv
                    ON (ouv.object_id = seminare.Seminar_id AND ouv.user_id = :user_id AND ouv.type = 'sem')
                  LEFT JOIN semester_data AS sd1 ON (start_time BETWEEN sd1.beginn AND sd1.ende)
                  LEFT JOIN semester_data sd2 ON (start_time + duration_time BETWEEN sd2.beginn AND sd2.ende)
                  WHERE Institute.Institut_id = :institute_id
                  GROUP BY seminare.Seminar_id
                  ORDER BY {$sortby}";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':unlimited', _('unbegrenzt'));
        $statement->bindValue(':user_id', $user->id);
        $statement->bindValue('institute_id', $_my_admin_inst_id);
        $statement->execute();
        $seminars = $statement->fetchAll(PDO::FETCH_ASSOC);

        $num_my_sem = count($seminars);
        if (!$num_my_sem) {
            $meldung = "msg§"
                    . sprintf(_("An der Einrichtung \"%s\" sind zur Zeit keine Veranstaltungen angelegt."), htmlReady($_my_inst[$_my_admin_inst_id]['name']))
                    . "§"
                    . $meldung;
        } else {
            foreach ($seminars as $seminar) {
                $teacher_statement->execute(array($seminar['Seminar_id']));
                $dozenten = $teacher_statement->fetchAll(PDO::FETCH_ASSOC);
                $teacher_statement->closeCursor();

                $my_sem[$seminar['Seminar_id']] = array(
                    'visitdate'            => $seminar['visitdate'],
                    'institut'             => $seminar['Institut'],
                    'teilnehmer'           => $seminar['teilnehmer'],
                    'VeranstaltungsNummer' => $seminar['VeranstaltungsNummer'],
                    'name'                 => $seminar['Name'],
                    'status'               => $seminar['status'],
                    'chdate'               => $seminar['chdate'],
                    'start_time'           => $seminar['start_time'],
                    'startsem'             => $seminar['startsem'],
                    'endsem'               => $seminar['endsem'],
                    'binding'              => $seminar['admission_binding'],
                    'visible'              => $seminar['visible'],
                    'modules'              => $Modules->getLocalModules($seminar['Seminar_id'],
                                                                       'sem', $seminar['modules'], $seminar['status']),
                    'dozenten'             => $dozenten
                );
            }
            get_my_obj_values($my_sem, $GLOBALS['user']->id);
        }
    }



    $template = $GLOBALS["template_factory"]->open("meine_seminare/index_admin");
    $template->set_attribute('_default_sem', $_SESSION['_default_sem']);
    echo $template->render(compact(words("meldung _my_inst _my_admin_inst_id num_my_sem Modules my_sem")));
}
    include ('lib/include/html_end.inc.php');
    ob_end_flush(); //Outputbuffering beenden
    page_close();
