<?php

/**
 * StatusgruppeUser.php
 * model class for statusgroupusers.
 *
 * This model should be joined to an user object if nessecary
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 */
class StatusgruppeUser extends SimpleORMap {

    protected $db_table = "statusgruppe_user";
    protected $belongs_to = array(
        'group' => array(
            'class_name' => 'Statusgruppen',
            'foreign_key' => 'statusgruppe_id'
        ),
        'user' => array(
            'class_name' => 'User',
            'foreign_key' => 'user_id')
    );

    /**
     * Prevents invisible users from being displayed
     *
     * @return string Fullname if visible else string for invisible user
     */
    public function name() {
        return $this->user->nachname . ', '. $this->user->vorname;
    }

    /**
     * Prevents the avatar of invisible users from being displayed
     *
     * @return mixed Useravatar if visible else dummyavatar
     */
    public function avatar() {
        return Avatar::getAvatar($this->user_id, $this->user->username)->getImageTag(Avatar::SMALL, array('title' => htmlReady($this->name())));
    }

    /**
     * {@inheritdoc }
     */
    public function store() {
        if ($this->isNew()) {
            $sql = "SELECT MAX(position)+1 FROM statusgruppe_user WHERE statusgruppe_id = ?";
            $stmt = DBManager::get()->prepare($sql);
            $stmt->execute(array($this->statusgruppe_id));
            $this->position = $stmt->fetchColumn();
        }
        parent::store();
    }

    /**
     * {@inheritdoc }
     */
    public function delete() {

        // Resort members
        $query = "UPDATE statusgruppe_user SET position = position - 1 WHERE statusgruppe_id = ? AND position > ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->statusgruppe_id, $this->position));
        parent::delete();
    }

}

?>
