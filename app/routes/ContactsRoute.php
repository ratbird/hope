<?php
namespace RESTAPI;

/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 *
 * @condition user_id ^[a-f0-9]{32}$
 * @condition friend_id ^[a-f0-9]{32}$
 * @condition group_id ^[a-f0-9]{32}$
 */
class ContactsRoute extends RouteMap
{

    public static function before()
    {
        require_once 'lib/contact.inc.php';
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

        $contact_groups = $this->findContactGroupsByUserId($user_id);

        $total = count($contact_groups);
        $contact_groups = array_slice($contact_groups, $this->offset, $this->limit, true);
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
        // TODO: get contact_group, using #notFound if required

        // TODO: auth

        return $this->contactGroupToJSON($contact_group);
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
     * Add a user to a contact group
     *
     * @put /contact_group/:group_id/members/:user_id
     */
    public function addToContactGroup($group_id, $user_id)
    {
        // TODO: get contact_group, using #notFound if required

        // TODO: auth

        // TODO: get user, using #notFound if required

        // TODO: is the other user visible to us?

        // TODO: add the user to the group

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
        // TODO: get contact_group, using #notFound if required

        // TODO: auth

        // TODO: find user in group, using #notFound if required

        // TODO: remove the user from the group

        if (!$success) {
            $this->error(500);
        }

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
#            $this->notFound(sprintf("Could not find user with id: %s", htmlReady($user_id)));
        }

        return $user;
    }

    private function contactsToJSON($contacts) {
        $result = array();
        foreach ($contacts as $contact) {
            $url = sprintf('/contact/%s', htmlReady($contact->id));
            $avatar = \Avatar::getAvatar($contact->user_id);
            $result[$url] = array(
                'id'            => $contact->id,
                'owner'         => sprintf('/user/%s', htmlReady($contact->owner_id)),
                'friend'        => array(
                                        'user_id'       => $contact->user_id,
                                        'url'           => sprintf('/user/%s', htmlReady($contact->user_id)),
                                        'fullname'      => $contact->friend->getFullName(),
                                        'avatar_small'  => $avatar->getURL(\Avatar::SMALL),
                                        'avatar_medium' => $avatar->getURL(\Avatar::MEDIUM),
                                        'avatar_normal' => $avatar->getURL(\Avatar::NORMAL)
                                    ),
                'buddy'         => (bool) $contact->buddy,
                'calpermission' => (bool) $contact->calpermission
            );
        }
        return $result;
    }

}
