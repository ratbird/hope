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
            Request::int("limit", 50),
            Request::int("offset", 0),
            Request::get("tag")
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
            Request::int("limit", 50),
            Request::int("offset", 0),
            Request::get("tag")
        );
        $this->received = 0;
        $this->tags = Message::getUserTags();
        
        $this->render_action("overview");
    }
    
    public function more_action()
    {
        $messages = $this->get_messages(
            Request::int("received") ? true : false,
            Request::int("limit", 50) + 1,
            Request::int("offset", 0),
            Request::get("tag")
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
        Navigation::activateItem('/messaging/messages/write');

        //collect possible default adressees
        $this->to = array();
        if (Request::username("rec_uname")) {
            $this->to[] = get_userid(Request::username("rec_uname"));
        }
        if (Request::getArray("rec_uname")) {
            $this->to = array_map("get_userid", Request::getArray("rec_uname"));
        }
        if (Request::option("group_id")) {
            $group = Statusgruppen::find(Request::option("group_id"));
            if (($group['range_id'] === $GLOBALS['user']->id)
                    || ($GLOBALS['perm']->have_studip_perm("autor", $group['range_id']))) {
                $this->to += $group->members->map(function ($m) { return $m['user_id']; });
            }
        }
        if (Request::get("filter") && Request::option("course_id")) {
            $course_id = Request::option('course_id');
            switch (Request::get("filter")) {
                case 'send_sms_to_all':
                    $who = Request::quoted('who');
                    $query = "SELECT b.user_id FROM seminar_user a, auth_user_md5 b WHERE a.Seminar_id = '".$course_id."' AND a.user_id = b.user_id AND a.status = '$who' ORDER BY Nachname, Vorname";
                    break;
                case 'all':
                    $query = "SELECT user_id FROM seminar_user LEFT JOIN auth_user_md5 USING(user_id) WHERE Seminar_id = '".$course_id."' ORDER BY Nachname, Vorname";
                    break;
                case 'prelim':
                    $query = "SELECT user_id FROM admission_seminar_user LEFT JOIN auth_user_md5 USING(user_id) WHERE seminar_id = '".$course_id."' AND status='accepted' ORDER BY Nachname, Vorname";
                    break;
                case 'waiting':
                    $query = "SELECT user_id FROM admission_seminar_user LEFT JOIN auth_user_md5 USING(user_id) WHERE seminar_id = '".$course_id."' AND (status='awaiting' OR status='claiming') ORDER BY Nachname, Vorname";
                    break;
                case 'inst_status':
                    $who = Request::quoted('who');
                    $query = "SELECT b.user_id FROM user_inst a, auth_user_md5 b WHERE a.Institut_id = '".$course_id."' AND a.user_id = b.user_id AND a.inst_perms = '$who' ORDER BY Nachname, Vorname";
                    break;
            }
            $this->to += DBManager::get()->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        if (Request::option("answer_to")) {

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
                Request::get("message_tags")
            );
            PageLayout::postMessage(MessageBox::success(_("Nachricht wurde verschickt.")));
        }
        $this->redirect("messages/sent");
    }

    public function add_tag_action() {
        if (Request::isPost() && Request::get("tag")) {
            $message = new Message(Request::option("message_id"));
            $message->addTag(Request::get("tag"));
            $this->redirect("messages/read/".$message->getId());
        } else {
            $this->render_nothing();
        }
    }

    public function remove_tag_action() {
        if (Request::isPost() && Request::get("tag")) {
            $message = new Message(Request::option("message_id"));
            $message->removeTag(Request::get("tag"));
            $this->redirect("messages/read/".$message->getId());
        } else {
            $this->render_nothing();
        }
    }

    function print_action($message_id, $sndrec = 'rec')
    {
        $data = get_message_data($message_id, $GLOBALS['user']->id, $sndrec);
        if ($data) {
            $this->msg = $data;
            $this->msg['from'] = get_fullname($data['snd_uid']);
            $this->msg['to'] = join(', ', array_map('get_fullname', explode(',', $data['rec_uid'])));
            $this->msg['attachments'] = array_filter(array_map(array('StudipDocument','find'), array_unique(explode(',', $data['attachments']))));
            PageLayout::setTitle($data['subject']);
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
        } else {
            $this->set_status(400);
            return $this->render_nothing();
        }
    }
    
    protected function get_messages($received = true, $limit = 50, $offset = 0, $tag = null)
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
        } else{
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
