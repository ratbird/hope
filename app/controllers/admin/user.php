<?php
/**
 * user.php - controller class for the user-administration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Nico Müller <nico.mueller@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 * @since       2.1
 */
require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/user.php';
require_once 'lib/classes/UserManagement.class.php';
require_once 'vendor/email_message/blackhole_message.php';

/**
 *
 * controller class for the user-administration
 *
 */
class Admin_UserController extends AuthenticatedController
{
    /**
     * Common tasks for all actions.
     */
    public function before_filter(&$action, &$args)
    {
        global $perm;
        parent::before_filter($action, $args);

        // user must have root permission if restricted user management is disabled
        $perm->check($GLOBALS['RESTRICTED_USER_MANAGEMENT'] ? 'root' : 'admin');

        // set navigation
        Navigation::activateItem('/admin/config/user');

        //PageLayout
        PageLayout::setHelpKeyword("Admins.Benutzerkonten");
        PageLayout::setTitle(_("Benutzerverwaltung"));

        //ajax
        if (Request::isXhr()) {
            Header('Content-Type: text/plain;charset=windows-1252');
            $this->via_ajax = true;
            $this->set_layout(null);
        }
    }

    /**
     * Display searchbox and all searched users (if any).
     *
     * @param bool $advanced open or close the advanced searchfields
     */
    public function index_action($advanced = false)
    {
        global $perm;

        $this->perm = $perm;

        //Daten annehmen
        if (Request::submitted('reset')) {
            unset($_SESSION['admin']['user']);
        } elseif (Request::submitted('search')) {
            $request = $_SESSION['admin']['user'] = Request::getInstance();
        }

        //Suchparameter und Ergebnisse vorhanden
        if (isset($_SESSION['admin']['user']) && $_SESSION['admin']['user']['results']) {
            $request = $_SESSION['admin']['user'];
        }

        // Inaktivität für die suche anpassen
        $inaktiv = array($request['inaktiv'], $request['inaktiv_tage']);
        if (empty($request['inaktiv_tage']) && $request['inaktiv'] != 'nie') {
            $inaktiv = NULL;
        }

        //Datafields
        $datafields = DataFieldStructure::getDataFieldStructures("user");
        foreach ($datafields as $datafield) {
            if ($this->perm->have_perm($datafield->getViewPerms())) {
                $this->datafields[] = $datafield;
            }
        }

        //wenn suche durchgeführt
        if (isset($request)) {
            //suche mit datafields
            foreach ($datafields as $id => $datafield) {
                if ($request[$id] && ($datafield->getType() != 'selectbox' && $request[$id] != 'alle')) {
                    $search_datafields[$id] = $request[$id];
                }
            }

            //Suchparameter
            $this->user = $request;
            $this->sortby = Request::option('sortby', 'username');
            $this->order = Request::option('order', 'asc');
            if (Request::int('toggle')) {
                $this->order = $this->order == 'desc' ? 'asc' : 'desc';
            }
            $request['vorname'] = ($request['vorname']) ? $request['vorname'] : NULL;
            $request['nachname'] = ($request['nachname']) ? $request['nachname'] : NULL;

            //Daten abrufen
            $this->users = UserModel::getUsers($request['username'], $request['vorname'],
                $request['nachname'], $request['email'], $inaktiv, $request['perm'],
                $request['locked'], $search_datafields, $this->sortby, $this->order);

            // Fehler abfangen
            if ($this->users == 0) {
                PageLayout::postMessage(MessageBox::info(_('Sie haben keine Suchkriterien ausgewählt!')));
            } elseif (count($this->users) < 1) {
                PageLayout::postMessage(MessageBox::info(_('Es wurden keine Benutzer mit diesen Suchkriterien gefunden.')));
            } else {
                $_SESSION['admin']['user']['results'] = true;
            }
        }

        //show datafields search
        if ($advanced || count($search_datafields) > 0) {
            $this->advanced = true;
        }
    }

    /**
     * Deleting one or more users
     *
     * @param md5 $user_id
     * @param string $parent redirect to this page after deleting users
     */
    public function delete_action($user_id = NULL, $parent = '')
    {
        //deleting one user
        if (!is_null($user_id)) {
            $user = UserModel::getUser($user_id);

            //check user
            if (empty($user)) {
                PageLayout::postMessage(MessageBox::error(_('Fehler! Der zu löschende Benutzer ist nicht vorhanden.')));
            //antwort ja
            } elseif (!empty($user) && Request::submitted('delete')) {

                CSRFProtection::verifyUnsafeRequest();

                //if deleting user, go back to mainpage
                $parent = '';

                //deactivate message
                if (!Request::int('mail')) {
                    $dev_null = new blackhole_message_class();
                    $default_mailer = StudipMail::getDefaultTransporter();
                    StudipMail::setDefaultTransporter($dev_null);
                }
                //preparing delete
                $umanager = new UserManagement();
                $umanager->getFromDatabase($user_id);

                //delete
                if ($umanager->deleteUser(Request::option('documents', false))) {
                    $details = explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($umanager->msg, 0, -1)));
                    PageLayout::postMessage(MessageBox::success(htmlReady(sprintf(_('Der Benutzer "%s %s (%s)" wurde erfolgreich gelöscht'), $user['Vorname'], $user['Nachname'], $user['username']), $details)));
                } else {
                    $details = explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($umanager->msg, 0, -1)));
                    PageLayout::postMessage(MessageBox::error(htmlReady(sprintf(_('Fehler! Der Benutzer "%s %s (%s)" konnte nicht gelöscht werden'), $user['Vorname'], $user['Nachname'], $user['username']), $details)));
                }

                //reavtivate messages
                if (!Request::int('mail')) {
                    StudipMail::setDefaultTransporter($default_mailer);
                }

            //sicherheitsabfrage
            } elseif (!empty($user) && !Request::submitted('back')) {

                $this->flash['delete'] = array(
                    'question' => sprintf(_('Wollen Sie den Benutzer "%s %s (%s)" wirklich löschen?'), $user['Vorname'], $user['Nachname'], $user['username']),
                    'action' => ($parent != '') ? $this->url_for('admin/user/delete/' . $user_id . '/' . $parent) : $this->url_for('admin/user/delete/' . $user_id),
                );
            }

        //deleting more users
        } else {
            $user_ids = Request::getArray('user_ids');

            if (count($user_ids) == 0) {
                 PageLayout::postMessage(MessageBox::error(_('Bitte wählen Sie mindestens einen Benutzer zum Löschen aus.')));
                $this->redirect('admin/user/'.$parent);
                return;
            }

            if (Request::submitted('delete')) {

                CSRFProtection::verifyUnsafeRequest();

                //deactivate message
                if (!Request::int('mail')) {
                    $dev_null = new blackhole_message_class();
                    $default_mailer = StudipMail::getDefaultTransporter();
                    StudipMail::setDefaultTransporter($dev_null);
                }

                foreach ($user_ids as $i => $user_id) {
                    $users[$i] = UserModel::getUser($user_id);
                    //preparing delete
                    $umanager = new UserManagement();
                    $umanager->getFromDatabase($user_id);

                    //delete
                    if ($umanager->deleteUser(Request::option('documents', false))) {
                        $details = explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($umanager->msg, 0, -1)));
                        PageLayout::postMessage(MessageBox::success(htmlReady(sprintf(_('Der Benutzer "%s %s (%s)" wurde erfolgreich gelöscht'), $users[$i]['Vorname'], $users[$i]['Nachname'], $users[$i]['username']), $details)));
                    } else {
                        $details = explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($umanager->msg, 0, -1)));
                        PageLayout::postMessage(MessageBox::error(htmlReady(sprintf(_('Fehler! Der Benutzer "%s %s (%s)" konnte nicht gelöscht werden'), $users[$i]['Vorname'], $users[$i]['Nachname'], $users[$i]['username']), $details)));
                    }
                }

                //reactivate messages
                if (!Request::int('mail')) {
                    StudipMail::setDefaultTransporter($default_mailer);
                }

            //sicherheitsabfrage
            } elseif (!Request::submitted('back')) {
                $users = array();
                foreach ($user_ids as $user_id) {
                    $users[] = UserModel::getUser($user_id);
                }
                $this->flash['delete'] = array(
                    'question' => _('Wollen Sie folgende Benutzer wirklich löschen?'),
                    'action' => $this->url_for('admin/user/delete'),
                    'users' => $users
                );
            }
        }

        //liste wieder anzeigen
        if ($parent == 'edit') {
            $this->redirect('admin/user/edit/' . $user_id);
        } else {
            $this->redirect('admin/user/'.$parent);
        }
    }

    /**
     * Display all information according to the selected user. All details can
     * be changed and deleted.
     *
     * @param md5 $user_id
     */
    public function edit_action($user_id = null)
    {
        global $perm;

        //check submitted user_id
        if (is_null($user_id)) {
            if (Request::option('user')) {
                $user_id = Request::option('user');
            } else {
                PageLayout::postMessage(MessageBox::info(_('Sie haben keinen Benutzer ausgewählt!')));
                //liste wieder anzeigen
                $this->redirect('admin/user/');
                return;
            }
        }

        //get user
        $this->user = UserModel::getUser($user_id, NULL, true);

        // Änderungen speichern
        if (Request::submitted('edit')) {
            $editPerms = Request::getArray('perms');
            $um = new UserManagement($user_id);

            //new user data
            $editUser = array();
            if (count($editPerms)) {
                $editUser['auth_user_md5.perms'] = $editPerms[0];
            }
            foreach(words('Vorname Nachname auth_plugin visible') as $param) {
                if (Request::get($param)) $editUser['auth_user_md5.' . $param] = Request::get($param);
            }
            foreach(words('title_front title_rear geschlecht') as $param) {
                if (Request::get($param) !== null) $editUser['user_info.' . $param] = Request::get($param);
            }
            //change username
            if (Request::get('username') && $this->user['username'] != Request::get('username')) {
                $editUser['auth_user_md5.username'] = Request::get('username');
            }
            //change email
            if (Request::get('Email') && $this->user['Email'] != Request::get('Email')) {
                //disable mailbox validation
                if (Request::get('disable_mail_host_check')) {
                    $GLOBALS['MAIL_VALIDATE_BOX'] = false;
                }
                $editUser['auth_user_md5.Email'] = Request::get('Email');
            }

            //change password
            if (($perm->have_perm('root') && $GLOBALS['ALLOW_ADMIN_USERACCESS']) && (Request::get('pass_1') != '' || Request::get('pass_2') != '' ))
            {
                if (Request::get('pass_1') == Request::get('pass_2')){
                    if (strlen(Request::get('pass_1')) < 4) {
                        $details[] = _("Das Passwort ist zu kurz. Es sollte mindestens 4 Zeichen lang sein.");
                    } else {
                        $um->changePassword(Request::get('pass_1'));
                        $details = explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($um->msg, 0, -1)));
                    }
                } else {
                    $details[] = _("Bei der Wiederholung des Passwortes ist ein Fehler aufgetreten! Bitte geben Sie das exakte Passwort ein!");
                }
            }

            //deleting validation-key
            if (Request::get('delete_val_key') == "1") {
                $editUser['auth_user_md5.validation_key'] = '';
                $details[] = _('Der Validation-Key wurde entfernt.');
            }

            //locking the user
            if (Request::get('locked')) {
                $editUser['auth_user_md5.locked'] = 1;
                $editUser['auth_user_md5.lock_comment'] = Request::get('locked_comment');
                $editUser['auth_user_md5.locked_by'] = $auth->auth["uid"];
                $details[] = _('Der Benutzer wurde gesperrt.');
            }

            //changing studiendaten
            if (in_array($editPerms[0], array('autor', 'tutor', 'dozent')) && Request::option('new_studiengang') != 'none' && Request::option('new_abschluss') != 'none') {
                //change studycourses
                if (Request::option('new_studiengang') == 'none' || Request::option('new_abschluss') == 'none') {
                    $details[] = _('<b>Der Studiengang wurde nicht hinzugefügt.</b> Bitte geben Sie Fach und Abschluss ein.');
                } else {
                    $db = DbManager::get()->prepare("INSERT IGNORE INTO user_studiengang "
                                                   ."(user_id, studiengang_id, abschluss_id, semester) "
                                                   ."VALUES (?,?,?,?)");
                    $db->execute(array($user_id, Request::option('new_studiengang'), Request::option('new_abschluss'), Request::option('fachsem')));
                    $details[] = _('Der Studiengang wurde hinzugefügt.');
                }
            }

            //change institute for studiendaten
            if (in_array($editPerms[0], array('autor', 'tutor', 'dozent')) && Request::option('new_student_inst') != 'none' &&
                Request::option('new_student_inst') != Request::option('new_inst')) {
                log_event('INST_USER_ADD', Request::option('new_student_inst'), $user_id, 'user');
                $db = DbManager::get()->prepare("INSERT IGNORE INTO user_inst (user_id, Institut_id, inst_perms) "
                                               ."VALUES (?,?,'user')");
                $db->execute(array($user_id, Request::option('new_student_inst')));
                $details[] = _('Die Einrichtung wurde hinzugefügt.');
            }

            //change institute
            if (Request::option('new_inst') != 'none' && Request::option('new_student_inst') != Request::option('new_inst') && $editPerms[0] != 'root') {
                log_event('INST_USER_ADD', Request::option('new_inst'), $user_id, $editPerms[0]);
                $db = DbManager::get()->prepare("REPLACE INTO user_inst (user_id, Institut_id, inst_perms) "
                                               ."VALUES (?,?,?)");
                $db->execute(array($user_id, Request::option('new_inst'), $editPerms[0]));
                $details[] = _('Die Einrichtung wurde hinzugefügt.');
            } elseif (Request::option('new_inst') != 'none' && Request::option('new_student_inst') == Request::option('new_inst') && $editPerms[0] != 'root') {
                $details[] = _('<b>Die Einrichtung wurde nicht hinzugefügt.</b> Sie können keinen Benutzer gleichzeitig als Student und Mitarbeiter einer Einrichtung hinzufügen.');
            }

            //change userdomain
            if (Request::get('new_userdomain', 'none') != 'none' && $editPerms[0] != 'root') {
                $domain = new UserDomain(Request::option('new_userdomain'));
                $domain->addUser($user_id);
                $details[] = _('Die Nutzerdomäne wurde hinzugefügt.');
            }

            //change datafields
            $datafields = Request::getArray('datafields');
            foreach (DataFieldEntry::getDataFieldEntries($user_id) as $id => $entry) {
                if (isset($datafields[$id])) {
                    $entry->setValueFromSubmit($datafields[$id]);
                    if ($entry->isValid()) {
                        $entry->store();
                    }
                }
            }

            //change ablaufdatum
            if (Request::get('expiration_date_delete') == 1) {
                UserConfig::get($user_id)->delete("EXPIRATION_DATE");
            } elseif (Request::get('expiration_date')) {
                $a = explode(".",stripslashes(trim(Request::get('expiration_date'))));
                if ($timestamp = @mktime(0,0,0,$a[1],$a[0],$a[2])) {
                    UserConfig::get($user_id)->store("EXPIRATION_DATE", $timestamp);
                } else {
                    $details[] = _("Das Ablaufdatum wurde in einem falschen Format angegeben.");
                }
            }

            if ($perm->have_perm('root') && Request::get('lock_rule')) {
                $st = DbManager::get()->prepare("UPDATE user_info SET lock_rule=? WHERE user_id=?");
                $st->execute(array((Request::option('lock_rule') == 'none' ? '' : Request::option('lock_rule')), $user_id));
                if ($st->rowCount()) {
                    $details[] = _("Die Sperrebene wurde geändert.");
                }
            }

            if (!Request::int('u_edit_send_mail')) {
                $dev_null = new blackhole_message_class();
                $default_mailer = StudipMail::getDefaultTransporter();
                StudipMail::setDefaultTransporter($dev_null);
                $GLOBALS['MAIL_VALIDATE_BOX'] = false;
                $GLOBALS['MAIL_VALIDATE_HOST'] = false;
            }
            //save action and messages
            $um->changeUser($editUser);
            if (!Request::int('u_edit_send_mail')) {
                StudipMail::setDefaultTransporter($default_mailer);
            }
            //get message
            $umdetails = explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($um->msg, 0, -1)));
            $details = array_reverse(array_merge((array)$details,(array)$umdetails));
            PageLayout::postMessage(Messagebox::info(_('Hinweise:'), $details));
        }

        //get user informations
        $this->user = UserModel::getUser($user_id, NULL, true);
        $this->perm = $perm;
        $this->about = new about($this->user['username'], '');
        $this->studycourses = UserModel::getUserStudycourse($user_id);
        $this->student_institutes = UserModel::getUserInstitute($user_id, true);
        $this->institutes = UserModel::getUserInstitute($user_id);
        $this->available_institutes = UserModel::getAvailableInstitutes($user_id);
        $this->datafields = DataFieldStructure::getDataFieldStructures("user");
        $this->userfields = DataFieldEntry::getDataFieldEntries($user_id);
        $this->userdomains = UserDomain::getUserDomainsForUser($user_id);
        if (LockRules::CheckLockRulePermission($user_id) && LockRules::getObjectRule($user_id)->description) {
            PageLayout::postMessage(MessageBox::info(fixLinks(htmlReady(LockRules::getObjectRule($user_id)->description))));
        }
    }

    /*
     * Adding a new user to Stud.IP
     */
    public function new_action()
    {
        global $perm, $auth;

        $this->perm = $perm;

        //check auth_plugins
        if (!in_array("Standard", $GLOBALS['STUDIP_AUTH_PLUGIN'])) {
            PageLayout::postMessage(MessageBox::info(_("Die Standard-Authentifizierung ist ausgeschaltet. Das Anlegen von neuen Benutzern ist nicht möglich!")));
            $this->redirect('admin/user');
        }

        //get formdata
        $this->user = array(
            'username' => Request::get('username'),
            'perm' => Request::option('perm'),
            'visible' => Request::int('visible'),
            'Vorname' => Request::get('Vorname'),
            'Nachname' => Request::get('Nachname'),
            'geschlecht' => Request::int('geschlecht'),
            'title_front' => Request::get('title_front'),
            'title_rear' => Request::get('title_rear'),
            'Email' => Request::get('Email'),
            'auth_plugin' => Request::get('auth_plugin'),
            'institute' => Request::option('institute'),
        );

        //save new user
        if (Request::submitted('speichern')) {

            //disable mailbox validation
            if (Request::get('disable_mail_host_check')) {
                $GLOBALS['MAIL_VALIDATE_BOX'] = false;
            }

            //messagebox details
            $details = array();

            //new user data
            $newuser = array(
                'auth_user_md5.username' => $this->user['username'],
                'auth_user_md5.Vorname' => $this->user['Vorname'],
                'auth_user_md5.Nachname' => $this->user['Nachname'],
                'auth_user_md5.Email' => $this->user['Email'],
                'auth_user_md5.perms' => $this->user['perm'],
                'auth_user_md5.auth_plugin' => $this->user['auth_plugin'],
                'auth_user_md5.visible' => $this->user['visible'],
                'user_info.title_front' => $this->user['title_front'],
                'user_info.title_rear' => $this->user['title_rear'],
                'user_info.geschlecht' => $this->user['geschlecht'],
            );

            //create new user
            $UserManagement = new UserManagement();
            if ($UserManagement->createNewUser($newuser)) {

                //get user_id
                $user_id = $UserManagement->user_data['auth_user_md5.user_id'];

                //new user is added to an institute
                if (Request::get('institute')
                    && $perm->have_studip_perm('admin', Request::get('institute'))
                    && $UserManagement->user_data['auth_user_md5.perms'] != 'root') {

                    //log
                    log_event('INST_USER_ADD', Request::option('institute'), $user_id, $UserManagement->user_data['auth_user_md5.perms']);

                    //insert into database
                    $db = DBManager::get()->prepare("INSERT INTO user_inst (user_id, Institut_id, inst_perms) VALUES (?, ?, ?)");
                    $check = $db->execute(array($user_id, Request::option('institute'), $UserManagement->user_data['auth_user_md5.perms']));

                    //send email, if new user is an admin
                    if ($check) {
                        //check recipients
                        if (Request::get('enable_mail_admin') == "admin" && Request::get('enable_mail_dozent') == "dozent") {
                            $in = "'admin','dozent'";
                            $wem = "Admins und Dozenten";
                        } elseif (Request::get('enable_mail_admin') == "admin") {
                            $in = "'admin'";
                            $wem = "Admins";
                        } elseif (Request::get('enable_mail_dozent') == "dozent") {
                            $in = "'dozent'";
                            $wem = "Dozenten";
                        }

                        if (!empty($in) && Request::get('perm') == 'admin') {

                            $i = 0;
                            $notin = array();

                            $sql = "SELECT Name FROM Institute WHERE Institut_id='" . Request::option('institute') . "'";
                            $inst_name = DBManager::get()->query($sql)->fetch(PDO::FETCH_COLUMN);

                            //get admins
                            $sql = "SELECT a.user_id, b.Vorname, b.Nachname, b.Email FROM "
                                 . "user_inst a INNER JOIN auth_user_md5 b ON a.user_id = b.user_id "
                                 . "WHERE a.Institut_id = '" . Request::option('institute') . "' "
                                 . "AND a.inst_perms IN ({$in}) AND a.user_id != '{$user_id}'";
                            $users = DBManager::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($users as $admin) {
                                $subject = _("Neuer Administrator in Ihrer Einrichtung angelegt");
                                $mailbody = sprintf(_("Liebe(r) %s %s,\n\n"
                                          . "in der Einrichtung '%s' wurde %s %s als Administrator eingetragen "
                                          ." und steht Ihnen als neuer Ansprechpartner bei Fragen oder Problemen "
                                          ."in Stud.IP zur Verfügung. "),
                                          $admin['Vorname'], $admin['Nachname'],
                                          $inst_name, $this->user['Vorname'], $this->user['Nachname']);

                                StudipMail::sendMessage($admin['Email'], $subject, $mailbody);
                                $notin[] = $admin['user_id'];
                                $i++;
                            }

                            //Noch ein paar Mails für die Fakultätsadmins
                            if ($in != "'dozent'") {

                                //get admins
                                $sql = sprintf("SELECT a.user_id,b.Vorname,b.Nachname,b.Email FROM user_inst a "
                                     . "INNER JOIN auth_user_md5 b ON a.user_id = b.user_id WHERE a.user_id NOT "
                                     . "IN ('%s','%s') AND  a.Institut_id IN (SELECT fakultaets_id FROM Institute "
                                     . "WHERE Institut_id = '%s' AND fakultaets_id != Institut_id) AND "
                                     . "a.inst_perms = 'admin' AND a.user_id != '%s' ", implode("','",$notin),
                                     $user_id, Request::option('institute'), $user_id);
                                $fak_admins = DBManager::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($fak_admins as $admin) {
                                    $subject = _("Neuer Administrator in Ihrer Einrichtung angelegt");
                                    $mailbody = sprintf(_("Liebe(r) %s %s,\n\n"
                                              . "in der Einrichtung '%s' wurde %s %s als Administrator eingetragen "
                                              ." und steht Ihnen als neuer Ansprechpartner bei Fragen oder Problemen "
                                              ."in Stud.IP zur Verfügung. "),
                                              $admin['Vorname'], $admin['Nachname'],
                                              $inst_name, $this->user['Vorname'], $this->user['Nachname']);

                                    StudipMail::sendMessage($admin['Email'], $subject, $mailbody);
                                    $i++;
                                }
                            }
                            $details[] = sprintf(_("Es wurden ingesamt %s Mails an die %s der Einrichtung \"%s\" geschickt."), $i, $wem, htmlReady($inst_name));
                        }

                        $details[] = sprintf(_("Der Benutzer wurde erfolgreich in die Einrichtung \"%s\" mit dem Status \"%s\" eingetragen."), htmlReady($inst_name), $UserManagement->user_data['auth_user_md5.perms']);
                    } else {
                        $details[] = sprintf(_("Der Benutzer konnte nicht in die Einrichtung \"%s\" eingetragen werden."), htmlReady($inst_name));
                    }
                }

                //adding userdomain
                if (Request::get('select_dom_id')) {
                    $domain = new UserDomain(Request::option('select_dom_id'));
                    if ($perm->have_perm('root') || in_array($domain, UserDomain::getUserDomainsForUser($auth->auth["uid"]))) {
                        $domain->addUser($user_id);
                        $details[] = sprintf(_("Der Benutzer wurde in Nutzerdomäne \"%s\" eingetragen."), htmlReady($domain->getName()));
                    } else {
                        $details[] = _("Der Benutzer konnte nicht in die Nutzerdomäne eingetragen werden.");
                    }
                }

                //get message
                $details = explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($UserManagement->msg, 0, -1)));
                PageLayout::postMessage(MessageBox::success(_('Der Benutzer wurde erfolgreich angelegt.'), $details));
                $this->redirect('admin/user/edit/' . $user_id);
            } else {
                //get message
                $details = explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($UserManagement->msg, 0, -1)));
                PageLayout::postMessage(Messagebox::error(_('Der Benutzer konnte nicht angelegt werden.'), $details));
            }
        }

        if ($this->perm->have_perm('root')) {
            $sql = "SELECT Institut_id, Name, 1 AS is_fak  FROM Institute WHERE Institut_id=fakultaets_id ORDER BY Name";
            $faks = DBManager::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $domains = UserDomain::getUserDomains();
        } else {
            $sql = "SELECT a.Institut_id, Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak "
                 . "FROM user_inst a LEFT JOIN Institute b USING (Institut_id) "
                 . "WHERE a.user_id='{$auth->auth['uid']}' AND a.inst_perms='admin' ORDER BY is_fak, Name";
            $faks = DBManager::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $domains = UserDomain::getUserDomainsForUser($auth->auth["uid"]);
        }

        foreach ($faks as $index => $fak) {
            if ($fak['is_fak']) {
                $sql = "SELECT Institut_id, Name FROM Institute WHERE fakultaets_id='{$fak['Institut_id']}' "
                     . "AND institut_id!='{$fak['Institut_id']}' ORDER BY Name";
                $faks[$index]['institutes'] = DBManager::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        $this->domains = $domains;
        $this->faks = $faks;
        $this->perms = $perm;
    }

    /**
     * Migrate 2 users to 1 account. This is a part of the old numit-plugin
     */
    function migrate_action()
    {
        //check submitted form
        if (Request::submitted('umwandeln')) {
            $old_id = Request::option('old_id');
            $new_id = Request::option('new_id');

            //check existing users
            if (UserModel::check($old_id) && UserModel::check($new_id)) {
                $identity = Request:: get('convert_ident');
                $details = UserModel::convert($old_id, $new_id, $identity);

                //delete old user
                if (Request::get('delete_old')) {
                    //no messaging
                    $dev_null = new blackhole_message_class();
                    $default_mailer = StudipMail::getDefaultTransporter();
                    StudipMail::setDefaultTransporter($dev_null);

                    //preparing delete
                    $umanager = new UserManagement();
                    $umanager->getFromDatabase($old_id);

                    //delete
                    $umanager->deleteUser();
                    $details = array_merge($details, explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($umanager->msg, 0, -1))));

                    //reactivate messaging
                    StudipMail::setDefaultTransporter($default_mailer);
                }

                PageLayout::postMessage(MessageBox::success(_('Die Benutzer wurden erfolgreich migriert.'), $details));
                $this->redirect('admin/user/edit/' . $new_id);
            } else {
                PageLayout::postMessage(MessageBox::error(_("Bitte wählen Sie zwei gültige Benutzer aus.")));
            }
        }
    }

    /**
     * Set the password of an user to a new random password, without security-query
     *
     * @param md5 $user_id
     */
    public function change_password_action($user_id)
    {
        $UserManagement = new UserManagement($user_id);
        if ($UserManagement->setPassword()) {
            PageLayout::postMessage(Messagebox::success(_('Das Passwort wurde neu gesetzt.')));
        } else {
            $details = explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($UserManagement->msg, 0, -1)));
            PageLayout::postMessage(Messagebox::error(_('Die Änderungen konnten nicht gespeichert werden.'), $details));
        }
        $this->redirect('admin/user/edit/' . $user_id);
    }

    /**
     * Unlock an user, without security-query
     *
     * @param md5 $user_id
     */
    public function unlock_action($user_id)
    {
        $db = DBManager::get()->prepare("UPDATE auth_user_md5 SET locked = 0, lock_comment = NULL, locked_by = NULL WHERE user_id = ?");
        $db->execute(array($user_id));
        if ($db->rowCount() == 1) {
            PageLayout::postMessage(Messagebox::success(_('Der Benutzer wurde erfolgreich entsperrt.')));
        } else {
            PageLayout::postMessage(Messagebox::error(_('Der Benutzer konnte nicht entsperrt werden.')));
        }
        $this->redirect('admin/user/edit/' . $user_id);
    }

    /**
     * Display institute informations of an user and save changes to it.
     *
     * @param md5 $user_id
     * @param md5 $institute_id
     */
    public function edit_institute_action($user_id, $institute_id)
    {
        if (Request::submitted('uebernehmen')) {
            //standard-values
            $values=array();
            foreach(words('inst_perms visible raum sprechzeiten Telefon Fax') as $param) {
                $values[$param] = Request::get(strtolower($param), '');
            }
            foreach(words('externdefault visible') as $param) {
                $values[$param] = Request::int($param, 0);
            }

            //change datafields
            $datafields = Request::getArray('datafields');
            foreach ($datafields as $id => $data) {
                $struct = new DataFieldStructure(array("datafield_id" => $id));
                $struct->load();
                $entry  = DataFieldEntry::createDataFieldEntry($struct, array($user_id, $institute_id));
                $entry->setValueFromSubmit($data);
                if ($entry->isValid()) {
                    $entry->store();
                }
            }

            //store to database
            UserModel::setInstitute($user_id, $institute_id, $values);

            //output
            PageLayout::postMessage(Messagebox::success(_('Die Einrichtungsdaten des Benutzers wurden erfolgreich geändert.')));
            $this->redirect('admin/user/edit/' . $user_id);
        }

        $this->user = UserModel::getUser($user_id, NULL, true);
        $this->institute = UserModel::getInstitute($user_id, $institute_id);
        $about = new about($this->user['username'], '');
        $this->perms = $about->allowedInstitutePerms();
        $this->datafields = DataFieldEntry::getDataFieldEntries(array($user_id, $institute_id),'userinstrole');
    }

    /**
     * Delete an studycourse of an user , without a security-query
     *
     * @param md5 $user_id
     * @param md5 $fach_id
     * @param md5 $abschluss_id
     */
    public function delete_studycourse_action($user_id, $fach_id, $abschlus_id)
    {
        $db = DBManager::get()->prepare("DELETE FROM user_studiengang WHERE user_id = ? AND studiengang_id = ? AND abschluss_id = ?");
        $db->execute(array($user_id, $fach_id, $abschlus_id));
        if ($db->rowCount() == 1) {
            PageLayout::postMessage(MessageBox::success(_('Der Studiengang wurde erfolgreich gelöscht.')));
        } else {
            PageLayout::postMessage(MessageBox::error(_('Der Studiengang konnte nicht gelöscht werden.')));
        }
        $this->redirect('admin/user/edit/' . $user_id);
    }

    /**
     * Delete an institute of an user , without a security-query
     *
     * @param md5 $user_id
     * @param md5 $institut_id
     */
    public function delete_institute_action($user_id, $institut_id)
    {
        $db = DBManager::get()->prepare("DELETE FROM user_inst WHERE user_id = ? AND Institut_id = ?");
        $db->execute(array($user_id, $institut_id));
        if ($db->rowCount() == 1) {
            PageLayout::postMessage(MessageBox::success(_('Die Einrichtung wurde erfolgreich gelöscht.')));
        } else {
            PageLayout::postMessage(MessageBox::error(_('Die Einrichtung konnte nicht gelöscht werden.')));
        }
        $this->redirect('admin/user/edit/' . $user_id);
    }

    /**
     * Delete an assignment of an user to an userdomain, without a security-query
     *
     * @param md5 $user_id
     * @param md5 $domain_id
     */
    public function delete_userdomain_action($user_id, $domain_id)
    {
        $domain = new UserDomain($domain_id);
        $domain->removeUser($user_id);
        PageLayout::postMessage(MessageBox::success(_('Die Zuordnung zur Nutzerdomäne wurde erfolgreich gelöscht.')));
        $this->redirect('admin/user/edit/' . $user_id);
    }
}
