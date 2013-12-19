<?php
namespace API;

/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 *
 * @condition id ^[a-f0-9]{32}$
 * @condition box ^(inbox|outbox)$
 * @condition folder ^[0-9]+$
 */
class MessagesRoute extends RouteMap
{
    public function before($router, &$handler, &$parameters)
    {
    }


    /**
     * Liefert die vorhandenen Nachrichtenordner des autorisierten
     * Nutzers zur�ck. Der Parameter bestimmt je nach Wert, auf
     * welchen Bereich zugegriffen werden soll.
     *
     * @get /user/:id/:box
     */
    public function indexOfFolders($user_id, $box)
    {
        if ($user_id !== self::currentUser()) {
            $this->error(401);
        }

        $folders = self::getUserFolders($user_id, $box);

        $this->paginate("/user/:id/$box?offset=%u&limit=%u", count($folders));
        $folders = array_slice($folders, $this->offset, $this->limit, true);

        $folders = self::linkFolders($user_id, $box, $folders);
        return $this->collect($folders);
    }


    /**
     * Liefert die vorhandenen Nachrichten eines Ordners des
     * autorisierten Nutzers zur�ck.
     *
     * @get /user/:id/:box/:folder
     */
    public function showFolder($user_id, $box, $folder_id)
    {
        $settings = \UserConfig::get($user_id)->MESSAGING_SETTINGS ?: array();

        $type = substr($box, 0, -3);
        if ($folder_id != 0 && (
                !isset($settings['folder'][$type][$folder_id])
                || $settings['folder'][$type][$folder_id] === 'dummy'
            )) {
            $this->notFound();
        }

        // only your messages!
        if ($user_id !== self::currentUser()) {
            $this->error(401);
        }

        // get all messages in the user's folder
        $ids = self::folder($user_id, $box === 'inbox' ? 'rec' : 'snd', $folder_id);

        $this->paginate('/user/:id/:box/:folder?offset=%u&limit=%u', count($ids));
        $ids = array_slice($ids, $this->offset, $this->limit, true);

        $messages = array();
        if (sizeof($ids)) {
            foreach (\Message::findMany($ids) as $msg) {
                $messages[] = self::messageToJSON($msg);
            }
        }

        return $this->collect($messages);
    }

    /**
     * Legt einen neuen Ordner in der (In|Out)box eines Nutzers an.
     *
     * @post /user/:id/:box
     */
    public function createFolder($user_id, $box)
    {

        if (!isset($this->data['name'])
            || !strlen($name = trim($this->data['name'])) ) {
            $this->error(400, 'No suitable folder name provided');
        }

        $folders = self::getUserFolders($user_id, $box);
        if (in_array($name, $folders)) {
            $this->error(409, 'Duplicate');
        }

        $settings = \UserConfig::get($user_id)->MESSAGING_SETTINGS ?: array();
        $settings['folder'][$_box = substr($box, 0, -3)][] = $name;
        $status = \UserConfig::get($user_id)->store('MESSAGING_SETTINGS', $settings);
        page_close();

        $this->redirect(self::folderUrl($user_id, $box, sizeof($settings['folder'][$_box]) - 1), 201);
    }


    /**
     * Liefert die Daten der angegebenen Nachricht zur�ck.
     *
     * @get /message/:id
     */
    public function showMessage($message_id)
    {
        $message = $this->requireMessage($message_id);
        return self::messageToJSON($message);
    }


    /**
     * Schreibt eine neue Nachricht.
     *
     * @post /messages
     */
    public function createMessage()
    {
        if (!strlen($subject = trim($this->data['subject'] ?: ''))) {
            $this->error(400, 'No subject provided');
        }

        if (!strlen($message = trim($this->data['message'] ?: ''))) {
            $this->error(400, 'No message provided');
        }

        $recipients = (array) ($this->data['recipients'] ?: null);
        if (!sizeof($recipients)) {
            $this->error(400, 'No recipient(s) provided');
        }

        $usernames = array_map(function ($id) { $user = \User::find($id); return @$user['username']; }, $recipients);

        if (sizeof($usernames) !== sizeof(array_filter($usernames))) {
            $this->error(400, "Some recipients do not exist.");
        }

        $message = \Message::send($GLOBALS['user']->id, $usernames, $subject, $message);
        if (!$message) {
            $this->error(500, 'Could not create message');
        }

        $this->redirect('message/' . $message->id, 201, "ok");
    }


    /**
     * Eine Nachricht als (un)gelesen markieren oder in einen anderen
     * Ordner verschieben.
     *
     * @put /message/:id
     */
    public function updateMessage($message_id)
    {

        $message = $this->requireMessage($message_id);
        $user_id = $this->currentUser();

        if (isset($this->data['folders'])) {
            $this->moveMessageToFolders($message, $this->data['folders']);
        }

        if (isset($this->data['unread'])) {
            if ($this->data['unread']) {
                $message->markAsUnread($user_id);
            } else {
                $message->markAsRead($user_id);
            }
        }

        $this->halt(204);
    }


    /**
     * L�scht eine Nachricht.
     *
     * @delete /message/:id
     */
    public function destroyMessage($message_id)
    {
        $message = $this->requireMessage($message_id);

        $msgin = new \messaging();
        if (!$msgin->delete_message($message_id, self::currentUser(), true)) {
            $this->error(500);
        }

        $this->status(204);
    }


    /**************************************************/
    /* PRIVATE HELPER METHODS                         */
    /**************************************************/

    private static function currentUser()
    {
        return $GLOBALS['user']->id;
    }

    private static function folderURL($user_id, $box, $folder_id)
    {
        return sprintf('/user/%s/%s/%s', $user_id, $box, $folder_id);
    }

    private function requireMessage($message_id)
    {
        if (!$message = \Message::find($message_id)) {
            $this->notFound("Message not found");
        }

        $current_user = self::currentUser();

        $mus = $message->users->filter(
            function ($mu) use ($current_user) {
                return $mu->user_id === $current_user;
            });
        if (!sizeof($mus)) {
            $this->error(401);
        }

        $deleted = $mus->pluck("deleted");
        if (sizeof($deleted) == array_sum($deleted)) {
            $this->notFound("Message not found");
        }

        return $message;
    }


    private static function getUserFolders($user_id, $box)
    {
        $settings = \UserConfig::get($user_id)->MESSAGING_SETTINGS ?: array();
        $folders = $settings['folder'];
        $folders['in'][0]  = _('Posteingang');
        $folders['out'][0] = _('Postausgang');

        return self::filterDeleted($folders[substr($box, 0, -3)]);
    }


    private static function filterDeleted($folder_list)
    {
        $result = array();
        foreach ($folder_list as $key => $value) {
            // the first folder and every non-dummy folder are ok
            if ($key === 0 || $value !== 'dummy') {
                $result[$key] = $value;
            }
        }
        return $result;
    }


    private static function linkFolders($user_id, $box, $folders)
    {
        $result = array();

        foreach ($folders as $id => $name) {
            $url = self::folderURL($user_id, $box, $id);
            $result[$url] = $name;
        }

        return $result;
    }

    private static function messageToJSON($message)
    {
        $user_id = self::currentUser();

        $my_mu = $message->users->filter(
            function ($mu) use ($user_id) { return $mu->user_id === $user_id; });

        $my_roles = array(
            'snd' => $message->autor_id === $user_id,
            'rec' => in_array('rec', $my_mu->pluck('snd_rec')));

        $json = $message->toArray(words("message_id subject message mkdate priority"));

        // formatted message
        $json['message_html'] = formatReady($json['message']) ?: '';

        // sender
        $sender = $message->getSender();
        $json['sender'] = '/user/' . $message->autor_id;

        // recipients
        if ($my_roles['snd']) {
            $json['recipients'] = array_map(function ($r) { return '/user/' . $r->id;}, $message->getRecipients());
        } else {
            $json['recipients'] = array('/user/' . $user_id);
        }

        // attachments
        if (sizeof($message->attachments)) {
            $json['attachments'] = $message->attachments->map(function ($att) { return '/file/' . $att->id; });
        }

        // unread only if in inbox
        if ($my_roles['rec']) {
            foreach ($my_mu as $mu) {
                if ($mu->snd_rec === 'rec') {
                    $json['unread'] = !$mu->readed;
                    break;
                }
            }
        }

        // folders
        $json['folders'] = array();
        foreach ($my_mu as $mu) {
            $json['folders'][] =
                self::folderURL($user_id,
                                $mu->snd_rec === 'rec' ? 'inbox' : 'outbox',
                                $mu->folder);
        }

        return $json;
    }

    // TODO: this should be using MessageUser
    static function folder($user_id, $sndrec, $folder)
    {
        $query = "SELECT message_id
                  FROM message_user
                  WHERE user_id = ? AND snd_rec = ? AND folder = ? AND deleted = 0
                  ORDER BY mkdate DESC";
        $statement = \DBManager::get()->prepare($query);
        $statement->execute(array($user_id, $sndrec, $folder));
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function moveMessageToFolders($message, $folders)
    {
        $to_store = array();
        $user_id = self::currentUser();

        $current_folders = $message->users->findBy("user_id", $user_id);
        $existing_folders = array(
            'inbox'  => self::getUserFolders($user_id, 'inbox'),
            'outbox' => self::getUserFolders($user_id, 'outbox')
        );


        foreach ($folders as $folder) {
            if ($this->router->uriMatchesTemplate($folder, '/user/:user_id/:box/:folder_id', $params)) {

                if ($params["user_id"] !== $user_id) {
                    $this->error(401);
                }

                if (!in_array($params["box"], words("inbox outbox"))) {
                    $this->error(400);
                }

                $old = $current_folders->findBy("snd_rec", $params["box"] === "inbox" ? "rec" : "snd")->first();
                if (!$old) {
                    $this->error(409, 'Cannot move to ' . $params['box']);
                }

                if (!isset($existing_folders[$params["box"]][$params['folder_id']])) {
                    $this->error(409, 'Target folder does not exist.');
                }

                $old->folder = $params["folder_id"];
                $to_store[] = $old;
            }
        }

        array_map(function ($record) { $record->store(); }, $to_store);
    }
}
