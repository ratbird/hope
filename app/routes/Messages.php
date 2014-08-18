<?php
namespace RESTAPI\Routes;

/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 *
 * @condition message_id ^[a-f0-9]{32}$
 * @condition user_id ^[a-f0-9]{32}$
 * @condition box ^(inbox|outbox)$
 * @condition folder_id ^[0-9]+$
 */
class Messages extends \RESTAPI\RouteMap
{
    /**
     * Liefert die vorhandenen Nachrichtenordner des autorisierten
     * Nutzers zurück. Der Parameter bestimmt je nach Wert, auf
     * welchen Bereich zugegriffen werden soll.
     * Die Rückgabe beinhaltet pro Ordner den Namen, die Anzahl
     * aller Nachrichten sowie die Anzahl der ungelesenen Nachrichten.
     *
     * @get /user/:user_id/:box
     */
    public function indexOfFolders($user_id, $box)
    {
        if ($user_id !== self::currentUser()) {
            $this->error(401);
        }

        $folders = self::getUserFolders($user_id, $box);
        $total = count($folders);
        $folders = $this->getFoldersMetaData($folders, $user_id, $box);
        $folders = $this->linkFolders($user_id, $box,
                                      array_slice($folders, $this->offset, $this->limit, true));

        return $this->paginated($folders, $total, compact('user_id', 'box'));
    }


    /**
     * Liefert die vorhandenen Nachrichten eines Ordners des
     * autorisierten Nutzers zurück.
     *
     * @get /user/:user_id/:box/:folder_id
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
        $total = count($ids);
        $ids = array_slice($ids, $this->offset, $this->limit, true);

        $messages = array();
        if (sizeof($ids)) {
            foreach (\Message::findMany($ids) as $msg) {
                $url = $this->urlf('/message/%s', array($msg->id));
                $messages[$url] = $this->messageToJSON($msg);
            }
        }
        $this->etag(md5(serialize($messages)));
        return $this->paginated($messages, $total, compact('user_id', 'box', 'folder_id'));
    }

    /**
     * Liefert die Daten der angegebenen Nachricht zurück.
     *
     * @get /message/:message_id
     */
    public function showMessage($message_id)
    {
        $message = $this->requireMessage($message_id);
        $message_json = $this->messageToJSON($message);
        $this->etag(md5(serialize($message_json)));
        return $message_json;
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
     * Ordner verschieben (Ordner werden dabei als Array und vollständig
     * angegeben, [/user/:user_id/:box/:folder]).
     *
     * @put /message/:message_id
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
     * Löscht eine Nachricht.
     *
     * @delete /message/:message_id
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

    private function folderURL($user_id, $box, $folder_id)
    {
        return $this->urlf('/user/%s/%s/%s', array($user_id, $box, $folder_id));
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
        $folders = array();
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


    private function linkFolders($user_id, $box, $folders)
    {
        $result = array();

        foreach ($folders as $id => $content) {
            $url = $this->folderURL($user_id, $box, $id);
            $result[$url] = $content;
        }

        return $result;
    }
    
    private function getFoldersMetaData($folders, $user_id, $box)
    {
        foreach ($folders as $id => $name) {
            $folders[$id] = array(
                'name'   => $name,
                'total'  => count(self::folder($user_id, $box === 'inbox' ? 'rec' : 'snd', $id)),
                'unread' => count(self::folder($user_id, $box === 'inbox' ? 'rec' : 'snd', $id, true)),
            );
        }
        
        return $folders;
    }

    private function messageToJSON($message)
    {
        $user_id = self::currentUser();

        $my_mu = $message->receivers->filter(
            function ($mu) use ($user_id) { return $mu->user_id === $user_id; });

        $my_roles = array(
            'snd' => $message->autor_id === $user_id,
            'rec' => in_array('rec', $my_mu->pluck('snd_rec')));

        $json = $message->toArray(words("message_id subject message mkdate priority"));

        // formatted message
        $json['message_html'] = formatReady($json['message']) ?: '';

        // sender
        $sender = $message->getSender();
        $json['sender'] = $this->urlf('/user/%s', array($message->autor_id));

        // recipients
        if ($my_roles['snd']) {
            $json['recipients'] = array();
            foreach ($message->getRecipients() as $r) {
                $json['recipients'][] = $this->urlf('/user/%s', array($r->id));
            }
        } else {
            $json['recipients'] = array($this->urlf('/user/%s', array($user_id)));
        }

        // attachments
        if (sizeof($message->attachments)) {
            $json['attachments'] = array();
            foreach ($message->attachments as $att) {
                $json['attachments'][] = $this->urlf('/file/%s', array($att->id));
            }
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
                $this->folderURL($user_id,
                                 $mu->snd_rec === 'rec' ? 'inbox' : 'outbox',
                                 0);
        }
        return $json;
    }

    private static function folder($user_id, $sndrec, $folder, $unread = null)
    {
        $temp = \MessageUser::findBySQL('user_id = ? AND snd_rec = ? AND deleted = 0',
                                        array($user_id, $sndrec));
        $messages = \SimpleORMapCollection::createFromArray($temp);
        if ($unread !== null) {
            $messages = $messages->filter(function ($message) use ($unread) {
                return $message->readed == ($unread ? 0 : 1);
            });
        }
        return $messages->pluck('message_id');
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


        $uri_tmpl = new \RESTAPI\UriTemplate('/user/:user_id/:box/:folder_id');
        foreach ($folders as $folder) {
            if ($uri_tmpl->match($folder, $params)) {

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
