<?php
namespace API;

/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 *
 * @condition user_id ^[a-f0-9]{32}$
 * @condition group_id ^[a-f0-9]{32}$
 */
class ContactsRoute extends RouteMap
{
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

        $contacts = $this->findContactsByUserId($user_id);

        $this->paginate("/user/:id/contacts?offset=%u&limit=%u", count($contacts));
        $contacts = array_slice($contacts, $this->offset, $this->limit, true);

        return $this->collect($this->contactsToJSON($contacts));
    }

    /**
     * Adds/Updates a contact to user's list of contacts
     *
     * @put /user/:user_id/contacts/:user_id
     */
    public function addUserContact($my_user_id, $buddy_user_id)
    {
        if ($GLOBALS['user']->id !== $user_id) {
            $this->error(401);
        }

        // TODO: add/update the buddy to the contacts

        if (!$success) {
            $this->error(500);
        }

        $this->halt(201);
    }

    /**
     * Deletes a contact
     *
     * @delete /user/:user_id/contacts/:user_id
     */
    public function removeUserContact($my_user_id, $buddy_user_id)
    {
        if ($GLOBALS['user']->id !== $user_id) {
            $this->error(401);
        }

        // TODO: get the contact, using #notFound if required

        // TODO: remove the buddy from the contacts

        if (!$success) {
            $this->error(500);
        }

        $this->halt(201);
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

        $this->paginate("/user/:id/contact_groups?offset=%u&limit=%u", count($contact_groups));
        $contact_groups = array_slice($contact_groups, $this->offset, $this->limit, true);

        return $this->collect($this->contactGroupsToJSON($contact_groups));
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
}
