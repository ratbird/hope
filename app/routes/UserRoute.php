<?php
/**
 * @author  André Klaßen <andre.klassen@elan-ev.de>
 * @license GPL 2 or later
 */

namespace API;
use DBManager, PDO, StudipPDO, User, Avatar;

class UserRoute extends RouteMap
{

    /**
     * getUser - retrieves data of a user
     *
     * @get /user/:uid
     * @get /user
     *
     * @return Collection
     */
    public function getUser($user_id = '')
    {
        $user_id = $user_id ?: $GLOBALS['user']->id;

        $user = User::find($user_id);
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

        $avatar = function ($size) use ($user_id, $visibilities) {
            static $avatar;
            if (!$avatar) {
                $avatar_id = is_element_visible_for_user($GLOBALS['user']->id, $user_id, $visibilities['picture'])
                           ? $user_id : 'nobody';
                $avatar = Avatar::getAvatar($avatar_id);
            }
            return $avatar->getURL($size);
        };

        $user = array(
            'user_id'       => $user_id,
            'username'      => $user['username'],
            'perms'         => $user['perms'],
            'title_pre'     => $user['title_front'],
            'forename'      => $user['Vorname'],
            'lastname'      => $user['Nachname'],
            'title_post'    => $user['title_rear'],
            'email'         => get_visible_email($user_id),
            'avatar_small'  => $avatar(Avatar::SMALL),
            'avatar_medium' => $avatar(Avatar::MEDIUM),
            'avatar_normal' => $avatar(Avatar::NORMAL),
            'phone'         => $get_field('privatnr', 'private_phone'),
            'homepage'      => $get_field('Home', 'homepage'),
            'privadr'       => strip_tags($get_field('privadr', 'privadr')),
        );

        $query = "SELECT value
                  FROM user_config
                  WHERE field = ? AND user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array('SKYPE_NAME', $user_id));
        $user['skype'] = $statement->fetchColumn() ?: '';
        $statement->closeCursor();

        if ($user['skype']) {
            $statement->execute(array('SKYPE_ONLINE_STATUS', $user_id));
            $user['skype_show'] = (bool)$statement->fetchColumn();
        } else {
            $user['skype_show'] = false;
        }

        return $user;

    }


    /**
     * deleteUser - deletes a user
     *
     * @delete /user/:uid
     */
    public function deleteUser()
    {

        
        if (false) {
            $this->halt(401);
        }

        $this->status(204);
    }



    /**
     * returns courses for a given user
     *
     * @get /user/:id/courses
     * @return Collection
     */
    public function getCourses($user_id)
    {
        $query = "SELECT Seminar_id AS course_id, su.status AS perms,
                         Veranstaltungsnummer AS event_number,
                         s.Name AS name, Untertitel AS subtitle,
                         sd.semester_id, sd.name AS semester_name
                  FROM seminar_user AS su
                  JOIN seminare AS s USING (Seminar_id)
                  LEFT JOIN semester_data AS sd ON (s.start_time BETWEEN sd.beginn AND sd.ende)
                  WHERE user_id = :user_id AND su.visible != 'no'
                  ORDER BY s.start_time DESC";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();

        $courses = array();
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            // TODO: work / study seperation has to be refined    
            //$index = $row['perms'] === 'user'
            //       ? 'study'
            //       : 'work';
            $courses[] = $row;
        }


        $this->paginate('/user/' . $user_id . '/courses?offset=%u&limit=%u', count($courses));

        $result = array_slice($courses, $this->offset, $this->limit);
        
        return $this->collect($result);
    }
    
    
    
    /**
     * returns institutes for a given user
     *
     * @get /user/:id/institutes
     * @return Collection
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
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();

        $institutes = array(
            'work'  => array(),
            'study' => array(),
        );
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
          // TODO: work / study seperation has to be refined    
          //  $index = $row['inst_perms'] === 'user'
          //          ? 'study'
          //           : 'work';
         $institutes[] = $row;
        }
        
        $this->paginate('/user/' . $user_id . '/institutes?offset=%u&limit=%u', count($institutes));

        $result = array_slice($institutes, $this->offset, $this->limit);
        
        return $this->collect($result);

    } 
}
