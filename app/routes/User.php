<?php
namespace RESTAPI\Routes;

/**
 * @author  André Klaßen <andre.klassen@elan-ev.de>
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 * @condition user_id ^[0-9a-f]{32}$
 */
class User extends \RESTAPI\RouteMap
{
    /**************************************************/
    /* PUBLIC STATIC HELPER METHODS                   */
    /**************************************************/

    public static function getMiniUser($routemap, $user)
    {
        $avatar = \Avatar::getAvatar($user->id);

        return array(
            'id'              => $user->id,
            'href'            => $routemap->urlf('/user/%s', array(htmlReady($user->id))),
            'name'            => self::getNamesOfUser($user),
            'avatar_small'    => $avatar->getURL(\Avatar::SMALL),
            'avatar_medium'   => $avatar->getURL(\Avatar::MEDIUM),
            'avatar_normal'   => $avatar->getURL(\Avatar::NORMAL),
            'avatar_original' => $avatar->getURL(\Avatar::ORIGINAL)
        );
    }

    public static function getNamesOfUser($user)
    {
        $name = array(
            'formatted' => $user->getFullName(),
            'family'    => $user->nachname,
            'given'     => $user->vorname,
            'prefix'    => $user->title_front,
            'suffix'    => $user->title_rear
        );
        return $name;
    }


    /**************************************************/
    /* ROUTES                                         */
    /**************************************************/


    /**
     * getUser - retrieves data of a user
     *
     * @get /user/:user_id
     * @get /user
     */
    public function getUser($user_id = '')
    {
        $user_id = $user_id ?: $GLOBALS['user']->id;

        $user = \User::find($user_id);
        if (!$user) {
            $this->halt(404, sprintf('User %s not found', $user_id));
        }

        $visibilities = get_local_visibility_by_id($user_id, 'homepage');
        if (is_array(json_decode($visibilities, true))) {
            $visibilities = json_decode($visibilities, true);
        } else {
            $visibilities = array();
        }

        $get_field = function ($field, $visibility) use ($user_id, $user, $visibilities) {
            if (!$user[$field]
                || !is_element_visible_for_user($GLOBALS['user']->id, $user_id, $visibilities[$visibility]))
            {
                return '';
            }
            return $user[$field];
        };

        $avatar = \Avatar::getAvatar($user_id);

        $user = array(
            'user_id'         => $user_id,
            'username'        => $user['username'],
            'name'            => self::getNamesOfUser($user),
            'perms'           => $user['perms'],
            'email'           => get_visible_email($user_id),
            'avatar_small'    => $avatar->getURL(\Avatar::SMALL),
            'avatar_medium'   => $avatar->getURL(\Avatar::MEDIUM),
            'avatar_normal'   => $avatar->getURL(\Avatar::NORMAL),
            'avatar_original' => $avatar->getURL(\Avatar::ORIGINAL),
            'phone'           => $get_field('privatnr', 'private_phone'),
            'homepage'        => $get_field('Home', 'homepage'),
            'privadr'         => strip_tags($get_field('privadr', 'privadr')),
        );

        $query = "SELECT value
                  FROM user_config
                  WHERE field = ? AND user_id = ?";
        $statement = \DBManager::get()->prepare($query);
        $statement->execute(array('SKYPE_NAME', $user_id));
        $user['skype'] = $statement->fetchColumn() ?: '';
        $statement->closeCursor();

        if ($user['skype']) {
            $statement->execute(array('SKYPE_ONLINE_STATUS', $user_id));
            $user['skype_show'] = (bool)$statement->fetchColumn();
        } else {
            $user['skype_show'] = false;
        }

        // Data fields
        $datafields = array();
        foreach (\DataFieldEntry::getDataFieldEntries($user_id, 'user') as $entry) {
            if (!$entry->structure->accessAllowed($GLOBALS['perm'], $GLOBALS['user']->id, $user_id)) {
                continue;
            }
            if (!\Visibility::verify($entry->structure->getID(), $user_id)) {
                continue;
            }
            $datafields[] = array(
                'type'  => $entry->getType(),
                'id'    => $entry->getId(),
                'name'  => $entry->getName(),
                'value' => $entry->getValue(),
            );
        }
        $user['datafields'] = $datafields;

        $this->etag(md5(serialize($user)));

        return $user;

    }


    /**
     * deleteUser - deletes a user
     *
     * @delete /user/:user_id
     */
    public function deleteUser($user_id)
    {
        if (!$GLOBALS['perm']->have_perm('root')) {
            $this->error(401);
        }

        if (!$GLOBALS['user']->id === $user_id) {
            $this->error(400, 'Must not delete yourself');
        }

        $user = \User::find($user_id);
        $user->delete();

        $this->status(204);
    }


    /**
     * returns institutes for a given user
     *
     * @get /user/:user_id/institutes
     */
    public function getInstitutes($user_id)
    {

        $query = "SELECT i0.Institut_id AS institute_id, i0.Name AS name,
                         inst_perms AS perms, sprechzeiten AS consultation,
                         raum AS room, ui.telefon AS phone, ui.fax,
                         i0.Strasse AS street, i0.Plz AS city,
                         i1.Name AS faculty_name, i1.Strasse AS faculty_street,
                         i1.Plz AS faculty_city
                  FROM user_inst AS ui
                  JOIN Institute AS i0 USING (Institut_id)
                  LEFT JOIN Institute AS i1 ON (i0.fakultaets_id = i1.Institut_id)
                  WHERE visible = 1 AND user_id = :user_id
                  ORDER BY priority ASC";
        $statement = \DBManager::get()->prepare($query);
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();

        $institutes = array(
            'work'  => array(),
            'study' => array(),
        );

        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            if ($row['perms'] === 'user') {
                $institutes['study'][] = $row;
            } else {
                $institutes['work'][] = $row;
            }
        }

        $this->etag(md5(serialize($institutes)));

        $result = array_slice($institutes, $this->offset, $this->limit);
        return $this->paginated($result, count($institutes), compact('user_id'));
    }
}
