<?php
# Lifter010: TODO
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

/**
 *
 *
 */
class UserModel
{
    /**
     * Return all informations of an user, otherwise the selected field of the
     * databse-table auth_user_md5, if field is set.
     *
     * @param md5 $user_id
     * @param string $field
     * @param bool $full
     *
     * @return string (if field is set), otherwise array()
     */
    public static function getUser($user_id, $field = NULL, $full = false)
    {
        //single field
        if(!is_null($field)) {
            $dbquery = "SELECT *,IFNULL(auth_plugin, 'preliminary') as auth_plugin FROM auth_user_md5 au WHERE au.user_id = ?";

            $db = DBManager::get()->prepare($dbquery);
            $db->execute(array($user_id));
            $row = $db->fetch(PDO::FETCH_ASSOC);
            return $row[$field];

        // all fields + optional user_info and user_online
        } else {
            if ($full) {
                $dbquery = "SELECT ui.*,au.*, last_lifesign as changed_timestamp,IFNULL(auth_plugin, 'preliminary') as auth_plugin FROM auth_user_md5 au"
                         . " LEFT JOIN user_info ui ON (au.user_id = ui.user_id)"
                         . " LEFT JOIN user_online uo ON au.user_id = uo.user_id";
            } else {
                $dbquery = "SELECT * FROM auth_user_md5 au";
            }
            $dbquery .= " WHERE au.user_id = ?";

            $db = DBManager::get()->prepare($dbquery);
            $db->execute(array($user_id));
            return $db->fetch(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Return the studycourses of an user.
     *
     * @param md5 $user_id
     * @return array() list of studycourses
     */
    public static function getUserStudycourse($user_id)
    {
        $sql = "SELECT s.name AS fach, a.name AS abschluss, us.studiengang_id AS fach_id, "
             . "us.abschluss_id AS abschluss_id, us.semester "
             . "FROM user_studiengang AS us "
             . "LEFT JOIN studiengaenge AS s ON us.studiengang_id = s.studiengang_id "
             . "LEFT JOIN abschluss AS a ON us.abschluss_id = a.abschluss_id "
             . "WHERE user_id=? ORDER BY fach";
        $db = DBManager::get()->prepare($sql);
        $db->execute(array($user_id));
        return $db->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return the Institutes of an user depending of the student-status.
     *
     * @param md5 $user_id
     * @param bool $as_student
     * @return array()
     */
    public static function getUserInstitute($user_id, $as_student = false)
    {
        $sql = "SELECT i.Institut_id, i.Name, ui.* FROM Institute AS i "
             . "LEFT JOIN user_inst AS ui ON i.Institut_id = ui.Institut_id "
             . "WHERE user_id=?";
        if ($as_student) {
            $sql .= " AND inst_perms = 'user'";
        } else {
             $sql .= " AND inst_perms <> 'user'";
        }
        $sql .= " ORDER BY priority ASC, Name ASC";
        $db = DBManager::get()->prepare($sql);
        $db->execute(array($user_id));
        return $db->fetchGrouped(PDO::FETCH_ASSOC);
    }

    /**
     * Search for users, depending of the used parameters.
     *
     * @param string $username
     * @param string $vorname
     * @param string $nachname
     * @param string $email
     * @param int $inaktiv
     * @param string $perms
     * @param int $locked
     * @param array() $datafields
     * @param string $sort
     * @param string $order
     *
     * @return array() list of found users
     */
    public static function getUsers($username = NULL, $vorname = NULL, $nachname = NULL,
                                    $email = NULL, $inaktiv = NULL, $perms = NULL,
                                    $locked = NULL, $datafields= NULL, $userdomains = NULL, $auth_plugins = NULL,$sort = NULL, $order = 'DESC')
    {
        // keine suchkriterien
        if (empty($username) && empty($email) && empty($vorname) && empty($nachname)
            && empty($locked) && empty($inaktiv) && empty($datafields)) {
            return 0;
        }

        $db = DBManager::get();
        foreach (words('username vorname nachname email') as $param) {
            if ($$param) {
                if (preg_match('/[%_]/', $$param)) {
                    $$param = $db->quote($$param);
                } else {
                    $$param = $db->quote('%' . $$param . '%');
                }
            }
        }
        $query = "SELECT DISTINCT au.*,IFNULL(auth_plugin, 'preliminary') as auth_plugin, uo.last_lifesign as changed_timestamp, ui.mkdate, "
                ." GROUP_CONCAT(DISTINCT uds.name) as userdomains "
                ."FROM auth_user_md5 au "
                ."LEFT JOIN datafields_entries de ON de.range_id=au.user_id "
                ."LEFT JOIN user_online uo ON au.user_id = uo.user_id "
                ."LEFT JOIN user_info ui ON (au.user_id = ui.user_id) "
                ."LEFT JOIN user_userdomains uud ON (au.user_id = uud.user_id) "
                ."LEFT JOIN userdomains uds USING (userdomain_id) "
                ."WHERE 1 ";

        if ($username) {
            $query .= "AND au.username like $username ";
        }

        //vorname
        if ($vorname) {
            $query .= "AND au.vorname like $vorname ";
        }

        //nachname
        if ($nachname) {
            $query .= "AND au.nachname like $nachname ";
        }

        //email
        if ($email) {
            $query .= "AND au.Email like $email ";
        }

        //permissions
        if (!is_null($perms) && $perms != "alle") {
            $query .= "AND au.perms = " . $db->quote($perms) . " ";
        }

        //locked user
        if ($locked == 1) {
            $query .= "AND au.locked = 1 ";
        }

        //inactivity
        if (!is_null($inaktiv) && $inaktiv[0] != 'nie') {
            $comp = in_array(trim($inaktiv[0]), array('=', '>', '<=')) ? $inaktiv[0] : '=';
            $days = (int)$inaktiv[1];
            $query .= "AND uo.last_lifesign {$comp} UNIX_TIMESTAMP(TIMESTAMPADD(DAY, -{$days}, NOW())) ";
        } elseif (!is_null($inaktiv)) {
            $query .= "AND uo.last_lifesign IS NULL ";
        }

        //datafields
        if (!is_null($datafields) && count($datafields) > 0) {
            foreach ($datafields as $id => $entry) {
                $query .= "AND de.datafield_id = " . $db->quote($id) . " AND de.content = " . $db->quote($entry) . " ";
            }
        }

        if ($auth_plugins) {
            $query .= "AND IFNULL(auth_plugin, 'preliminary') = " . $db->quote($auth_plugins) . " ";
        }

        if ($userdomains) {
            if ($userdomains === 'null-domain') {
                $query .= "AND userdomain_id IS NULL ";
            } else {
                $query .= "AND userdomain_id = " . $db->quote($userdomains) . " ";
            }
        }
        $query .= " GROUP BY au.user_id ";
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
                $query .= "ORDER BY uo.last_lifesign {$order}, au.username";
                break;
            case "mkdate":
                $query .= "ORDER BY ui.mkdate {$order}, au.username";
                break;
            case "auth_plugin":
                $query .= "ORDER BY auth_plugin {$order}, au.username";
                break;
            default:
                $query .= " ORDER BY au.username {$order}";
        }

        //ergebnisse zurückgeben
        return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return the institute information, depending on the selected user and
     * institute.
     *
     * @param md5 $user_id
     * @param md5 $inst_id
     *
     * @return array()
     */
    public static function getInstitute($user_id, $inst_id)
    {
        $sql = "SELECT * FROM user_inst WHERE user_id = ? AND Institut_id = ?";
        $db = DBManager::get()->prepare($sql);
        $db->execute(array($user_id, $inst_id));
        return $db->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update the institute-informations of an user.
     *
     * @param md5 $user_id
     * @param md5 $inst_id
     * @param array() $values list of changed entries
     */
    public static function setInstitute($user_id, $inst_id, $values)
    {
        //change externdefault
        if ($values['externdefault'] == 1) {
            //first set all institutes externdefault to 0
            $db = DBManager::get()->prepare("UPDATE user_inst SET externdefault = 0 WHERE user_id = ?");
            $db->execute(array($user_id));
        }

        //logging
        $old = self::getInstitute($user_id, $inst_id);
        if ($old['inst_perms'] != $values['inst_perms']) {
            log_event("INST_USER_STATUS", $inst_id, $user_id, $user_id .' -> '. $values['inst_perms']);
        }

        //change values
        foreach ($values as $index => $value) {
            $sql = "UPDATE user_inst SET " . $index . "=? WHERE user_id=? AND Institut_id=?";
            $db = DBManager::get()->prepare($sql);
            $db->execute(array($value, $user_id, $inst_id));
        }
    }

    /**
     * Check if a user exists.
     *
     * @param md5 $user_id
     * @return bool
     */
    public static function check($user_id)
    {
        $sql = "SELECT 1 FROM auth_user_md5 WHERE user_id = ?";
        $db = DBManager::get()->prepare($sql);
        $db->execute(array($user_id));
        return $db->fetchColumn();
    }

    /**
     * Merge an user ($old_id) to another user ($new_id).  This is a part of the
     * old numit-plugin.
     *
     * @param string $old_user
     * @param string $new_user
     * @param boolean $identity merge identity (if true)
     *
     * @return array() messages to display after migration
     */
    public static function convert($old_id, $new_id, $identity = false)
    {
        NotificationCenter::postNotification('UserWillMigrate', $old_id, $new_id);

        $messages = array();

        //Identitätsrelevante Daten migrieren
        if ($identity) {
            // Veranstaltungseintragungen
            self::removeDoubles('seminar_user', 'Seminar_id', $new_id, $old_id);
            $query = "UPDATE IGNORE seminar_user SET user_id = ? WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($new_id, $old_id));

            self::removeDoubles('admission_seminar_user', 'seminar_id', $new_id, $old_id);
            $query = "UPDATE IGNORE admission_seminar_user SET user_id = ? WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($new_id, $old_id));

            // Persönliche Infos
            $query = "DELETE FROM user_info WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($new_id));

            $query = "UPDATE IGNORE user_info SET user_id = ? WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($new_id, $old_id));

            // Studiengänge
            self::removeDoubles('user_studiengang', 'studiengang_id', $new_id, $old_id);
            $query = "UPDATE IGNORE user_studiengang SET user_id = ? WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($new_id, $old_id));

            // Eigene Kategorien
            $query = "UPDATE IGNORE kategorien SET range_id = ? WHERE range_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($new_id, $old_id));

            // Institute
            self::removeDoubles('user_inst', 'Institut_id', $new_id, $old_id);
            $query = "UPDATE IGNORE user_inst SET user_id = ? WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($new_id, $old_id));

            // Generische Datenfelder zusammenführen (bestehende Einträge des
            // "neuen" Nutzers werden dabei nicht überschrieben)
            $old_user = User::find($old_id);

            $query = "INSERT INTO datafields_entries
                        (datafield_id, range_id, sec_range_id, content, mkdate, chdate)
                      VALUES (:datafield_id, :range_id, :sec_range_id, :content,
                              UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
                      ON DUPLICATE KEY
                        UPDATE content = IF(content IN ('', 'default_value'), VALUES(content), content),
                               chdate = UNIX_TIMESTAMP()";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':range_id', $new_id);

            $old_user->datafields->each(function ($field) use ($new_id, $statement) {
                $statement->bindValue(':datafield_id', $field->datafield_id);
                $statement->bindValue(':sec_range_id', $field->sec_range_id);
                $statement->bindValue(':content', $field->content);
                $statement->execute();
            });

            # Datenfelder des alten Nutzers leeren
            $old_user->datafields = array();
            $old_user->store();

            //Buddys
            $query = "UPDATE IGNORE contact SET owner_id = ? WHERE owner_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($new_id, $old_id));

            // Avatar
            $old_avatar = Avatar::getAvatar($old_id);
            $new_avatar = Avatar::getAvatar($new_id);
            if ($old_avatar->is_customized()) {
                if (!$new_avatar->is_customized()) {
                    $avatar_file = $old_avatar->getFilename(AVATAR::ORIGINAL);
                    if (!file_exists($avatar_file)) {
                        $avatar_file = $old_avatar->getFilename(AVATAR::NORMAL);
                    }
                    $new_avatar->createFrom($avatar_file);
                }
                $old_avatar->reset();
            }

            $messages[] = _('Identitätsrelevante Daten wurden migriert.');
        }

        // Restliche Daten übertragen

        // ForumsModule migrieren
        foreach (PluginEngine::getPlugins('ForumModule') as $plugin) {
            $plugin->migrateUser($old_id, $new_id);
        }

        // Dateieintragungen und Ordner
        // TODO (mlunzena) should post a notification
        $query = "UPDATE IGNORE dokumente SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        $query = "UPDATE IGNORE folder SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        //Kalender
        $query = "UPDATE IGNORE calendar_events SET range_id = ? WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        $query = "UPDATE IGNORE calendar_events SET autor_id = ? WHERE autor_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        //Archiv
        self::removeDoubles('archiv_user', 'seminar_id', $new_id, $old_id);
        $query = "UPDATE IGNORE archiv_user SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        // Evaluationen
        $query = "UPDATE IGNORE eval SET author_id = ? WHERE author_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        self::removeDoubles('eval_user', 'eval_id', $new_id, $old_id);
        $query = "UPDATE IGNORE eval_user SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        $query = "UPDATE IGNORE evalanswer_user SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        // Kategorien
        $query = "UPDATE IGNORE kategorien SET range_id = ? WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        // Literatur
        $query = "UPDATE IGNORE lit_catalog SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        $query = "UPDATE IGNORE lit_list SET range_id = ? WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        // Nachrichten (Interne)
        $query = "UPDATE IGNORE message SET autor_id = ? WHERE autor_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        self::removeDoubles('message_user', 'message_id', $new_id, $old_id);
        $query = "UPDATE IGNORE message_user SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        // News
        $query = "UPDATE IGNORE news SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        $query = "UPDATE IGNORE news_range SET range_id = ? WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        // Referenztabelle: Abstimmungen, etc.
        self::removeDoubles('object_user', 'object_id', $new_id, $old_id);
        $query = "UPDATE IGNORE object_user SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        // Informationsseiten
        $query = "UPDATE IGNORE scm SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        // Statusgruppeneinträge
        self::removeDoubles('statusgruppe_user', 'statusgruppe_id', $new_id, $old_id);
        $query = "UPDATE IGNORE statusgruppe_user SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        // Termine
        $query = "UPDATE IGNORE termine SET autor_id = ? WHERE autor_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        //Votings
        $query = "UPDATE IGNORE vote SET author_id = ? WHERE author_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        $query = "UPDATE IGNORE vote SET range_id = ? WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        self::removeDoubles('vote_user', 'vote_id', $new_id, $old_id);
        $query = "UPDATE IGNORE vote_user SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        self::removeDoubles('voteanswers_user', 'answer_id', $new_id, $old_id);
        $query = "UPDATE IGNORE voteanswers_user SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        //Wiki
        $query = "UPDATE IGNORE wiki SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        $query = "UPDATE IGNORE wiki_locks SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        //Adressbucheinträge
        $query = "UPDATE IGNORE contact SET owner_id = ? WHERE owner_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        //Blubber
        $query = "UPDATE IGNORE blubber SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));
        $query = "UPDATE IGNORE blubber_follower SET studip_user_id = ? WHERE studip_user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));
        $query = "UPDATE IGNORE blubber_mentions SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));
        $query = "UPDATE IGNORE blubber_reshares SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));
        $query = "UPDATE IGNORE blubber_streams SET user_id = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));

        NotificationCenter::postNotification('UserDidMigrate', $old_id, $new_id);

        $messages[] = _('Dateien, Termine, Adressbuch, Nachrichten und weitere Daten wurden migriert.');
        return $messages;
    }

    /**
     *  Return a list of free and available institutes of an user.
     *
     * @param md5 $user_id
     * @return array() list of institutes
     */
    public static function getAvailableInstitutes($user_id)
    {
        $sql = "SELECT a.Institut_id, a.Name " .
               "FROM Institute AS a " .
                   "LEFT JOIN user_inst AS b ON (b.user_id=? AND a.Institut_id=b.Institut_id) " .
                   (!$GLOBALS['perm']->have_perm("root") ? "INNER JOIN user_inst AS p ON (p.Institut_id = a.Institut_id AND p.user_id = ? AND p.inst_perms = 'admin') " : "") .
               "WHERE b.Institut_id IS NULL ORDER BY a.Name ";
        $db = DBManager::get()->prepare($sql);
        $db->execute(array($user_id, ($GLOBALS['perm']->have_perm("root") ? null : $GLOBALS['user']->id)));
        return $db->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete double entries of the old and new user. This is a part of the old
     * numit-plugin.
     *
     * @param string $table
     * @param string $field
     * @param md5 $new_id
     * @param md5 $old_id
     */
    private static function removeDoubles($table, $field, $new_id, $old_id)
    {
        $items = array();

        $query = "SELECT a.{$field} AS field_item
                  FROM {$table} AS a, {$table} AS b
                  WHERE a.user_id = ? AND b.user_id = ? AND a.{$field} = b.{$field}";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($new_id, $old_id));
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $value) {
            array_push($items, $value['field_item']);
        }

        if (!empty($items)) {
            $query = "DELETE FROM `{$table}`
                  WHERE user_id = :user_id AND `{$field}` IN (:items)";

            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':user_id', $new_id);
            $statement->bindValue(':items', $items, StudipPDO::PARAM_ARRAY);
            $statement->execute();
        }
    }

    public static function getAvailableAuthPlugins()
    {
        $query = "SELECT DISTINCT IFNULL(auth_plugin, 'standard') as auth_plugin FROM auth_user_md5 ORDER BY auth_plugin='standard',auth_plugin";
        return DBManager::get()->query($query)->fetchAll(PDO::FETCH_COLUMN);
    }
}
