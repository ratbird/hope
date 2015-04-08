<?php
# Lifter010: TODO

/*
 * Copyright (C) 2014 - Arne Schröder <schroeder@data-quest.de>
 *
 * formerly institut_main.php - Die Eingangsseite fuer ein Institut
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/authenticated_controller.php';
require_once ("lib/statusgruppe.inc.php");  //Funktionen der Statusgruppen
require_once ("lib/classes/DataFieldEntry.class.php");
require_once('lib/classes/searchtypes/SQLSearch.class.php');
include_once($GLOBALS['PATH_EXPORT'] . "/export_linking_func.inc.php");

class Institute_MembersController extends AuthenticatedController
{
    protected $allow_nobody = true;

    function before_filter(&$action, &$args) {
        if (Request::option('auswahl')) {
            Request::set('cid', Request::option('auswahl'));
        }

        parent::before_filter($action, $args);

        $this->admin_view = $GLOBALS['perm']->have_perm('admin') && Request::option('admin_view') !== null;
        PageLayout::addStylesheet('multi-select.css');
        PageLayout::addScript('jquery/jquery.multi-select.js');
        PageLayout::addScript('multi_person_search.js');
    }

    /**
     * show institute members page
     *
     * @return void
     */
    function index_action()
    {
        if ($GLOBALS['perm']->have_studip_perm('tutor', $GLOBALS['SessSemName'][1])) {
            $this->rechte = true;
        }

        // this page is used for administration (if the user has the proper rights)
        // or for just displaying the workers and their roles
        if ($this->admin_view || $GLOBALS['perm']->have_perm('admin')) {
            PageLayout::setTitle(_("Verwaltung der MitarbeiterInnen"));
            Navigation::activateItem('/admin/institute/faculty');
            $GLOBALS['perm']->check("admin");
        } else {
            PageLayout::setTitle(_("Liste der MitarbeiterInnen"));
            Navigation::activateItem('/course/faculty/view');
            $GLOBALS['perm']->check("autor");
        }

        require_once 'lib/admin_search.inc.php';

        //get ID from a open Institut. We have to wait until a links_*.inc.php has opened an institute (necessary if we jump directly to this page)
        if ($GLOBALS['SessSemName'][1])
            $this->inst_id=$GLOBALS['SessSemName'][1];

        if (!$this->admin_view) {
            checkObject();
            checkObjectModule("personal");
        }

        if ($this->admin_view && isset($this->inst_id) && !$GLOBALS['perm']->have_studip_perm('admin', $this->inst_id)) {
            $this->admin_view = false;
        }

        //Change header_line if open object
        $header_line = getHeaderLine($this->inst_id);
        if ($header_line) {
            PageLayout::setTitle($header_line." - ".PageLayout::getTitle());
        }

        if ($this->admin_view || !isset($this->inst_id)) {
            include 'lib/include/admin_search_form.inc.php';
        }

        // check the given parameters or initialize them
        if ($GLOBALS['perm']->have_studip_perm("admin", $this->inst_id)) {
            $accepted_columns = array("Nachname", "inst_perms");
        } else {
            $accepted_columns = array("Nachname");
        }
        $sortby = Request::option('sortby');
        $this->extend = Request::option('extend');
        if (!in_array($sortby, $accepted_columns)) {
            $sortby = "Nachname";
            $this->statusgruppe_user_sortby = "position";
        } else {
            $this->statusgruppe_user_sortby = $sortby;
        }
        $this->direction = Request::option('direction');
        if ($this->direction == "ASC") {
            $new_direction = "DESC";
        } else if ($this->direction == "DESC") {
            $new_direction = "ASC";
        } else {
            $this->direction = "ASC";
            $new_direction = "DESC";
        }
        $this->show = Request::option('show');
        if (!isset($this->show)) {
            $this->show = "funktion";
        }
        URLHelper::addLinkParam('admin_view', $this->admin_view);
        URLHelper::addLinkParam('sortby', $sortby);
        URLHelper::addLinkParam('direction', $this->direction);
        URLHelper::addLinkParam('show', $this->show);
        URLHelper::addLinkParam('extend', $this->extend);

        $this->groups = GetAllStatusgruppen($this->inst_id);
        $this->group_list = GetRoleNames($this->groups, 0, '', true);

        $cmd = Request::option('cmd');
        $role_id = Request::option('role_id');
        $username = Request::get('username');
        if ($cmd == 'removeFromGroup' && $GLOBALS['perm']->have_studip_perm('admin', $this->inst_id)) {
            $query = "DELETE FROM statusgruppe_user
                      WHERE statusgruppe_id = ? AND user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($role_id, get_userid($username)));

            if ($statement->rowCount() > 0) {
                PageLayout::postMessage(MessageBox::info(sprintf(_('%s wurde von der Liste der MitarbeiterInnen gelöscht.'),
                                   User::findByUsername($username)->getFullName())));
            }
        }

        if ($cmd == 'removeFromInstitute' && $GLOBALS['perm']->have_studip_perm('admin', $this->inst_id)) {
            $del_user_id = get_userid($username);
            if (is_array($this->group_list) && count($this->group_list) > 0) {
                $query = "DELETE FROM statusgruppe_user
                          WHERE statusgruppe_id IN (?) AND user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(array_keys($this->group_list), $del_user_id));
            }

            $query = "DELETE FROM user_inst
                      WHERE user_id = ? AND Institut_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($del_user_id, $this->inst_id));

            if ($statement->rowCount() > 0) {
                PageLayout::postMessage(MessageBox::info(sprintf(_('%s wurde von der Liste der MitarbeiterInnen gelöscht.'),
                                   User::findByUsername($username)->getFullName())));
            }

            log_event('INST_USER_DEL', $this->inst_id, $del_user_id);
            checkExternDefaultForUser($del_user_id);
        }

        // Jemand soll ans Institut...
        $ins_id = Request::option('ins_id');
        $this->mp = MultiPersonSearch::load("inst_member_add" . $this->inst_id);
        $additionalCheckboxes = $this->mp->getAdditionalOptionArray();

        if ($additionalCheckboxes != NULL && array_search("admins", $additionalCheckboxes) !== false) {
            $enable_mail_admin = true;
        }
        if ($additionalCheckboxes != NULL && array_search("dozenten", $additionalCheckboxes) !== false) {
            $enable_mail_dozent = true;
        }

        if (count($this->mp->getAddedUsers()) !== 0) {
            foreach ($this->mp->getAddedUsers() as $u_id) {

                $query = "SELECT inst_perms FROM user_inst WHERE Institut_id = ? AND user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($ins_id, $u_id));
                $inst_perms = $statement->fetchColumn();

                if ($inst_perms && $inst_perms != 'user') {
                    // der Admin hat Tomaten auf den Augen, der Mitarbeiter sitzt schon im Institut
                    my_error("<b>" . _("Die Person ist bereits in der Einrichtung eingetragen. Um Rechte etc. zu ändern folgen Sie dem Link zu den Nutzerdaten der Person!") . "</b>");
                } else {  // mal nach dem globalen Status sehen
                    $query = "SELECT {$GLOBALS['_fullname_sql']['full']} AS fullname, perms
                              FROM auth_user_md5
                              LEFT JOIN user_info USING (user_id)
                              WHERE user_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($u_id));
                    $user_info = $statement->fetch(PDO::FETCH_ASSOC);

                    $Fullname = $user_info['fullname'];
                    $perms    = $user_info['perms'];

                    if ($perms == 'root') {
                        PageLayout::postMessage(MessageBox::error(_('ROOTs können nicht berufen werden!')));
                    } elseif ($perms == 'admin') {
                        if ($GLOBALS['perm']->have_perm('root') || (!$GLOBALS['SessSemName']["is_fak"] && $GLOBALS['perm']->have_studip_perm("admin",$GLOBALS['SessSemName']["fak"]))) {
                            // Emails schreiben...
                            if ($enable_mail_admin && $enable_mail_dozent) {
                                $in = array('admin', 'dozent');
                                $wem = 'Admins und Dozenten';
                            } else if($enable_mail_admin){
                                $in = array('admin');
                                $wem = 'Admins';
                            } else if($enable_mail_dozent) {
                                $in = array('dozent');
                                $wem = 'Dozenten';
                            }
                            if (!empty($in)) {
                                $notin = array();
                                $mails_sent = 0;

                                $query = "SELECT Name FROM Institute WHERE Institut_id = ?";
                                $statement = DBManager::get()->prepare($query);
                                $statement->execute(array($ins_id));
                                $instname = $statement->fetchColumn();

                                $vorname = $Fullname;
                                $nachname = ''; // siehe $vorname

                                $query = "SELECT user_id, Vorname, Nachname, Email
                                          FROM user_inst
                                          INNER JOIN auth_user_md5 USING (user_id)
                                          WHERE Institut_id = ? AND inst_perms IN (?)";
                                $statement = DBManager::get()->prepare($query);
                                $statement->execute(array($ins_id, $in));

                                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                                    $user_language = getUserLanguagePath($row['user_id']);
                                    include("locale/$user_language/LC_MAILS/new_admin_mail.inc.php");
                                    StudipMail::sendMessage($row['Email'], $subject, $mailbody);
                                    $notin[] = $row['user_id'];

                                    $mails_sent += 1;
                                }
                                if (!(count($in) == 1 && reset($in) == 'dozent')) {
                                    $notin[] = $u_id;
                                    //Noch ein paar Mails für die Fakultätsadmins
                                    $query = "SELECT user_id, Vorname, Nachname, Email
                                              FROM user_inst
                                              INNER JOIN auth_user_md5 USING (user_id)
                                              WHERE user_id NOT IN (?) AND inst_perms = 'admin'
                                                AND Institut_id IN (
                                                        SELECT fakultaets_id
                                                        FROM Institute
                                                        WHERE Institut_id = ? AND Institut_id != fakultaets_id
                                                    )";
                                    $statement = DBManager::get()->prepare($query);
                                    $statement->execute(array($notin, $ins_id));

                                    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                                        $user_language = getUserLanguagePath($row['user_id']);
                                        include("locale/$user_language/LC_MAILS/new_admin_mail.inc.php");
                                        StudipMail::sendMessage($row['Email'], $subject, $mailbody);

                                        $mails_sent += 1;
                                    }
                                }
                                PageLayout::postMessage(MessageBox::info(_(sprintf(_("Es wurden ingesamt %s Mails an die %s der Einrichtung geschickt."),$mails_sent,$wem))));
                            }

                            log_event('INST_USER_ADD', $ins_id ,$u_id, 'admin');

                            // als admin aufnehmen
                            $query = "INSERT INTO user_inst (user_id, Institut_id, inst_perms)
                                      VALUES (?, ?, 'admin')";
                            $statement = DBManager::get()->prepare($query);
                            $statement->execute(array($u_id, $ins_id));

                            PageLayout::postMessage(MessageBox::info(sprintf(_("%s wurde als \"admin\" in die Einrichtung aufgenommen."), $Fullname)));
                        } else {
                            PageLayout::postMessage(MessageBox::error(_("Sie haben keine Berechtigung einen Admin zu berufen!")));
                        }
                    } else {
                        //ok, aber nur hochstufen auf Maximal-Status (hat sich selbst schonmal gemeldet als Student an dem Inst)
                        if ($inst_perms == 'user') {
                            // ok, neu aufnehmen als das was er global ist
                            $query = "UPDATE user_inst
                                      SET inst_perms = ?
                                      WHERE user_id = ? AND Institut_id = ?";
                            $statement = DBManager::get()->prepare($query);
                            $statement->execute(array($perms, $u_id, $ins_id));

                            log_event('INST_USER_STATUS', $ins_id ,$u_id, $perms);
                        } else {
                            $query = "INSERT INTO user_inst (user_id, Institut_id, inst_perms)
                                      VALUES (?, ?, ?)";
                            $statement = DBManager::get()->prepare($query);
                            $statement->execute(array($u_id, $ins_id, $perms));

                            log_event('INST_USER_ADD', $ins_id ,$u_id, $perms);
                        }
                        if ($statement->rowCount()) {
                            PageLayout::postMessage(MessageBox::info(sprintf(_("%s wurde als \"%s\" in die Einrichtung aufgenommen. Um Rechte etc. zu ändern folgen Sie dem Link zu den Nutzerdaten der Person!"), $Fullname, $perms)));
                        } else {
                            PageLayout::postMessage(MessageBox::error(sprintf(_("%s konnte nicht in die Einrichtung aufgenommen werden!"), $Fullname)));
                        }
                    }
                }
                checkExternDefaultForUser($u_id);
            }
            $this->inst_id=$ins_id;
            $this->mp->clearSession();
        }

        $lockrule = LockRules::getObjectRule($this->inst_id);
        if ($this->admin_view && $lockrule->description && LockRules::Check($this->inst_id, 'participants')) {
            PageLayout::postMessage(MessageBox::info(formatLinks($lockrule->description)));
        }

        if ($this->inst_id != '' && $this->inst_id != '0') {
            $inst_name = $GLOBALS['SessSemName'][0];
            $this->auswahl = $this->inst_id;

            // Mitglieder zählen und E-Mail-Adressen zusammenstellen
            if ($GLOBALS['perm']->have_studip_perm('admin', $this->inst_id)) {
                $query = "SELECT Email
                          FROM user_inst
                          LEFT JOIN auth_user_md5 USING (user_id)
                          WHERE Institut_id = ? AND inst_perms != 'user' AND Email != ''";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($this->auswahl));
                $this->mail_list = $statement->fetchAll(PDO::FETCH_COLUMN);

                $this->count = count($this->mail_list);
            } else {
                $this->count = CountMembersStatusgruppen($this->auswahl);
            }

            if ($this->admin_view) {
                if (!LockRules::Check($this->inst_id, 'participants')) {
                    // Der Admin will neue Sklaven ins Institut berufen...
                    $query = "SELECT DISTINCT auth_user_md5.user_id, {$GLOBALS['_fullname_sql']['full_rev_username']} AS fullname
                              FROM auth_user_md5
                              LEFT JOIN user_info USING (user_id)
                              LEFT JOIN user_inst ON user_inst.user_id = auth_user_md5.user_id AND Institut_id = :ins_id
                              WHERE perms NOT IN ('user', 'root')
                                AND (user_inst.inst_perms = 'user' OR user_inst.inst_perms IS NULL)
                                AND (Vorname LIKE :input OR Nachname LIKE :input OR username LIKE :input)
                              ORDER BY Nachname, Vorname";
                    $InstituteUser = new SQLSearch($query, _('Nutzer eintragen'), 'user_id');
                    $search_obj = new SQLSearch("SELECT auth_user_md5.user_id, {$GLOBALS['_fullname_sql']['full_rev']} as fullname, username, perms "
                        . "FROM auth_user_md5 "
                        . "LEFT JOIN user_info ON (auth_user_md5.user_id = user_info.user_id) "
                        . "WHERE "
                        . "username LIKE :input OR Vorname LIKE :input "
                        . "OR CONCAT(Vorname,' ',Nachname) LIKE :input "
                        . "OR CONCAT(Nachname,' ',Vorname) LIKE :input "
                        . "OR Nachname LIKE :input OR {$GLOBALS['_fullname_sql']['full_rev']} LIKE :input "
                        . " ORDER BY fullname ASC",
                        _("Nutzer suchen"), "user_id");
                    $query = "SELECT user_id , {$GLOBALS['_fullname_sql']['full_rev']} as fullname
                              FROM statusgruppe_user
                              LEFT JOIN auth_user_md5 USING (user_id)
                              LEFT JOIN user_info USING (user_id)
                              LEFT JOIN user_inst USING (user_id)
                              WHERE Institut_id = :inst_id
                                AND inst_perms != 'user'
                            ORDER BY fullname ASC";
                    $statement = DBManager::get()->prepare($query);
                    $statement->bindValue(':inst_id', $this->inst_id);
                    $statement->execute();

                    $defaultSelectedUser = array_unique(array_map(function ($member) {
                        return $member['user_id'];
                    }, $statement->fetchAll(PDO::FETCH_ASSOC)));
                    URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
                    $this->mp = MultiPersonSearch::get("inst_member_add" . $this->inst_id)
                    ->setLinkText(_("MitarbeiterInnen hinzufügen"))
                    ->setDefaultSelectedUser($defaultSelectedUser)
                    ->setTitle(_('Personen in die Einrichtung eintragen'))
                    ->setExecuteURL(URLHelper::getLink("dispatch.php/institute/members", array('admin_view' => 1, 'ins_id' => $this->inst_id)))
                    ->setSearchObject($search_obj)
                    ->setAdditionalHTML('<p><strong>' . _('Nur bei Zuordnung eines Admins:') .' </strong> <label>Benachrichtigung der <input name="additional[]" value="admins" type="checkbox">' . _('Admins') .'</label>
                                         <label><input name="additional[]" value="dozenten" type="checkbox">' . _('Dozenten') . '</label></p>')
                    ->render();
                }
            }

            $this->datafields_list = DataFieldStructure::getDataFieldStructures("userinstrole");

            $dview = array();
            if ($this->extend == 'yes') {
                if (is_array($GLOBALS['INST_ADMIN_DATAFIELDS_VIEW']['extended'])) {
                    $dview = $GLOBALS['INST_ADMIN_DATAFIELDS_VIEW']['extended'];
                }
                else $dview = array();
            } else {
                if(is_array($GLOBALS['INST_ADMIN_DATAFIELDS_VIEW']['default'])) {
                    $dview = $GLOBALS['INST_ADMIN_DATAFIELDS_VIEW']['default'];
                }
                else $dview = array();
            }

            if (!is_array($dview) || sizeof($dview) == 0) {
                $this->struct = array (
                    "raum" => array("name" => _("Raum"), "width" => "10%"),
                    "sprechzeiten" => array("name" => _("Sprechzeiten"), "width" => "10%"),
                    "telefon" => array("name" => _("Telefon"), "width" => "10%"),
                    "email" => array("name" => _("E-Mail"), "width" => "10%")
                );

                if ($this->extend == 'yes') {
                    $this->struct["homepage"] = array("name" => _("Homepage"), "width" => "10%");
                }
            } else {
                foreach ($this->datafields_list as $entry) {
                    if (in_array($entry->getId(), $dview) === TRUE) {
                        $this->struct[$entry->getId()] = array (
                            'name' => $entry->getName(),
                            'width' => '10%'
                        );
                    }
                }
            }

            // this array contains the structure of the table for the different views
            if ($this->extend == "yes") {
                switch ($this->show) {
                    case 'liste' :
                        if ($GLOBALS['perm']->have_perm("admin")) {
                            $this->table_structure = array(
                                "name" => array(
                                    "name" => _("Name"),
                                    "link" => "?sortby=Nachname&direction=" . $new_direction,
                                    "width" => "30%"),
                                "status" => array(
                                    "name" => _("Status"),
                                    "link" => "?sortby=inst_perms&direction=" . $new_direction,
                                    "width" => "10"),
                                "statusgruppe" => array(
                                    "name" => _("Funktion"),
                                    "width" => "15%")
                            );
                        }
                        else {
                            $this->table_structure = array(
                                "name" => array(
                                    "name" => _("Name"),
                                    "link" => "?sortby=Nachname&direction=" . $new_direction,
                                    "width" => "30%"),
                                "statusgruppe" => array(
                                    "name" => _("Funktion"),
                                    "width" => "10%")
                            );
                        }
                        break;
                    case 'status' :
                        $this->table_structure = array(
                            "name" => array(
                                "name" => _("Name"),
                                "link" => "?sortby=Nachname&direction=" . $new_direction,
                                "width" => "30%"),
                            "statusgruppe" => array(
                                "name" => _("Funktion"),
                                "width" => "15%")
                        );
                        break;
                    default :
                        if ($GLOBALS['perm']->have_perm("admin")) {
                            $this->table_structure = array(
                                "name" => array(
                                    "name" => _("Name"),
                                    "link" => "?sortby=Nachname&direction=" . $new_direction,
                                    "width" => "30%"),
                                "status" => array(
                                    "name" => _("Status"),
                                    "link" => "?sortby=inst_perms&direction=" . $new_direction,
                                    "width" => "10")
                            );
                        }
                        else {
                            $this->table_structure = array(
                                "name" => array(
                                    "name" => _("Name"),
                                    "link" => "?sortby=Nachname&direction=" . $new_direction,
                                    "width" => "30%")
                            );
                        }
                } // switch
            } else {
                switch ($this->show) {
                    case 'liste' :
                        if ($GLOBALS['perm']->have_perm("admin")) {
                            $this->table_structure = array(
                                "name" => array(
                                    "name" => _("Name"),
                                    "link" => "?sortby=Nachname&direction=" . $new_direction,
                                    "width" => "35%"),
                                "status" => array(
                                    "name" => _("Status"),
                                    "link" => "?sortby=inst_perms&direction=" . $new_direction,
                                    "width" => "10"),
                                "statusgruppe" => array(
                                    "name" => _("Funktion"),
                                    "width" => "15%")
                            );
                        }
                        else {
                            $this->table_structure = array(
                                "name" => array(
                                    "name" => _("Name"),
                                    "link" => "?sortby=Nachname&direction=" . $new_direction,
                                    "width" => "30%"),
                                "statusgruppe" => array(
                                    "name" => _("Funktion"),
                                    "width" => "15%")
                            );
                        }
                        break;
                    case 'status' :
                        $this->table_structure = array(
                            "name" => array(
                                "name" => _("Name"),
                                "link" => "?sortby=Nachname&direction=" . $new_direction,
                                "width" => "40%"),
                            "statusgruppe" => array(
                                "name" => _("Funktion"),
                                "width" => "20%")
                        );
                        break;
                    default :
                        if ($GLOBALS['perm']->have_perm("admin")) {
                            $this->table_structure = array(
                                "name" => array(
                                    "name" => _("Name"),
                                    "link" => "?sortby=Nachname&direction=" . $new_direction,
                                    "width" => "40%"),
                                "status" => array(
                                    "name" => _("Status"),
                                    "link" => "?sortby=inst_perms&direction=" . $new_direction,
                                    "width" => "15")
                            );
                        }
                        else {
                            $this->table_structure = array(
                                "name" => array(
                                    "name" => _("Name"),
                                    "link" => "?sortby=Nachname&direction=" . $new_direction,
                                    "width" => "40%")
                            );
                        }
                } // switch
            }

            // StEP 154: Nachricht an alle Mitglieder der Gruppe; auch auf der inst_members.php
            if ($this->admin_view OR $GLOBALS['perm']->have_studip_perm('autor', $GLOBALS['SessSemName'][1])) {
                $nachricht['nachricht'] = array(
                    "name" => _("Aktionen"),
                    "width" => "5%"
                );
            }

            $this->table_structure = array_merge((array)$this->table_structure, (array)$this->struct);
            $this->table_structure = array_merge((array)$this->table_structure, (array)$nachricht);

            $this->colspan = sizeof($this->table_structure)+1;

            if ($this->show == "funktion") {
                $all_statusgruppen = $this->groups;
                if ($all_statusgruppen) {
                    $this->display_recursive($all_statusgruppen, 0, '', $dview);
                }
                if ($GLOBALS['perm']->have_perm('admin')) {
                    $assigned = GetAllSelected($this->auswahl) ?: array('');
                    if ($this->extend == 'yes') {
                        $query = "SELECT {$GLOBALS['_fullname_sql']['full_rev']} AS fullname,
                                         ui.inst_perms, ui.raum, ui.sprechzeiten, ui.Telefon,
                                         aum.Email, aum.user_id, aum.username
                                  FROM user_inst AS ui
                                  LEFT JOIN auth_user_md5 AS aum USING (user_id)
                                  LEFT JOIN user_info USING (user_id)
                                  WHERE ui.Institut_id = :inst_id AND ui.inst_perms != 'user'
                                    AND ui.user_id NOT IN (:user_ids)
                                  ORDER BY :sort_column :sort_order";
                    } else {
                        $query = "SELECT {$GLOBALS['_fullname_sql']['full_rev']} AS fullname,
                                         ui.inst_perms, ui.raum, ui.Telefon,
                                         aum.user_id, aum.username
                                  FROM user_inst AS ui
                                  LEFT JOIN auth_user_md5 AS aum USING (user_id)
                                  LEFT JOIN user_info USING (user_id)
                                  WHERE ui.Institut_id = :inst_id AND ui.inst_perms != 'user'
                                    AND ui.user_id NOT IN (:user_ids)
                                  ORDER BY :sort_column :sort_order";
                    }
                    $statement = DBManager::get()->prepare($query);
                    $statement->bindValue(':inst_id', $this->auswahl);
                    $statement->bindValue(':user_ids', $assigned, StudipPDO::PARAM_ARRAY);
                    $statement->bindValue(':sort_column', $sortby, StudipPDO::PARAM_COLUMN);
                    $statement->bindValue(':sort_order', $this->direction, StudipPDO::PARAM_COLUMN);
                    $statement->execute();

                    $institut_members = $statement->fetchAll(PDO::FETCH_ASSOC);

                    if (count($institut_members) > 0) {
                        $template = $GLOBALS['template_factory']->open('institute/_table_body.php');
                        $template->colspan = $this->colspan;
                        $template->th_title = _("keiner Funktion zugeordnet");
                        $template->members = $institut_members;
                        $template->range_id = $this->auswahl;
                        $template->struct = $this->struct;
                        $template->structure = $this->table_structure;
                        $template->datafields_list = $this->datafields_list;
                        $template->group_list = $this->group_list;
                        $template->admin_view = $this->admin_view;
                        $template->dview = $dview;
                        $this->table_content .= $template->render();
                    }
                }
            } elseif ($this->show == 'status') {
                $inst_permissions = array(
                    'admin'  => _('Admin'),
                    'dozent' => _('DozentIn'),
                    'tutor'  => _('TutorIn'),
                    'autor'  => _('AutorIn')
                );

                $query = "SELECT {$GLOBALS['_fullname_sql']['full_rev']} AS fullname,
                                 ui.raum, ui.sprechzeiten, ui.Telefon,
                                 inst_perms, Email, user_id, username,
                                 (ui.visible = 1 AND auth_user_md5.visible != 'never') AS visible
                          FROM user_inst AS ui
                          LEFT JOIN auth_user_md5 USING (user_id)
                          LEFT JOIN user_info USING (user_id)
                          WHERE ui.Institut_id = :inst_id AND inst_perms = :perms
                          ORDER BY :sort_column :sort_order";
                $statement = DBManager::get()->prepare($query);

                foreach ($inst_permissions as $key => $permission) {
                    $statement->bindValue(':inst_id', $this->auswahl);
                    $statement->bindValue(':perms', $key);
                    $statement->bindValue(':sort_column', $sortby, StudipPDO::PARAM_COLUMN);
                    $statement->bindValue(':sort_order', $this->direction, StudipPDO::PARAM_COLUMN);
                    $statement->execute();

                    $institut_members = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $institut_members = array_filter($institut_members, function ($member) {
                        return $GLOBALS['perm']->have_perm('admin') || $member['visible'];
                    });

                    $statement->closeCursor();

                    if (count($institut_members) > 0) {
                        $template = $GLOBALS['template_factory']->open('institute/_table_body.php');
                        $template->mail_status = true;
                        $template->group_colspan = $this->colspan - 2;
                        $template->colspan = $this->colspan;
                        $template->th_title = $permission;
                        $template->members = $institut_members;
                        $template->range_id = $this->auswahl;
                        $template->struct = $this->struct;
                        $template->structure = $this->table_structure;
                        $template->datafields_list = $this->datafields_list;
                        $template->group_list = $this->group_list;
                        $template->admin_view = $this->admin_view;
                        $template->dview = $dview;
                        $this->table_content .= $template->render();
                    }
                }
            } else {
                $parameters = array();
                if ($this->extend == 'yes') {
                    if ($GLOBALS['perm']->have_perm('admin')) {
                        $query = "SELECT {$GLOBALS['_fullname_sql']['full_rev']} AS fullname,
                                         ui.raum, ui.sprechzeiten, ui.Telefon, ui.inst_perms,
                                         user_id, info.Home, aum.Email, aum.username,
                                         (ui.visible = 1 AND aum.visible != 'never') AS visible
                                  FROM user_inst AS ui
                                  LEFT JOIN auth_user_md5 AS aum USING (user_id)
                                  LEFT JOIN user_info AS info USING (user_id)
                                  WHERE ui.Institut_id = :inst_id AND ui.inst_perms != 'user'
                                  ORDER BY :sort_column :sort_order";
                    } else {
                        $query = "SELECT {$GLOBALS['_fullname_sql']['full_rev']} AS fullname,
                                         ui.raum, ui.sprechzeiten, ui.Telefon,
                                         user_id, info.Home, aum.Email, aum.username, Institut_id,
                                         (ui.visible = 1 AND aum.visible != 'never') AS visible
                                  FROM statusgruppen
                                  LEFT JOIN statusgruppe_user USING (statusgruppe_id)
                                  LEFT JOIN user_inst AS ui USING (user_id)
                                  LEFT JOIN auth_user_md5 AS aum USING (user_id)
                                  LEFT JOIN user_info AS info USING (user_id)
                                  WHERE statusgruppen.statusgruppe_id IN (:statusgruppen_ids)
                                    AND Institut_id = :inst_id
                                  ORDER BY :sort_column :sort_order";
                        $parameters[':statusgruppen_ids'] = getAllStatusgruppenIDS($this->auswahl);
                    }
                } else {
                    if ($GLOBALS['perm']->have_perm('admin')) {
                        $query = "SELECT {$GLOBALS['_fullname_sql']['full_rev']} AS fullname,
                                         ui.raum, ui.sprechzeiten, ui.Telefon,
                                         user_id, username, inst_perms,
                                         (ui.visible = 1 AND auth_user_md5.visible != 'never') AS visible
                                  FROM user_inst AS ui
                                  LEFT JOIN auth_user_md5 USING (user_id)
                                  LEFT JOIN user_info USING (user_id)
                                  WHERE ui.Institut_id = :inst_id AND inst_perms != 'user'
                                  ORDER BY :sort_column :sort_order";
                    } else {
                        $query = "SELECT {$GLOBALS['_fullname_sql']['full_rev']} AS fullname,
                                         ui.raum, ui.sprechzeiten, ui.Telefon,
                                         user_id, username, Institut_id,
                                         (ui.visible = 1 AND aum.visible != 'never') AS visible
                                  FROM statusgruppen
                                  LEFT JOIN statusgruppe_user AS su USING (statusgruppe_id)
                                  LEFT JOIN user_inst AS ui USING (user_id)
                                  LEFT JOIN auth_user_md5 AS aum USING (user_id)
                                  LEFT JOIN user_info USING (user_id)
                                  WHERE statusgruppen.statusgruppe_id IN (:statusgruppen_ids)
                                    AND Institut_id = :inst_id
                                  GROUP BY user_id
                                  ORDER BY :sort_column :sort_order";
                        $parameters[':statusgruppen_ids'] = getAllStatusgruppenIDS($this->auswahl);
                    }
                }
                $statement = DBManager::get()->prepare($query);
                $statement->bindValue(':inst_id', $this->auswahl);
                $statement->bindValue(':sort_column', $sortby, StudipPDO::PARAM_COLUMN);
                $statement->bindValue(':sort_order', $this->direction, StudipPDO::PARAM_COLUMN);

                $aborted = false;
                foreach ($parameters as $parameter => $value) {
                    if (is_array($value) && count($value) === 0) {
                        $aborted = true;
                        break;
                    }
                    $statement->bindValue($parameter, $value);
                }

                if (!$aborted) {
                    $statement->execute();

                    $institut_members = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $institut_members = array_filter($institut_members, function ($member) {
                        return $GLOBALS['perm']->have_perm('admin') || $member['visible'];
                    });

                    if (count($institut_members) > 0) {
                        $template = $GLOBALS['template_factory']->open('institute/_table_body.php');
                        $template->colspan = $this->colspan;
                        $template->members = $institut_members;
                        $template->range_id = $this->auswahl;
                        $template->struct = $this->struct;
                        $template->structure = $this->table_structure;
                        $template->datafields_list = $this->datafields_list;
                        $template->group_list = $this->group_list;
                        $template->admin_view = $this->admin_view;
                        $template->dview = $dview;
                        $this->table_content .= $template->render();
                    }
                }
            }
        }
    }

    function display_recursive($roles, $level = 0, $title = '', $dview = array()) {
        foreach ($roles as $role_id => $role) {
            if ($title == '') {
                $zw_title = $role['role']->getName();
            } else {
                $zw_title = $title .' > '. $role['role']->getName();
            }
            if ($this->extend == 'yes') {
                $query = "SELECT {$GLOBALS['_fullname_sql']['full_rev']} AS fullname, ui.inst_perms,
                                 ui.raum, ui.sprechzeiten, ui.Telefon, aum.Email, aum.user_id,
                                 aum.username, info.Home, statusgruppe_id,
                                 (ui.visible = 1 AND aum.visible != 'never') AS visible
                          FROM statusgruppe_user
                          LEFT JOIN auth_user_md5 AS aum USING (user_id)
                          LEFT JOIN user_info AS info USING (user_id)
                          LEFT JOIN user_inst AS ui USING (user_id)
                          WHERE ui.Institut_id = :inst_id AND statusgruppe_id = :role_id
                            AND ui.inst_perms != 'user'
                          ORDER BY :sort_column :sort_order";
            } else {
                $query = "SELECT {$GLOBALS['_fullname_sql']['full_rev']} AS fullname, user_inst.raum,
                                 user_inst.sprechzeiten, user_inst.Telefon, inst_perms,
                                 Email, user_id, username, statusgruppe_id,
                                 (user_inst.visible = 1 AND auth_user_md5.visible != 'never') AS visible
                          FROM statusgruppe_user
                          LEFT JOIN auth_user_md5 USING (user_id)
                          LEFT JOIN user_info USING (user_id)
                          LEFT JOIN user_inst USING (user_id)
                          WHERE Institut_id = :inst_id AND statusgruppe_id = :role_id
                            AND inst_perms != 'user'
                          ORDER BY :sort_column :sort_order";
            }
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':inst_id', $this->auswahl);
            $statement->bindValue(':role_id', $role_id);
            $statement->bindValue(':sort_column', $this->statusgruppe_user_sortby, StudipPDO::PARAM_COLUMN);
            $statement->bindValue(':sort_order', $this->direction, StudipPDO::PARAM_COLUMN);
            $statement->execute();

            $institut_members = $statement->fetchAll(PDO::FETCH_ASSOC);
            $institut_members = array_filter($institut_members, function ($member) {
                return $GLOBALS['perm']->have_perm('admin') || $member['visible'];
            });

            if (count($institut_members) > 0) {
                $template = $GLOBALS['template_factory']->open('institute/_table_body.php');
                // StEP 154: Nachricht an alle Mitglieder der Gruppe
                if ($GLOBALS['perm']->have_studip_perm('autor', $GLOBALS['SessSemName'][1]) AND $GLOBALS["ENABLE_EMAIL_TO_STATUSGROUP"] == true) {
                    $template->mail_gruppe = true;
                    $template->group_colspan = $this->colspan - 2;
                }
                $template->colspan = $this->colspan;
                $template->th_title = $zw_title;
                $template->members = $institut_members;
                $template->range_id = $this->auswahl;
                $template->struct = $this->struct;
                $template->structure = $this->table_structure;
                $template->datafields_list = $this->datafields_list;
                $template->group_list = $this->group_list;
                $template->admin_view = $this->admin_view;
                $template->dview = $dview;
                $this->table_content .= $template->render();

            }
            if ($role['child']) {
                $this->display_recursive($role['child'], $level + 1, $zw_title, $dview);
            }
        }
    }
}