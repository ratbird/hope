<?php
namespace RESTAPI\Routes;

/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 *
 * @condition user_id ^[a-f0-9]{32}$
 * @condition friend_id ^[a-f0-9]{32}$
 * @condition group_id ^[a-f0-9]{32}$
 */
class Contacts extends \RESTAPI\RouteMap
{

    public static function before()
    {
        require_once 'lib/contact.inc.php';
        require_once 'lib/statusgruppe.inc.php';
    }

    /**
     * Lists all contacts of a user
     *
     * @get /user/:user_id/contacts
     */
    public function getUserContacts($user_id)
    {
        if ($GLOBALS['user']->id !== $user_id) {
            $this->error(401);
        }

        // quite degenerated as long as we can only see our own contacts
        $user = $this->requireUser($user_id);

        $total = count($user->contacts);
        $contacts = $user->contacts->limit($this->offset, $this->limit);

        return $this->paginated($this->contactsToJSON($contacts),
                                $total, compact('user_id'));
    }

    /**
     * Adds/Updates a contact to user's list of contacts
     *
     * @put /user/:user_id/contacts/:friend_id
     */
    public function addUserContact($user_id, $buddy_user_id)
    {
        if ($GLOBALS['user']->id !== $user_id) {
            $this->error(401);
        }

        $user = $this->requireUser($user_id);
        $friend = $this->requireUser($buddy_user_id);

        // prevent duplicates
        if (sizeof($user->contacts->findOneBy('user_id', $friend->id))) {
            $this->error(409, sprintf('User "%s" is already a contact', htmlReady($friend->id)));
        }

        // TODO: only adds contacts to the global $user
        AddNewContact($friend->id);

        // TODO: add/update the buddy to the contacts
        // TODO: what does the last TODO mean after all?

        $this->status(201);
    }

    /**
     * Deletes a contact
     *
     * @delete /user/:user_id/contacts/:friend_id
     */
    public function removeUserContact($user_id, $buddy_user_id)
    {
        if ($GLOBALS['user']->id !== $user_id) {
            $this->error(401);
        }

        $user = $this->requireUser($user_id);
        $friend = $this->requireUser($buddy_user_id);

        if (!sizeof($contact = $user->contacts->findOneBy('user_id', $friend->id))) {
            $this->notFound("Contact not found");
        }

        DeleteContact($contact->id);

        // TODO: remove the buddy from the contacts
        // TODO: what does the last TODO mean after all?

        $this->status(204);
    }


    /**
     * List all contact groups of a user
     *
     * @get /user/:user_id/contact_groups
     */
    public function getUserContactGroups($user_id)
    {
        if ($GLOBALS['user']->id !== $user_id) {
            $this->error(401);
        }

        $contact_groups = \SimpleCollection::createFromArray(
                \Statusgruppen::findByRange_id($GLOBALS['user']->id))
            ->orderBy('name ASC');

        $total = count($contact_groups);
        $contact_groups = $contact_groups->limit($this->offset, $this->limit);
        return $this->paginated($this->contactGroupsToJSON($contact_groups),
                                $total, compact('user_id'));
    }

    /**
     * Create a new contact group for a user.
     *
     * @post /user/:user_id/contact_groups
     */
    public function createContactGroup($user_id)
    {
        if ($GLOBALS['user']->id !== $user_id) {
            $this->error(401);
        }

        // TODO: add the new contact group

        if (!$success) {
            $this->error(500);
        }

        $this->redirect('contact_group/' . $contact_group->id, 201, 'ok');
    }

    /**
     * Show a single contact group
     *
     * @get /contact_group/:group_id
     */
    public function showContactGroup($group_id)
    {
        $group = $this->requireContactGroup($group_id);
        return $this->contactGroupToJSON($group);
    }

    /**
     * Remove a contact group
     *
     * @delete /contact_group/:group_id
     */
    public function destroyContactGroup($group_id)
    {
        // TODO: get contact_group, using #notFound if required

        // TODO: auth

        // TODO: destroy contact group

        if (!$success) {
            $this->error(500);
        }

        $this->status(204);
    }

    /**
     * List all members of a contact group
     *
     * @get /contact_group/:group_id/members
     */
    public function indexOfContactGroupMembers($group_id)
    {
        $group = $this->requireContactGroup($group_id);
        $contacts = $group->members->limit($this->offset, $this->limit);

        $json = array();
        foreach ($contacts as $contact) {
            $json[] = $this->minimalUserToJSON($contact->user_id, $contact->name());
        }

        return $this->paginated($json, count($group->members), compact('group_id'));
    }

    /**
     * Add a user to a contact group
     *
     * @put /contact_group/:group_id/members/:user_id
     */
    public function addToContactGroup($group_id, $user_id)
    {
        $group = $this->requireContactGroup($group_id);
        $user = $this->requireUser($user_id);

        // prevent duplicates
        $exists = $group->members->findBy('user_id', $user_id)->first();
        if ($exists) {
            $this->error(409, 'Duplicate');
        }

        $success = InsertPersonStatusgruppe($user_id, $group_id);

        if (!$success) {
            $this->error(500);
        }

        $this->status(201);
    }

    /**
     * Remove a user from a contact group
     *
     * @delete /contact_group/:group_id/members/:user_id
     */
    public function removeFromContactGroup($group_id, $user_id)
    {
        $group = $this->requireContactGroup($group_id);
        $membership = $group->members->findBy('user_id', $user_id)->first();
        if (!$membership) {
            $this->notFound();
        }

        $membership->delete();

        $this->status(204);
    }


    /**************************************************/
    /* PRIVATE HELPER METHODS                         */
    /**************************************************/

    private function requireUser($user_id)
    {
        $user = \User::find($user_id);
        // TODO: checks visibility using the global perm object!
        if (!$user || !get_visibility_by_id($user_id)) {
            $this->notFound(sprintf("Could not find user with id: %s", htmlReady($user_id)));
        }

        return $user;
    }

    private function requireContactGroup($group_id)
    {
        $group = \Statusgruppen::find($group_id);
        if (!$group) {
            $this->notFound();
        }

        if ($group->range_id !== $GLOBALS['user']->id) {
            $this->error(401);
        }
        return $group;
    }

    private function contactsToJSON($contacts) {
        $result = array();
        foreach ($contacts as $contact) {
            $url = sprintf('/contact/%s', htmlReady($contact->id));
            $result[$url] = array(
                'id'            => $contact->id,
                'owner'         => sprintf('/user/%s', htmlReady($contact->owner_id)),
                'friend'        => $this->minimalUserToJSON($contact->user_id, array($contact->friend->getFullName())),
                'buddy'         => (bool) $contact->buddy,
                'calpermission' => (bool) $contact->calpermission
            );
        }
        return $result;
    }

    private function minimalUserToJSON($id, $fullname)
    {
        $avatar = \Avatar::getAvatar($id);
        return array('user_id'       => $id,
                     'url'           => sprintf('/user/%s', htmlReady($id)),
                     'fullname'      => $fullname,
                     'avatar_small'  => $avatar->getURL(\Avatar::SMALL),
                     'avatar_medium' => $avatar->getURL(\Avatar::MEDIUM),
                     'avatar_normal' => $avatar->getURL(\Avatar::NORMAL)
        );
    }

    private function contactGroupsToJSON($contact_groups)
    {
        $result = array();
        foreach ($contact_groups as $cg) {
            $url = sprintf('/contact_group/%s', htmlReady($cg->id));
            $result[$url] = $this->contactGroupToJSON($cg);
        }
        return $result;
    }

    private function contactGroupToJSON($group)
    {
        $json = array(
            'id' => $group->id,
            'name' => $group->name,
            'contacts' => sprintf('/contact_group/%s/members', htmlReady($group->id)),
            'contacts_count' => sizeof($group->members)
        );
        return $json;
    }


    /*

    private function contactGroupExists($group_id)
    {
        $query = "SELECT 1 FROM statusgruppen WHERE statusgruppe_id = ?";
        $statement = \DBManager::get()->prepare($query);
        $statement->execute(array($group_id));
        return $statement->fetchColumn();
    }


    static function load($user_id)
    {
        $query = "SELECT statusgruppe_id AS group_id, name FROM statusgruppen WHERE range_id = ? ORDER BY position ASC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $groups = $statement->fetchAll(PDO::FETCH_ASSOC);

        $groups['unassigned'] = self::loadGroup('unassigned');
        return $groups;
    }

    static function loadGroup($group_id)
    {
        if ($group_id === 'unassigned') {
            return array(
                'group_id' => 'unassigned',
                'name'     => _('Nicht zugeordnet'),
            );
        }
        $query = "SELECT statusgruppe_id AS group_id, name FROM statusgruppen WHERE statusgruppe_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($group_id));
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    static function loadUnassigned($user_id)
    {
        $query = "SELECT user_id
                  FROM contact
                  WHERE owner_id = :user_id AND user_id NOT IN(
                      SELECT user_id
                      FROM statusgruppen
                      JOIN statusgruppe_user USING (statusgruppe_id)
                      WHERE range_id = :user_id
                  )";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    static function loadMembers($user_id, $group_id)
    {
        if ($group_id === 'unassigned') {
            return self::loadUnassigned($user_id);
        }
        $query = "SELECT user_id
                  FROM statusgruppen
                  JOIN statusgruppe_user USING (statusgruppe_id)
                  WHERE range_id = ? AND statusgruppe_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id, $group_id));
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }
    */
}
