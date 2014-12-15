<?php

/**
 * Contact.class.php - model class for table contact
 *
 * @author      <mlunzena@uos.de>
 * @license GPL 2 or later
 * @property string contact_id database column
 * @property string id alias column for contact_id
 * @property string owner_id database column
 * @property string user_id database column
 * @property string buddy database column
 * @property string calpermission database column
 * @property SimpleORMapCollection infos has_many ContactUserinfo
 * @property User owner belongs_to User
 * @property User friend belongs_to User
 */
class Contact extends SimpleORMap {

    protected static function configure($config = array()) {

        $config['db_table'] = 'contact';
        $config['belongs_to']['owner'] = array(
            'class_name' => 'User',
            'foreign_key' => 'owner_id'
        );
        $config['belongs_to']['friend'] = array(
            'class_name' => 'User',
            'foreign_key' => 'user_id'
        );

        $config['has_many']['infos'] = array(
            'class_name' => 'ContactUserinfo',
            'assoc_foreign_key' => 'contact_id'
        );

        parent::configure($config);
    }

    /* public function findByOwner_id($id, $order = 'ORDER BY contact_id ASC')
      {
      return self::findBySQL('contact.owner_id = ? ' . $order, array($id));
      } */

    /**
     * Adds a contact
     * 
     * @param String $username The username that should be added to the
     * current users contacts
     * 
     * @param String $group id of the statusgroup you want to add the contact
     * 
     * @throws MethodNotAllowedException Throws an exception if the selected
     * group doesnt belong to the user
     */
    public static function add($username, $group = null) {

        // Parse user by username
        $contact = User::findByUsername($username);

        // Create contact if not exist
        Contact::import(array(
            'owner_id' => User::findCurrent()->id,
            'user_id' => $contact->id)
        );

        // Also add to a group if requested 
        if ($group) {
            $group = new Statusgruppen($group);

            // Security check if it is really the group of the current user
            if ($group->range_id != User::findCurrent()->id) {
                throw new MethodNotAllowedException;
            }

            $group->addUser($contact->id);
        }
    }

    /**
     * Removes a contact from a contactgroup or from everything
     * 
     * @param String $username the username of the contact
     * 
     * @param String $group The id of the group if the contact should only be
     * removed of one group
     * 
     * @throws MethodNotAllowedException Throws an exception if the selected
     * group doesnt belong to the user
     */
    public static function remove($username, $group = null) {

        // Parse user by username
        $contact = User::findByUsername($username);

        // if we got a group just remove from that
        if ($group) {
            $group = new Statusgruppen($group);

            // Security check if it is really the group of the current user
            if ($group->range_id != User::findCurrent()->id) {
                throw new MethodNotAllowedException;
            }
            $group->removeUser($contact->id);
        } else {

            // Otherwise remove whole contact
            self::deleteBySQL("owner_id = ? AND user_id = ?", array(
                User::findCurrent()->id,
                $contact->id
            ));

            // And remove him from every group
            $stmt = DBManager::get()->prepare('DELETE statusgruppe_user FROM statusgruppen JOIN statusgruppe_user USING (statusgruppe_id) WHERE range_id = ? AND user_id = ?');
            $stmt->execute(array(User::findCurrent()->id, $contact->id));
        }
    }

}
