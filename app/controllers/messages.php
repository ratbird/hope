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
require_once 'lib/statusgruppe.inc.php';

class MessagesController extends AuthenticatedController {

    protected $number_of_displayed_messages = 50;
    protected $utf8decode_xhr = true;

    function before_filter (&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setTitle(_("Nachrichten"));
        PageLayout::setHelpKeyword("Basis.InteraktionNachrichten");

        if (Request::isXhr()&& Request::isGet()) {
            $request = Request::getInstance();
            foreach(words('default_body default_subject') as $key) {
                $request[$key] = Request::removeMagicQuotes($_GET[$key]);
            }
        }
    }

    public function overview_action($message_id = null)
    {
        Navigation::activateItem('/messaging/messages/inbox');


        if (Request::get("read_all")) {
            Message::markAllAs($GLOBALS['user']->id, 1);
            PageLayout::postMessage(MessageBox::success(_("Alle Nachrichten wurden als gelesen markiert.")));
        }

        if (Request::isPost()) {
            foreach (Request::getArray("bulk") as $message_id) {
                $this->delete_message($message_id);
            }
            PageLayout::postMessage(MessageBox::success(sprintf(_("%u Nachrichten wurden gel�scht"), count(Request::getArray("bulk")))));
        }

        $this->messages = $this->get_messages(
            true,
            Request::int("limit", $this->number_of_displayed_messages),
            Request::int("offset", 0),
            Request::get("tag"),
            Request::get("search")
        );
        $this->received   = true;
        $this->tags       = Message::getUserTags();
        $this->message_id = $message_id;
        $this->settings   = UserConfig::get($GLOBALS['user']->id)->MESSAGING_SETTINGS;
    }

    public function sent_action($message_id = null)
    {
        Navigation::activateItem('/messaging/messages/sent');

        if (Request::isPost()) {
            foreach (Request::getArray("bulk") as $message_id) {
                $this->delete_message($message_id);
            }
            PageLayout::postMessage(MessageBox::success(sprintf(_("%u Nachrichten wurden gel�scht"), count(Request::getArray("bulk")))));
        }

        $this->messages = $this->get_messages(
            false,
            Request::int("limit", $this->number_of_displayed_messages),
            Request::int("offset", 0),
            Request::get("tag"),
            Request::get("search")
        );
        $this->received   = false;
        $this->tags       = Message::getUserTags();
        $this->message_id = $message_id;
        $this->settings   = UserConfig::get($GLOBALS['user']->id)->MESSAGING_SETTINGS;

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
        $this->settings   = UserConfig::get($GLOBALS['user']->id)->MESSAGING_SETTINGS;
        $template_factory = $this->get_template_factory();
        foreach ($messages as $message) {
            $this->output['messages'][] = $template_factory
                                            ->open("messages/_message_row.php")
                                            ->render(array('message'    => $message,
                                                           'controller' => $this,
                                                           'received'   => (bool) Request::int("received"),
                                                           'settings'   => $this->settings
                                                    ));
        }

        $this->render_json($this->output);
    }

    public function read_action($message_id)
    {
        $this->message = new Message($message_id);
        if (!$this->message->permissionToRead()) {
            throw new AccessDeniedException();
        }

        PageLayout::setTitle(_('Betreff') . ': ' . $this->message['subject']);

        if ($this->message['autor_id'] === $GLOBALS['user']->id) {
            Navigation::activateItem('/messaging/messages/sent');
        } else {
            Navigation::activateItem('/messaging/messages/inbox');
        }
        if (Request::isXhr()) {
            $this->response->add_header('X-Tags', json_encode(studip_utf8encode($this->message->getTags())));
            $this->response->add_header('X-All-Tags', json_encode(studip_utf8encode(Message::getUserTags())));
        } else {
            // Try to redirect to overview of recevied/sent messages if
            // controller is not called via ajax to ensure message is loaded
            // in dialog.
            $target = ($this->message->autor_id === $GLOBALS['user']->id)
                    ? $this->url_for('messages/sent/' . $message_id)
                    : $this->url_for('messages/overview/' . $message_id);

            $script = sprintf('if (STUDIP.Dialog.shouldOpen()) { location.href = "%s"; }', $target);
            PageLayout::addHeadElement('script', array(), $script);
        }
        $this->message->markAsRead($GLOBALS["user"]->id);
    }

    /**
     * Lets the user compose a message and send it.
     */
    public function write_action()
    {
        PageLayout::setTitle(_("Neue Nachricht schreiben"));

        //collect possible default adressees
        $this->to = array();
        $this->default_message = new Message();
        if (Request::username("rec_uname")) {
            $user = new MessageUser();
            $user->setData(array('user_id' => get_userid(Request::username("rec_uname")), 'snd_rec' => "rec"));
            $this->default_message->receivers[] = $user;
        }
        if (Request::getArray("rec_uname")) {
            foreach (Request::usernameArray("rec_uname") as $username) {
                $user = new MessageUser();
                $user->setData(array('user_id' => get_userid($username), 'snd_rec' => "rec"));
                $this->default_message->receivers[] = $user;
            }
        }
        if (Request::option("group_id")) {
            $this->default_message->receivers = array();
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

        if(Request::get('inst_id') && $GLOBALS['perm']->have_perm('admin')) {
            $query = "SELECT user_id FROM user_inst WHERE Institut_id = ? AND inst_perms != 'user'";
            $this->default_message->receivers = DBManager::get()->fetchAll($query, array(Request::option('inst_id')), 'MessageUser::build');
        }

        if (Request::get("filter") && Request::option("course_id") && $GLOBALS['perm']->have_studip_perm('tutor', Request::option("course_id"))) {
            $this->default_message->receivers = array();
            if (Request::get("filter") === 'claiming') {
                $cs = CourseSet::getSetForCourse(Request::option("course_id"));
                if (is_object($cs) && !$cs->hasAlgorithmRun()) {
                    foreach (AdmissionPriority::getPrioritiesByCourse($cs->getId(), Request::option("course_id")) as $user_id => $p) {
                        $this->default_message->receivers[] = MessageUser::build(array('user_id' => $user_id, 'snd_rec' => 'rec'));
                    }
                }
            } else {
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
                    case 'awaiting':
                        $query = "SELECT user_id,'rec' as snd_rec FROM admission_seminar_user LEFT JOIN auth_user_md5 USING(user_id) WHERE seminar_id = ? AND status='awaiting' ORDER BY Nachname, Vorname";
                        break;
                    case 'inst_status':
                        $query = "SELECT b.user_id,'rec' as snd_rec FROM user_inst a, auth_user_md5 b WHERE a.Institut_id = ? AND a.user_id = b.user_id AND a.inst_perms = ? ORDER BY Nachname, Vorname";
                        break;
                }
                $this->default_message->receivers = DBManager::get()->fetchAll($query, $params, 'MessageUser::build');
            }

        }

        if (Request::option('prof_id') && Request::option('deg_id') && $GLOBALS['perm']->have_perm('root')) {
            $query = "SELECT DISTINCT user_id,'rec' as snd_rec
            FROM user_studiengang
            WHERE studiengang_id = ? AND abschluss_id = ?";
            $this->default_message->receivers = DBManager::get()->fetchAll($query, array(
                Request::option('prof_id'),
                Request::option('deg_id')
            ), 'MessageUser::build');
        }

        if (Request::option('sd_id') && $GLOBALS['perm']->have_perm('root')) {
            $query = "SELECT DISTINCT user_id,'rec' as snd_rec
            FROM user_studiengang
            WHERE abschluss_id = ?";
            $this->default_message->receivers = DBManager::get()->fetchAll($query, array(
                Request::option('sd_id')
            ), 'MessageUser::build');
        }

        if (Request::option('sp_id') && $GLOBALS['perm']->have_perm('root')) {
            $query = "SELECT DISTINCT user_id,'rec' as snd_rec
            FROM user_studiengang
            WHERE studiengang_id = ?";
            $this->default_message->receivers = DBManager::get()->fetchAll($query, array(
                Request::option('sp_id')
            ), 'MessageUser::build');
        }

        if (!$this->default_message->receivers->count() && is_array($_SESSION['sms_data']['p_rec'])) {
            $this->default_message->receivers = DBManager::get()->fetchAll("SELECT user_id,'rec' as snd_rec FROM auth_user_md5 WHERE username IN(?) ORDER BY Nachname,Vorname", array($_SESSION['sms_data']['p_rec']), 'MessageUser::build');
            unset($_SESSION['sms_data']);
        }
        if (Request::option("answer_to")) {
            $this->default_message->receivers = array();
            $old_message = new Message(Request::option("answer_to"));
            if (!$old_message->permissionToRead()) {
                throw new AccessDeniedException("Message is not for you.");
            }
            if (!Request::get('forward')) {
                if (Request::option("quote") === $old_message->getId()) {
                    if (Studip\Markup::isHtml($old_message['message'])) {
                        $this->default_message['message'] = "<div>[quote]\n".$old_message['message']."\n[/quote]</div>";
                    } else {
                        $this->default_message['message'] = "[quote]\n".$old_message['message']."\n[/quote]";
                    }
                }
                $this->default_message['subject'] = substr($old_message['message'], 0, 4) === "RE: " ? $old_message['subject'] : "RE: ".$old_message['subject'];
                $user = new MessageUser();
                $user->setData(array('user_id' => $old_message['autor_id'], 'snd_rec' => "rec"));
                $this->default_message->receivers[] = $user;
                $this->answer_to = $old_message->id;
            } else {
                $messagesubject = 'FWD: ' . $old_message['subject'];
                $message = _("-_-_ Weitergeleitete Nachricht _-_-");
                $message .= "\n" . _("Betreff") . ": " . $old_message['subject'];
                $message .= "\n" . _("Datum") . ": " . strftime('%x %X', $old_message['mkdate']);
                $message .= "\n" . _("Von") . ": " . get_fullname($old_message['autor_id']);
                $num_recipients = $old_message->getNumRecipients();
                if ($GLOBALS['user']->id == $old_message->autor_id) {
                    $message .= "\n" . _("An") . ": " . ($num_recipients == 1 ? _('Eine Person') : sprintf(_('%s Personen'), $num_recipients));
                } else {
                    $message .= "\n" . _("An") . ": " . $GLOBALS['user']->getFullname() . ($num_recipients > 1 ? ' ' . sprintf(_('(und %d weitere)'), $num_recipients) : '');
                }
                $message .= "\n\n";
                if (Studip\Markup::isHtml($old_message['message'])) {
                    $message = '<div>' . htmlReady($message,false,true) . '</div>' . $old_message['message'];
                } else {
                    $message .= $old_message['message'];
                }
                if (count($old_message->attachments)) {
                    Request::set('message_id', $old_message->getNewId());
                    foreach($old_message->attachments as $attachment) {
                        $attachment->range_id = 'provisional';
                        $attachment->seminar_id = $GLOBALS['user']->id;
                        $attachment->autor_host = $_SERVER['REMOTE_ADDR'];
                        $attachment->user_id = $GLOBALS['user']->id;
                        $attachment->description = Request::option('message_id');
                        $new_attachment = $attachment->toArray(array('range_id', 'user_id', 'seminar_id', 'name', 'description', 'filename', 'filesize'));
                        $new_attachment = StudipDocument::createWithFile(get_upload_file_path($attachment->getId()), $new_attachment);
                        $this->default_attachments[] = array('icon' => Assets::img(GetFileIcon(getFileExtension($new_attachment['filename'])), array('class' => "text-bottom")),
                                                             'name' => $new_attachment['filename'],
                                                             'document_id' => $new_attachment->id,
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
        $settings = UserConfig::get($GLOBALS['user']->id)->MESSAGING_SETTINGS;
        $this->mailforwarding = Request::get('emailrequest') ? true : $settings['request_mail_forward'];
        if (trim($settings['sms_sig'])) {
            if (Studip\Markup::isHtml($this->default_message['message']) || Studip\Markup::isHtml($settings['sms_sig'])) {
                if (!Studip\Markup::isHtml($this->default_message['message'])) {
                    $this->default_message['message'] = '<div>' . nl2br($this->default_message['message']) . '</div>';
                }
                $this->default_message['message'] .= '<br><br>--<br>';
                if (Studip\Markup::isHtml($settings['sms_sig'])) {
                    $this->default_message['message'] .= $settings['sms_sig'];
                } else {
                    $this->default_message['message'] .= formatReady($settings['sms_sig']);
                }

            } else {
                $this->default_message['message'] .= "\n\n--\n" . $settings['sms_sig'];
            }
        }
        NotificationCenter::postNotification("DefaultMessageForComposerCreated", $this->default_message);


    }

    /**
     * Sends a message and redirects the user.
     */
    public function send_action() {
        PageLayout::setTitle(_("Nachricht verschicken"));
        if (Request::isPost() && count(array_filter(Request::getArray("message_to"))) && Request::submitted("message_body")) {
            $messaging = new messaging();
            $rec_uname = array();
            foreach (Request::getArray("message_to") as $user_id) {
                if ($user_id) {
                    $rec_uname[] = get_username($user_id);
                }
            }
            $messaging->provisonal_attachment_id = Request::option("message_id");
            $messaging->send_as_email =  Request::int("message_mail");
            $messaging->insert_message(
                Request::get("message_body"),
                $rec_uname,
                $GLOBALS['user']->id,
                '',
                '',
                '',
                null,
                Request::get("message_subject"),
                "",
                'normal',
                trim(Request::get("message_tags")) ?: null
            );
            if (Request::option('answer_to')) {
                $old_message = Message::find(Request::option('answer_to'));
                if ($old_message) {
                    $old_message->originator->answered = 1;
                    $old_message->store();
                }
            }
            PageLayout::postMessage(MessageBox::success(_("Nachricht wurde verschickt.")));
        } else if (!count(array_filter(Request::getArray('message_to')))) {
            PageLayout::postMessage(MessageBox::error(_('Sie haben nicht angegeben, wer die Nachricht empfangen soll!')));
        }
    }

    public function tag_action($message_id) {
        if (Request::isPost()) {
            $message = Message::find($message_id);
            if (!$message->permissionToRead()) {
                throw new AccessDeniedException();
            }
            if (Request::get('add_tag')) {
                $message->addTag(Request::get('add_tag'));
            } elseif (Request::get('remove_tag')) {
                $message->removeTag(Request::get('remove_tag'));
            }
        }
        $this->redirect('messages/read/' . $message_id);
    }

    function print_action($message_id)
    {
        $message = Message::find($message_id);
        if (!$message->permissionToRead()) {
            throw new AccessDeniedException();
        }
        if ($message && $message->permissionToRead($GLOBALS['user']->id)) {
            $this->msg = $message->toArray();
            $this->msg['from'] = $message['autor_id'] === '____%system%____'
                                ? _('Stud.IP')
                                : ($message->getSender()
                                    ? $message->getSender()->getFullname()
                                    : _('unbekannt'));
            $this->msg['to'] = $GLOBALS['user']->id == $message->autor_id ?
                join(', ', $message->getRecipients()->pluck('fullname')) :
                $GLOBALS['user']->getFullname() . ' ' . sprintf(_('(und %d weitere)'), $message->getNumRecipients()-1);
            $this->msg['attachments'] = $message->attachments->toArray('filename filesize');
            PageLayout::setTitle($this->msg['subject']);
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        } else {
            $this->set_status(400);
            return $this->render_nothing();
        }
    }

    protected function delete_message($message_id)
    {
        $messageuser = new MessageUser(array($GLOBALS['user']->id, $message_id, "snd"));
        $success = 0;
        if (!$messageuser->isNew()) {
            $messageuser['deleted'] = 1;
            $success = $messageuser->store();
        }
        $messageuser = new MessageUser(array($GLOBALS['user']->id, $message_id, "rec"));
        if (!$messageuser->isNew()) {
            $messageuser['deleted'] = 1;
            $success += $messageuser->store();
        }
        return $success;
    }

    protected function get_messages($received = true, $limit = 50, $offset = 0, $tag = null, $search = null)
    {
        if ($tag) {
            $messages_data = DBManager::get()->prepare("
                SELECT message.*
                FROM message_user
                    INNER JOIN message ON (message_user.message_id = message.message_id)
                    INNER JOIN message_tags ON (message_tags.message_id = message_user.message_id AND message_tags.user_id = message_user.user_id)
                WHERE message_user.user_id = :me
                    AND message_user.deleted = 0
                    AND snd_rec = :sender_receiver
                    AND message_tags.tag = :tag
                ORDER BY message_user.mkdate DESC
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

            if (!Request::int('search_autor') && !Request::int('search_subject') && !Request::int('search_content')) {
                $message = _('Es wurden keine Bereiche angegeben, in denen gesucht werden soll.');
                PageLayout::postMessage(MessageBox::error($message));

                $search_sql = "AND 0";
            } else {
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
            }

            $messages_data = DBManager::get()->prepare("
                SELECT message.*
                FROM message_user
                    INNER JOIN message ON (message_user.message_id = message.message_id)
                    LEFT JOIN auth_user_md5 ON (auth_user_md5.user_id = message.autor_id)
                WHERE message_user.user_id = :me
                    AND message_user.deleted = 0
                    AND snd_rec = :sender_receiver
                    $search_sql
                ORDER BY message_user.mkdate DESC
                LIMIT ".(int) $offset .", ".(int) $limit ."
            ");
            $messages_data->execute(array(
                'me' => $GLOBALS['user']->id,
                'sender_receiver' => $received ? "rec" : "snd"
            ));
        } else {
            $messages_data = DBManager::get()->prepare("
                SELECT message.*
                FROM message_user
                    INNER JOIN message ON (message_user.message_id = message.message_id)
                WHERE message_user.user_id = :me
                    AND message_user.deleted = 0
                    AND snd_rec = :sender_receiver
                ORDER BY message_user.mkdate DESC
                LIMIT ".(int) $offset .", ".(int) $limit ."
            ");
            $messages_data->execute(array(
                'me' => $GLOBALS['user']->id,
                'sender_receiver' => $received ? "rec" : "snd"
            ));
        }
        $messages_data->setFetchMode(PDO::FETCH_ASSOC);
        $messages = array();
        foreach ($messages_data as $data) {
            $messages[] = Message::buildExisting($data);
        }
        return $messages;
    }

    public function upload_attachment_action() {
        if ($GLOBALS['user']->id === "nobody") {
            throw new AccessDeniedException();
        }
        if (!$GLOBALS['ENABLE_EMAIL_ATTACHMENTS']) {
            throw new AccessDeniedException(_('Mailanh�nge sind nicht erlaubt.'));
        }
        $file = studip_utf8decode($_FILES['file']);
        $output = array(
            'name' => $file['name'],
            'size' => $file['size']
        );
        $output['message_id'] = Request::option("message_id");
        if (!validate_upload($file)) {
            list($type, $error) = explode("�", $GLOBALS['msg']);
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
        $output['icon'] = (string)Assets::img(GetFileIcon(getFileExtension($output['name'])), array('class' => "text-bottom"));

        $this->render_json($output);
    }

    public function delete_attachment_action()
    {
        CSRFProtection::verifyUnsafeRequest();
        $doc = StudipDocument::find(Request::option('document_id'));
        if ($doc && $doc->range_id == 'provisional' && $doc->description == Request::option('message_id')) {
            @unlink(get_upload_file_path($doc->id));
            $doc->delete();
        }
        $this->render_nothing();
    }

    public function preview_action()
    {
        if (Request::isXhr()) {
            $this->render_text(formatReady(Request::get("text")));
        }
    }

    public function delete_tag_action()
    {
        CSRFProtection::verifyUnsafeRequest();
        DbManager::get()->execute("DELETE FROM message_tags WHERE user_id=? AND tag LIKE ?", array($GLOBALS['user']->id, Request::get('tag')));
        PageLayout::postMessage(MessageBox::success(_('Schlagwort gel�scht!')));
        $this->redirect($this->url_for('messages/overview'));
    }

    function after_filter($action, $args)
    {
        if (Request::isXhr()) {
            if (PageLayout::getTitle()) {
                $this->response->add_header('X-Title', PageLayout::getTitle());
            }
        }
        parent::after_filter($action, $args);
    }

}
