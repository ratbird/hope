<?php
# Lifter002: DONE - not applicable
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: DONE - not applicable

// erstellt von Alex

class DbPermissions
{
    function search_range($search_str)
    {
        global $perm, $auth, $_fullname_sql, $user;

        /* If user is root --------------------------------------------------- */
        if ($perm->have_perm("root")) {
            $query = "SELECT a.user_id, {$_fullname_sql['full']} AS full_name, username
                      FROM auth_user_md5 AS a
                      LEFT JOIN user_info USING (user_id)
                      WHERE CONCAT(Vorname, ' ', Nachname, ' ', username) LIKE CONCAT('%', ?, '%')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($search_str));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->search_result[$row['username']] = array(
                    'type' => 'user',
                    'name' => $row['full_name'] . ' (' . $row['username'] . ')'
                );
            }

            $query = "SELECT Seminar_id, Name
                      FROM seminare
                      WHERE Name LIKE CONCAT('%', ?, '%')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($search_str));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->search_result[$row['Seminar_id']] = array(
                    'type' => 'sem',
                    'name' => $row['Name']
                );
            }

            $query = "SELECT Institut_id, Name,
                             IF (Institut_id = fakultaets_id,'fak','inst') AS inst_type
                      FROM Institute
                      WHERE Name LIKE CONCAT('%', ?, '%')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($search_str));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->search_result[$row['Institut_id']] = array(
                    'type' => $row['inst_type'],
                    'name' => $row['Name']
                );
            }
        }
        /* ------------------------------------------------------------------- */

        /* If user is an admin ----------------------------------------------- */
        else if ($perm->have_perm('admin')) {
            $query = "SELECT b.Seminar_id, b.Name
                      FROM user_inst AS a
                      LEFT JOIN seminare AS b USING (Institut_id)
                      WHERE a.user_id = ? AND a.inst_perms = 'admin'
                        AND b.Name LIKE CONCAT('%', ?, '%')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id, $search_str));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->search_result[$row['Seminar_id']] = array(
                    'type' => 'sem',
                    'name' => $row['Name']
                );
            }

            $query = "SELECT b.Institut_id, b.Name
                      FROM user_inst AS a
                      LEFT JOIN Institute AS b USING (Institut_id)
                      WHERE a.user_id = ? AND a.inst_perms = 'admin'
                        AND a.institut_id != b.fakultaets_id
                        AND b.Name LIKE CONCAT('%', ?, '%')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id, $search_str));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->search_result[$row['Institut_id']] = array(
                    'type' => 'inst',
                    'name' => $row['Name']
                );
            }

            if ($perm->is_fak_admin()) {
                $query = "SELECT d.Seminar_id, d.Name
                          FROM user_inst AS a
                          LEFT JOIN Institute AS b ON (a.Institut_id = b.Institut_id AND b.Institut_id = b.fakultaets_id)
                          LEFT JOIN Institute AS c ON (c.fakultaets_id = b.institut_id AND c.fakultaets_id != c.institut_id)
                          LEFT JOIN seminare AS d USING (Institut_id)
                          WHERE a.user_id = ? AND a.inst_perms = 'admin'
                            AND NOT ISNULL(b.Institut_id)
                            AND d.Name LIKE CONCAT('%', ?, '%')";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($user->id, $search_str));
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $this->search_result[$row['Seminar_id']] = array(
                        'type' => 'sem',
                        'name' => $row['Name']
                    );
                }

                $query = "SELECT c.Institut_id, c.Name
                          FROM user_inst AS a
                          LEFT JOIN Institute AS b ON (a.Institut_id = b.Institut_id AND b.Institut_id = b.fakultaets_id)
                          LEFT JOIN Institute AS c ON (c.fakultaets_id = b.institut_id AND c.fakultaets_id != c.institut_id)
                          WHERE a.user_id = ? AND a.inst_perms = 'admin'
                            AND NOT ISNULL(b.Institut_id)
                            AND c.Name LIKE CONCAT('%', ?, '%')";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($user->id, $search_str));
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $this->search_result[$row['Institut_id']] = array(
                        'type' => 'inst',
                        'name' => $row['Name']
                    );
                }

                $query = "SELECT b.Institut_id, b.Name
                          FROM user_inst AS a
                          LEFT JOIN Institute AS b ON (a.Institut_id = b.Institut_id AND b.Institut_id = b.fakultaets_id)
                          WHERE a.user_id = ? AND a.inst_perms = 'admin'
                            AND NOT ISNULL(b.Institut_id)
                            AND b.Name LIKE CONCAT('%', ?, '%')";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($user->id, $search_str));
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $this->search_result[$row['Institut_id']] = array(
                        'type' => 'fak',
                        'name' => $row['Name']
                    );
                }
            }
        }

        /* is tutor ------------------------------- */
        elseif ($perm->have_perm('tutor')) {
            $query = "SELECT b.Seminar_id, b.Name
                      FROM seminar_user AS a
                      LEFT JOIN seminare AS b USING (Seminar_id)
                      WHERE a.user_id = ? AND a.status IN ('dozent', 'tutor')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->search_result[$row['Seminar_id']] = array(
                    'type' => 'sem',
                    'name' => $row['Name']
                );
            }

            $query = "SELECT b.Institut_id, b.Name
                      FROM user_inst AS a
                      LEFT JOIN Institute AS b USING (Institut_id)
                      WHERE a.user_id = ? AND a.inst_perms IN ('dozent', 'tutor')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->search_result[$row['Institut_id']] = array(
                    'type' => 'inst',
                    'name' => $row['Name']
                );
            }
        }
        /* --------------------------------------- */

        return $this->search_result;
    }
}
