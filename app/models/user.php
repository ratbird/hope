<?php
/**
 * user.php - model class for the useradministration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Nico Müller <nico.mueller@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 * @since       2.1
 */

class UserModel
{
    /**
     * Holt alle Daten eines Nutzers oder ein Datenfeld (als String)
     *
     * @param md5 $user_id
     * @param string $field
     * @return array()
     */
    public static function getUser($user_id, $field = NULL, $full = false)
    {
        if(!is_null($field)) {
            $dbquery = "SELECT {$field} FROM auth_user_md5 au WHERE au.user_id = '{$user_id}'";
            return DBManager::get()->query($dbquery)->fetchColumn();
        } else {
            if ($full) {
                $dbquery = "SELECT *, UNIX_TIMESTAMP(ud.changed) as changed_timestamp FROM auth_user_md5 au"
                         . " LEFT JOIN user_info ui ON (au.user_id = ui.user_id)"
                         . " LEFT JOIN user_data ud ON au.user_id = ud.sid";
            } else {
                $dbquery = "SELECT * FROM auth_user_md5 au";
            }
            $dbquery .= " WHERE au.user_id = '{$user_id}'";

            return DBManager::get()->query($dbquery)->fetch(PDO::FETCH_ASSOC);
        }
    }

    /**
     *
     * @param md5 $user_id
     * @return array()
     */
    public static function getUserStudycourse($user_id)
    {
        $sql = "SELECT s.name AS fach, a.name AS abschluss, us.studiengang_id AS fach_id, "
             . "us.abschluss_id AS abschluss_id, us.semester "
             . "FROM user_studiengang AS us "
             . "LEFT JOIN studiengaenge AS s ON us.studiengang_id = s.studiengang_id "
             . "LEFT JOIN abschluss AS a ON us.abschluss_id = a.abschluss_id "
             . "WHERE user_id='{$user_id}'";
        return DBManager::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     *
     * @param md5 $user_id
     * @param bool $as_student
     * @return array()
     */
    public static function getUserInstitute($user_id, $as_student = false)
    {
        $sql = "SELECT ui.*, i.Name FROM Institute AS i "
             . "LEFT JOIN user_inst AS ui ON i.Institut_id = ui.Institut_id "
             . "WHERE user_id='{$user_id}'";
        if ($as_student) {
            $sql .= " AND inst_perms = 'user'";
        } else {
             $sql .= " AND inst_perms <> 'user'";
        }
        return DBManager::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Sucht Benutzer die den Suchangaben entsprechen
     *
     * @param string $username
     * @param string $vorname
     * @param string $nachname
     * @param string $email
     * @param string $inaktiv
     * @param string $perms
     * @param int $locked
     * @param string $sortby
     */
    public static function getUsers($username = NULL, $vorname = NULL, $nachname = NULL,
                                    $email = NULL, $inaktiv = NULL, $perms = NULL,
                                    $locked = NULL, $datafields= NULL, $sort = NULL, $order = 'DESC')
    {
        // keine suchkriterien
        if (empty($username) && empty($vorname) && empty($nachname) && empty($email)
            && empty($locked) && empty($inaktiv) && empty($datafields)) {
            return 0;
        }

        $query = "SELECT DISTINCT au.*, UNIX_TIMESTAMP(ud.changed) as changed_timestamp, ui.mkdate "
                ."FROM auth_user_md5 au "
                ."LEFT JOIN datafields_entries de ON de.range_id=au.user_id "
                ."LEFT JOIN user_data ud ON au.user_id = ud.sid "
                ."LEFT JOIN user_info ui ON (au.user_id = ui.user_id) "
                ."WHERE au.username like '%".$username."%' "
                ."AND au.vorname like '%".$vorname."%'"
                ."AND au.nachname like '%".$nachname."%' "
                ."AND au.Email like '%".$email."%' ";

        //permissions
        if (!is_null($perms) && $perms != "alle") {
            $query .= "AND au.perms like '%".$perms."%' ";
        }

        //locked user
        if ($locked == 1) {
            $query .= "AND au.locked = '1' ";
        }

        //inactivity
        if (!is_null($inaktiv) && $inaktiv[0] != 'nie') {
            $query .= "AND ud.changed {$inaktiv[0]} TIMESTAMPADD(DAY, -{$inaktiv[1]}, NOW()) ";
        } elseif (!is_null($inaktiv)) {
            $query .= "AND ud.changed IS NULL ";
        }

        //datafields
        if (!is_null($datafields) && count($datafields) > 0) {
            foreach ($datafields as $id => $entry) {
                $query .= "AND de.datafield_id = '{$id}' AND de.content = '{$entry}' ";
            }
        }

        //sortieren
        switch ($sort) {
            case "perms":
                $query .= "ORDER BY au.perms {$order}, au.username";
                break;
            case "Vorname":
                $query .= "ORDER BY au.Vorname {$order}, au.Nachname";
                break;
            case "Nachname":
                $query .= "ORDER BY au.Nachname {$order}, au.Vorname";
                break;
            case "Email":
                $query .= "ORDER BY au.Email {$order}, au.username";
                break;
            case "changed":
                $query .= "ORDER BY ud.changed {$order}, au.username";
                break;
            case "mkdate":
                $query .= "ORDER BY ui.mkdate {$order}, au.username";
                break;
            case "auth_plugin":
                $query .= "ORDER BY au.auth_plugin {$order}, au.username";
                break;
            default:
                $query .= " ORDER BY au.username {$order}";
        }

        //ergebnisse zurückgeben
        return DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * checks if a user exists
     *
     * @param md5 $user_id
     * @return boolean
     */
    public static function check($user_id)
    {
        return DBManager::get()->query("SELECT 1 FROM auth_user_md5 WHERE user_id = '{$user_id}'")->fetchColumn();
    }

    /**
     *
     * @param string $old_user
     * @param string $new_user
     * @param boolean $identity
     */
    public static function convert($old_id, $new_id, $identity = false)
    {
        $messages = array();

        //Identitätsrelevante Daten migrieren
        if ($identity) {
            // Namen übertragen
            $query = "SELECT Vorname, Nachname FROM auth_user_md5 WHERE user_id = '{$old_id}'";
            $db = DBManager::get()->query($query)->fetch(PDO::FETCH_ASSOC);
            $update = "UPDATE IGNORE auth_user_md5 SET Vorname = '{$db['Vorname']}', Nachname = '{$db['Nachname']}' WHERE user_id = '{$old_id}'";
            DBManager::get()->exec($update);

            // Veranstaltungseintragungen
            self::removeDoubles('seminar_user', 'Seminar_id', $new_id, $old_id);
            $query = "UPDATE IGNORE seminar_user SET user_id = '{$new_id}' WHERE user_id = '{$old_id}'";
            DBManager::get()->exec($query);

            self::removeDoubles('admission_seminar_user', 'seminar_id', $new_id, $old_id);
            $query = "UPDATE IGNORE admission_seminar_user SET user_id = '{$new_id}' WHERE user_id = '{$old_id}'";
            DBManager::get()->exec($query);

            // Persönliche Infos
            $query = "DELETE FROM user_info WHERE user_id = '{$new_id}'";
            DBManager::get()->exec($query);
            $query = "UPDATE IGNORE user_info SET user_id = '{$new_id}' WHERE user_id = '{$old_id}'";
            DBManager::get()->exec($query);

            // Studiengänge
            self::removeDoubles('user_studiengang', 'studiengang_id', $new_id, $old_id);
            $query = "UPDATE IGNORE user_studiengang SET user_id = '{$new_id}' WHERE user_id = '{$old_id}'";
            DBManager::get()->exec($query);

            // Gästebuch
            $query = "UPDATE IGNORE guestbook SET user_id = '{$new_id}' WHERE user_id = '{$old_id}'";
            DBManager::get()->exec($query);
            $query = "UPDATE IGNORE guestbook SET range_id = '{$new_id}' WHERE range_id = '{$old_id}'";
            DBManager::get()->exec($query);

            // Eigene Kategorien
            $query = "UPDATE IGNORE kategorien SET range_id = '{$new_id}' WHERE range_id = '{$old_id}'";
            DBManager::get()->exec($query);

            // Institute
            self::removeDoubles('user_inst', 'Institut_id', $new_id, $old_id);
            $query = "UPDATE IGNORE user_inst SET user_id = '{$new_id}' WHERE user_id = '{$old_id}'";
            DBManager::get()->exec($query);

            // Generische Datenfelder
            $query = "DELETE FROM datafields_entries WHERE range_id = '{$new_id}'";
            DBManager::get()->exec($query);
            $query = "UPDATE IGNORE datafields_entries SET range_id = '{$new_id}' WHERE range_id = '{$old_id}'";
            DBManager::get()->exec($query);

            //Buddys
            $query = "UPDATE IGNORE contact SET owner_id='{$new_id}' WHERE owner_id = '{$old_id}'";
            DBManager::get()->exec($query);

            $messages[] = _('Identitätsrelevante Daten wurden migriert.');
        }

        // Restliche Daten übertragen

        // Forumsbeiträge
        $query = "UPDATE IGNORE px_topics SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);

        // Dateieintragungen und Ordner
        $query = "UPDATE IGNORE dokumente SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);
        $query = "UPDATE IGNORE folder SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);

        //Kalender
        $query = "UPDATE IGNORE calendar_events SET range_id='{$new_id}' WHERE range_id = '{$old_id}'";
        DBManager::get()->exec($query);
        $query = "UPDATE IGNORE calendar_events SET autor_id='{$new_id}' WHERE autor_id = '{$old_id}'";
        DBManager::get()->exec($query);

        //Archiv
        self::removeDoubles('archiv_user', 'seminar_id', $new_id, $old_id);
        $query = "UPDATE IGNORE archiv_user SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);

        // Evaluationen
        $query = "UPDATE IGNORE eval SET author_id='{$new_id}' WHERE author_id = '{$old_id}'";
        DBManager::get()->exec($query);
        self::removeDoubles('eval_user', 'eval_id', $new_id, $old_id);
        $query = "UPDATE IGNORE eval_user SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);
        $query = "UPDATE IGNORE evalanswer_user SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);

        // Kategorien
        $query = "UPDATE IGNORE kategorien SET range_id='{$new_id}' WHERE range_id = '{$old_id}'";
        DBManager::get()->exec($query);

        // Literatur
        $query = "UPDATE IGNORE lit_catalog SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);
        $query = "UPDATE IGNORE lit_list SET range_id='{$new_id}' WHERE range_id = '{$old_id}'";
        DBManager::get()->exec($query);

        // Nachrichten (Interne)
        $query = "UPDATE IGNORE message SET autor_id='{$new_id}' WHERE autor_id = '{$old_id}'";
        DBManager::get()->exec($query);
        self::removeDoubles('message_user', 'message_id', $new_id, $old_id);
        $query = "UPDATE IGNORE message_user SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);

        // News
        $query = "UPDATE IGNORE news SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);
        $query = "UPDATE IGNORE news_range SET range_id='{$new_id}' WHERE range_id = '{$old_id}'";
        DBManager::get()->exec($query);

        // Referenztabelle: Abstimmungen, etc.
        self::removeDoubles('object_user', 'object_id', $new_id, $old_id);
        $query = "UPDATE IGNORE object_user SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);

        // Informationsseiten
        $query = "UPDATE IGNORE scm SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);

        // Statusgruppeneinträge
        self::removeDoubles('statusgruppe_user', 'statusgruppe_id', $new_id, $old_id);
        $query = "UPDATE IGNORE statusgruppe_user SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);

        // Termine
        $query = "UPDATE IGNORE termine SET autor_id='{$new_id}' WHERE autor_id = '{$old_id}'";
        DBManager::get()->exec($query);

        //Votings
        $query = "UPDATE IGNORE vote SET author_id='{$new_id}' WHERE author_id = '{$old_id}'";
        DBManager::get()->exec($query);
        $query = "UPDATE IGNORE vote SET range_id='{$new_id}' WHERE range_id = '{$old_id}'";
        DBManager::get()->exec($query);
        self::removeDoubles('vote_user', 'vote_id', $new_id, $old_id);
        $query = "UPDATE IGNORE vote_user SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);
        self::removeDoubles('voteanswers_user', 'answer_id', $new_id, $old_id);
        $query = "UPDATE IGNORE voteanswers_user SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);

        //Wiki
        $query = "UPDATE IGNORE wiki SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);
        $query = "UPDATE IGNORE wiki_locks SET user_id='{$new_id}' WHERE user_id = '{$old_id}'";
        DBManager::get()->exec($query);

        //Adressbucheinträge
        $query = "UPDATE IGNORE contact SET owner_id = '{$new_id}' WHERE owner_id = '{$old_id}'";
        DBManager::get()->exec($query);

        $messages[] = _('Dateien, Termine, Adressbuch, Nachrichten und weitere Daten wurden migriert.');
        return $messages;
    }

    /**
     *
     * @param md5 $userid
     * @return array()
     */
    public static function getAvailableInstitutes($userid)
    {
        return DBManager::get()->query("SELECT a.Institut_id, a.Name FROM Institute "
                                      ."AS a LEFT JOIN user_inst AS b ON (b.user_id='{$userid}' "
                                      ."AND a.Institut_id=b.Institut_id) WHERE b.Institut_id "
                                      ."IS NULL ORDER BY a.Name")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     *
     * @param string $table
     * @param string $field
     * @param md5 $new_id
     * @param md5 $old_id
     */
    private static function removeDoubles($table, $field, $new_id, $old_id)
    {
        $items = array();

        $query = "SELECT a.".$field." AS field_item FROM ".$table." a, ".$table." b "
               . "WHERE a.user_id = '{$new_id}' AND b.user_id = '{$old_id}' AND a.".$field." = b.".$field."";
        $results = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $value) {
            array_push($items, $value['field_item']);
        }

        $query = "DELETE FROM ".$table." WHERE user_id='{$new_id}' AND ".$field." IN ('".implode("','", $items)."')";
        DBManager::get()->exec($query);
    }
}
