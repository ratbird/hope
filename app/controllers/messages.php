<?php
/**
 * message.php - Message controller
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/sms_functions.inc.php';
require_once 'lib/visual.inc.php';
require_once 'lib/statusgruppe.inc.php';
require_once 'app/controllers/authenticated_controller.php';

class MessagesController extends AuthenticatedController {

    protected $number_of_displayed_messages = 50;

    public function overview_action()
    {
        PageLayout::setTitle(_("Nachrichten"));
        PageLayout::setHelpKeyword("Basis.InteraktionNachrichten");
        Navigation::activateItem('/messaging/messages/inbox');

        if (Request::isPost() && Request::get("delete_message")) {
            $messaging = new messaging();
            $success = $messaging->delete_message(Request::option("delete_message"));
            if ($success) {
                PageLayout::postMessage(MessageBox::success(_("Nachricht gelöscht!")));
            } else {
                PageLayout::postMessage(MessageBox::error(_("Nachricht konnte nicht gelöscht werden.")));
            }
        }

        $this->messages = $this->get_messages(
            true,
            Request::int("limit", $this->number_of_displayed_messages),
            Request::int("offset", 0),
            Request::get("tag"),
            Request::get("search")
        );
        $this->received = 1;
        $this->tags = Message::getUserTags();
    }

    public function sent_action()
    {
        PageLayout::setTitle(_("Nachrichten"));
        PageLayout::setHelpKeyword("Basis.InteraktionNachrichten");
        Navigation::activateItem('/messaging/messages/sent');

        if (Request::isPost() && Request::get("delete_message")) {
            $messaging = new messaging();
            $success = $messaging->delete_message(Request::option("delete_message"));
            if ($success) {
                PageLayout::postMessage(MessageBox::success(_("Nachricht gelöscht!")));
            } else {
                PageLayout::postMessage(MessageBox::error(_("Nachricht konnte nicht gelöscht werden.")));
            }
        }

        $this->messages = $this->get_messages(
            false,
            Request::int("limit", $this->number_of_displayed_messages),
            Request::int("offset", 0),
            Request::get("tag"),
            Request::get("search")
        );
        $this->received = 0;
        $this->tags = Message::getUserTags();

        $this->render_action("overview");
    }

    public function more_action()
    {
        $messages = $this->get_messages(
            Request::int("received") ? true : false,
            Request::int("limit", $this->number_of_displayed_messages) + 1,
            Request::int("offset", 0),
            Request::get("tag"),
            Request::get("search")
        );
        $this->output = array('messages' => array(), "more" => 0);
        if (count($messages) > Request::int("limit")) {
            $this->output["more"] = 1;
            array_pop($messages);
        }
        $template_factory = $this->get_template_factory();
        foreach ($messages as $message) {
            $this->output['messages'][] = $template_factory
                                            ->open("messages/_message_row.php")
                                            ->render(compact("message"));
        }

        $this->render_text(json_encode(studip_utf8encode($this->output)));
    }

    public function read_action($message_id)
    {
        PageLayout::setTitle(_("Nachrichten"));
        PageLayout::setHelpKeyword("Basis.InteraktionNachrichten");
        $this->message = new Message($message_id);
        if (!$this->message->permissionToRead()) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        if ($this->message['autor_id'] === $GLOBALS['user']->id) {
            Navigation::activateItem('/messaging/messages/sent');
        } else {
            Navigation::activateItem('/messaging/messages/inbox');
        }
        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
            $this->response->add_header('X-Title', _("Betreff").": ".$this->message["subject"]);
        }
        $this->message->markAsRead($GLOBALS["user"]->id);
    }

    /**
     * Lets the user compose a message and send it.
     */
    public function write_action()
    {
        PageLayout::setTitle(_("Nachrichten"));
        PageLayout::setHelpKeyword("Basis.InteraktionNachrichten");
        Navigation::activateItem('/messaging/messages/sent');

        //collect possible default adressees
        $this->to = array();
        $this->default_message = new Message();
        if (Request::username("rec_uname")) {
            $user = new MessageUser();
            $user->setData(array('user_id' => get_userid(Request::username("rec_uname")), 'snd_rec' => "rec"));
            $this->default_message->receivers[] = $user;
        }
        if (Request::getArray("rec_uname")) {
            foreach (Request::getArray("rec_uname") as $username) {
                $user = new MessageUser();
                $user->setData(array('user_id' => get_userid($username), 'snd_rec' => "rec"));
                $this->default_message->receivers[] = $user;
            }
        }
        if (Request::option("group_id")) {
            $group = Statusgruppen::find(Request::option("group_id"));
            if (($group['range_id'] === $GLOBALS['user']->id)
                    || ($GLOBALS['perm']->have_studip_perm("autor", $group['range_id']))) {
                foreach ($group->members as $member) {
                    $user = new MessageUser();
                    $user->setData(array('user_id' => $member['user_id'], 'snd_rec' => "rec"));
                    $this->default_message->receivers[] = $user;
                }
            }
        }
        if (Request::get("filter") && Request::option("course_id")) {
            $params = array(Request::option('course_id'), Request::option('who'));
            switch (Request::get("filter")) {
                case 'send_sms_to_all':
                    $query = "SELECT b.user_id,'rec' as snd_rec FROM seminar_user a, auth_user_md5 b WHERE a.Seminar_id = ? AND a.user_id = b.user_id AND a.status = ? ORDER BY Nachname, Vorname";
                    break;
                case 'all':
                    $query = "SELECT user_id,'rec' as snd_rec FROM seminar_user LEFT JOIN auth_user_md5 USING(user_id) WHERE Seminar_id = ? ORDER BY Nachname, Vorname";
                    break;
                case 'prelim':
                    $query = "SELECT user_id,'rec' as snd_rec FROM admission_seminar_user LEFT JOIN auth_user_md5 USING(user_id) WHERE seminar_id = ? AND status='accepted' ORDER BY Nachname, Vorname";
                    break;
                case 'waiting':
                    $query = "SELECT user_id,'rec' as snd_rec FROM admission_seminar_user LEFT JOIN auth_user_md5 USING(user_id) WHERE seminar_id = ? AND status='awaiting' ORDER BY Nachname, Vorname";
                    break;
                case 'inst_status':
                    $query = "SELECT b.user_id,'rec' as snd_rec FROM user_inst a, auth_user_md5 b WHERE a.Institut_id = ? AND a.user_id = b.user_id AND a.inst_perms = ? ORDER BY Nachname, Vorname";
                    break;
            }
            $this->default_message->receivers = DBManager::get()->fetchAll($query, $params, 'MessageUser::build');
        }
        if (Request::option("answer_to")) {
            $old_message = new Message(Request::option("answer_to"));
            if (!$old_message->permissionToRead()) {
                throw new AccessDeniedException("Message is not for you.");
            }
            if (!Request::get('forward')) {
                if (Request::option("quote") === $old_message->getId()) {
                    $this->default_message['message'] = "[quote]\n".$old_message['message']."\n[/quote]";
                }
                $this->default_message['subject'] = substr($old_message['message'], 0, 4) === "RE: " ? $old_message['subject'] : "RE: ".$old_message['subject'];
                $user = new MessageUser();
                $user->setData(array('user_id' => $old_message['autor_id'], 'snd_rec' => "rec"));
                $this->default_message->receivers[] = $user;
            } else {
                $messagesubject = 'FWD: ' . $old_message['subject'];
                $message = _("-_-_ Weitergeleitete Nachricht _-_-");
                $message .= "\n" . _("Betreff") . ": " . $old_message['subject'];
                $message .= "\n" . _("Datum") . ": " . strftime('%x %X', $old_message['mkdate']);
                $message .= "\n" . _("Von") . ": " . get_fullname($old_message['autor_id']);
                $message .= "\n" . _("An") . ": " . join(', ', $old_message->getRecipients()->getFullname());
                $message .= "\n\n" . $old_message['message'];
                if (count($old_message->attachments)) {
                    Request::set('message_id', $old_message->getNewId());
                    foreach($old_message->attachments as $attachment) {
                        $attachment->range_id = 'provisional';
                        $attachment->seminar_id = $GLOBALS['user']->id;
                        $attachment->autor_host = $_SERVER['REMOTE_ADDR'];
                        $attachment->user_id = $GLOBALS['user']->id;
                        $attachment->description = Request::option('message_id');
                        $new_attachment = $attachment->toArray(array('range_id', 'user_id', 'seminar_id', 'name', 'description', 'filename', 'filesize'));
                        StudipDocument::createWithFile(get_upload_file_path($attachment->getId()), $new_attachment);
                        $this->default_attachments[] = array('icon' => Assets::img(GetFileIcon(getFileExtension($new_attachment['filename'])), array('class' => "text-bottom")),
                                                             'name' => $new_attachment['filename'],
                                                             'size' => relsize($new_attachment['filesize'],false));

                    }
                }
                $this->default_message['subject'] = $messagesubject;
                $this->default_message['message'] = $message;
            }
        }
        if (Request::get("default_body")) {
            $this->default_message['message'] = Request::get("default_body");
        }
        if (Request::get("default_subject")) {
            $this->default_message['subject'] = Request::get("default_subject");
        }
        NotificationCenter::postNotification("DefaultMessageForComposerCreated", $this->default_message);

        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
            $this->response->add_header('X-Title', _("Neue Nachricht schreiben"));
        }
    }

    /**
     * Sends a message and redirects the user.
     */
    public function send_action() {
        if (Request::isPost() && count(Request::getArray("message_to")) && Request::get("message_body")) {
            $messaging = new messaging();
            $rec_uname = array();
            foreach (Request::getArray("message_to") as $user_id) {
                if ($user_id) {
                    $rec_uname[] = get_username($user_id);
                }
            }
            $messaging->provisonal_attachment_id = Request::option("message_id");
            $messaging->insert_message(
                Request::get("message_body"),
                $rec_uname,
                $GLOBALS['user']->id,
                '',
                '',
                '',
                Request::get("message_signature") ? Request::get("message_signature_content") : "",
                Request::get("message_subject"),
                Request::get("message_mail") ? true : "",
                'normal',
                trim(Request::get("message_tags")) ?: null
            );
            PageLayout::postMessage(MessageBox::success(_("Nachricht wurde verschickt.")));
        }
        $this->redirect("messages/sent");
    }

    public function add_tag_action() {
        if (Request::isPost() && Request::get("tag")) {
            $message = new Message(Request::option("message_id"));
            $message->addTag(Request::get("tag"));

            $output = array();
            $factory = $this->get_template_factory();
            $template = $factory->open($this->get_default_template("read"));
            $template->set_attribute("message", $message);
            $output['full'] = $template->render();

            $template = $factory->open($this->get_default_template("_message_row"));
            $template->set_attribute("message", $message);
            $output['row'] = $template->render();

            $this->render_text(json_encode(studip_utf8encode($output)));
        } else {
            $this->render_nothing();
        }
    }

    public function remove_tag_action() {
        if (Request::isPost() && Request::get("tag")) {
            $message = new Message(Request::option("message_id"));
            $message->removeTag(Request::get("tag"));

            $output = array();
            $factory = $this->get_template_factory();
            $template = $factory->open($this->get_default_template("read"));
            $template->set_attribute("message", $message);
            $output['full'] = $template->render();

            $template = $factory->open($this->get_default_template("_message_row"));
            $template->set_attribute("message", $message);
            $output['row'] = $template->render();

            $this->render_text(json_encode(studip_utf8encode($output)));
        } else {
            $this->render_nothing();
        }
    }

    function print_action($message_id)
    {
        $message = Message::find($message_id);
        if ($message && $message->permissionToRead($GLOBALS['user']->id)) {
            $this->msg = $message->toArray();
            $this->msg['from'] = $message->getSender()->getFullname();
            $this->msg['to'] = $GLOBALS['user']->id == $message->autor_id ?
                join(', ', $message->getRecipients()->getFullname()) :
                $GLOBALS['user']->getFullname() . ' ' . sprintf(_('(und %d weitere)'), count($message->receivers)-1);
            $this->msg['attachments'] = $message->attachments->toArray('filename filesize');
            PageLayout::setTitle($data['subject']);
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
        } else {
            $this->set_status(400);
            return $this->render_nothing();
        }
    }

    protected function get_messages($received = true, $limit = 50, $offset = 0, $tag = null, $search = null)
    {
        if ($tag) {
            $messages_data = DBManager::get()->prepare("
                SELECT *
                FROM message
                    INNER JOIN message_user ON (message_user.message_id = message.message_id)
                    INNER JOIN message_tags ON (message_tags.message_id = message.message_id AND message_tags.user_id = message_user.user_id)
                WHERE message_user.user_id = :me
                    AND snd_rec = :sender_receiver
                    AND message_tags.tag = :tag
                ORDER BY message.mkdate DESC
                LIMIT ".(int) $offset .", ".(int) $limit ."
            ");
            $messages_data->execute(array(
                'me' => $GLOBALS['user']->id,
                'tag' => $tag,
                'sender_receiver' => $received ? "rec" : "snd"
            ));
        } elseif($search) {

            $suchmuster = '/".*"/U';
            preg_match_all($suchmuster, $search, $treffer);
            array_walk($treffer[0], function(&$value) { $value = trim($value, '"'); });

            // remove the quoted parts from $_searchfor
            $_searchfor = trim(preg_replace($suchmuster, '', $search));

            // split the searchstring $_searchfor at every space
            $parts = explode(' ', $_searchfor);
            foreach ($parts as $key => $val) {
                if ($val == '') {
                    unset($parts[$key]);
                }
            }
            if (!empty($parts)) {
                $_searchfor = array_merge($parts, $treffer[0]);
            } else  {
                $_searchfor = $treffer[0];
            }

            $search_sql = "";
            foreach ($_searchfor as $val) {
                $tmp_sql = array();
                if (Request::get("search_autor")) {
                    $tmp_sql[] = "CONCAT(auth_user_md5.Vorname, ' ', auth_user_md5.Nachname) LIKE ".DBManager::get()->quote("%".$val."%")." ";
                }
                if (Request::get("search_subject")) {
                    $tmp_sql[] = "message.subject LIKE ".DBManager::get()->quote("%".$val."%")." ";
                }
                if (Request::get("search_content")) {
                    $tmp_sql[] = "message.message LIKE ".DBManager::get()->quote("%".$val."%")." ";
                }
                $search_sql .= "AND (";
                $search_sql .= implode(" OR ", $tmp_sql);
                $search_sql .= ") ";
            }



            $messages_data = DBManager::get()->prepare("
                SELECT *
                FROM message
                    INNER JOIN message_user ON (message_user.message_id = message.message_id)
                    INNER JOIN auth_user_md5 ON (auth_user_md5.user_id = message.autor_id)
                WHERE message_user.user_id = :me
                    AND snd_rec = :sender_receiver
                    $search_sql
                ORDER BY message.mkdate DESC
                LIMIT ".(int) $offset .", ".(int) $limit ."
            ");
            $messages_data->execute(array(
                'me' => $GLOBALS['user']->id,
                'sender_receiver' => $received ? "rec" : "snd"
            ));
        } else {
            $messages_data = DBManager::get()->prepare("
                SELECT *
                FROM message
                    INNER JOIN message_user ON (message_user.message_id = message.message_id)
                WHERE message_user.user_id = :me
                    AND snd_rec = :sender_receiver
                ORDER BY message.mkdate DESC
                LIMIT ".(int) $offset .", ".(int) $limit ."
            ");
            $messages_data->execute(array(
                'me' => $GLOBALS['user']->id,
                'sender_receiver' => $received ? "rec" : "snd"
            ));
        }
        $messages_data = $messages_data->fetchAll(PDO::FETCH_ASSOC);
        $messages = array();
        foreach ($messages_data as $data) {
            $message = new Message();
            $message->setData($data);
            $message->setNew(false);
            $messages[] = $message;
        }
        return $messages;
    }

    public function upload_attachment_action() {
        //var_dump($_FILES);
        $file = $_FILES['file'];
        $output = array(
            'name' => $file['name'],
            'size' => $file['size']
        );
        $output['message_id'] = Request::option("message_id");
        if (!validate_upload($file)) {
            list($type, $error) = explode("§", $GLOBALS['msg']);
            throw new Exception($error);
        }

        $document = new StudipDocument();
        $document->setValue('range_id' , 'provisional');
        $document->setValue('seminar_id' , $GLOBALS['user']->id);
        $document->setValue('name' , $output['name']);
        $document->setValue('filename' , $document->getValue('name'));
        $document->setValue('filesize' , (int) $output['size']);
        $document->setValue('autor_host' , $_SERVER['REMOTE_ADDR']);
        $document->setValue('user_id' , $GLOBALS['user']->id);
        $document->setValue('description', Request::option('message_id'));
        $success = $document->store();
        if (!$success) {
            throw new Exception("Unable to handle uploaded file.");
        }
        $file_moved = move_uploaded_file($file['tmp_name'], get_upload_file_path($document->getId()));
        if(!$file_moved) {
            throw new Exception("No permission to move file to destination.");
        }

        $output['document_id'] = $document->getId();
        $output['icon'] = Assets::img(GetFileIcon(substr($output['name'], strrpos($output['name'], ".") + 1)), array('class' => "text-bottom"));

        $this->render_text(json_encode(studip_utf8encode($output)));
    }

}
