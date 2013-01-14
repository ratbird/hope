<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/application.php";

class StreamsController extends ApplicationController {

    protected $max_threads = 10;

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function global_action() {
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/autoresize.jquery.min.js"), "");
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/blubberforum.js"), "");
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/formdata.js"), "");
        PageLayout::setTitle(_("Globaler Blubberstream"));
        Navigation::activateItem("/community/blubber");

        $parameter = array(
            'limit' => $this->max_threads + 1
        );
        if (Request::get("hash")) {
            $this->search = "#".Request::get("hash");
        }
        if ($this->search) {
            $parameter['search'] = $this->search;
        }
        $this->threads = BlubberPosting::getThreads($parameter);
        $this->more_threads = count($this->threads) > $this->max_threads;
        if ($this->more_threads) {
            $this->threads = array_slice($this->threads, 0, $this->max_threads);
        }
        $this->contact_groups = DBManager::get()->query(
            "SELECT statusgruppen.* " .
            "FROM statusgruppen " .
            "WHERE statusgruppen.range_id = ".DBManager::get()->quote($GLOBALS['user']->id)." " .
            "ORDER BY name ASC " .
        "")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function forum_action() {
        object_set_visit($_SESSION['SessionSeminar'], "forum");
        $seminar = new Seminar($_SESSION['SessionSeminar']);
        if ($seminar->read_level > 0 && !$GLOBALS['perm']->have_studip_perm("autor", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/autoresize.jquery.min.js"), "");
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/blubberforum.js"), "");
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/formdata.js"), "");
        PageLayout::setTitle($GLOBALS['SessSemName']["header_line"]." - ".$this->plugin->getDisplayTitle());
        Navigation::getItem("/course/blubberforum")->setImage($this->plugin->getPluginURL()."/assets/images/blubber.png");
        Navigation::activateItem("/course/blubberforum");
        $parameter = array(
            'seminar_id' => $_SESSION['SessionSeminar'],
            'limit' => $this->max_threads + 1
        );
        if (Request::get("hash")) {
            $this->search = "#".Request::get("hash");
        }
        if ($this->search) {
            $parameter['search'] = $this->search;
        }
        $this->threads = BlubberPosting::getThreads($parameter);
        $this->more_threads = count($this->threads) > $this->max_threads;
        $this->course_id = $_SESSION['SessionSeminar'];
        if ($this->more_threads) {
            $this->threads = array_slice($this->threads, 0, $this->max_threads);
        }
    }

    public function profile_action() {
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/autoresize.jquery.min.js"), "");
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/blubberforum.js"), "");
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/formdata.js"), "");

        if (Request::get("extern")) {
            $this->user = BlubberExternalContact::find(Request::get("user_id"));
        } else {
            $this->user = new BlubberUser(Request::get("user_id"));
        }
        PageLayout::setTitle(htmlReady($this->user->getName())." - Blubber");
        
        $this->threads = BlubberPosting::getThreads(array(
            'user_id' => $this->user->getId(),
            'limit' => $this->max_threads + 1
        ));
        $this->more_threads = count($this->threads) > $this->max_threads;
        $this->course_id = $_SESSION['SessionSeminar'];
        if ($this->more_threads) {
            $this->threads = array_slice($this->threads, 0, $this->max_threads);
        }
    }

    public function more_comments_action() {
        $thread = new BlubberPosting(Request::option("thread_id"));
        if ($thread['context_type'] === "course") {
            //&& !$GLOBALS['perm']->have_studip_perm("autor", $thread['Seminar_id'])) {
            $seminar = new Seminar($thread['Seminar_id']);
            if ($seminar->read_level > 0 && !$GLOBALS['perm']->have_studip_perm("autor", $thread['Seminar_id'])) {
                throw new AccessDeniedException("Kein Zugriff");
            }
        }
        BlubberPosting::$course_hashes = $thread['context_type'] === "course" ? $thread['Seminar_id'] : false;

        $output = array(
            'more' => false,
            'comments' => array()
        );
        $comments = $thread->getChildren();

        if (($last_id = Request::option("last_id")) && (Request::option('count') !== 'all')) {
            $count = Request::int("count", 20);
            $ids   = array_map(function ($item) { return $item->getId(); }, $comments);
            $pos   = max(0, array_search($last_id, $ids) - $count);

            if ($pos > 0) {
                $comments = array_slice($comments, $pos);
                $output['more'] = sprintf(ngettext('%u weiterer Kommentar', '%u weitere Kommentare', $pos), $pos);
            }
        }

        $factory = new Flexi_TemplateFactory($this->plugin->getPluginPath()."/views");
        foreach ($comments as $posting) {
            $template = $factory->open("streams/comment.php");
            $template->set_attribute('posting', $posting);
            $template->set_attribute('course_id', $thread['Seminar_id']);
            $output['comments'][] = array(
                'content' => studip_utf8encode($template->render()),
                'mkdate' => $posting['mkdate'],
                'posting_id' => $posting->getId()
            );
        }
        $this->render_json($output);
    }

    public function more_postings_action() {
        $context_id = Request::option("context_id");
        if (Request::get("stream") === "course") {
            $seminar = new Seminar($context_id);
            if ($seminar->read_level > 0 && !$GLOBALS['perm']->have_studip_perm("autor", $context_id)) {
                throw new AccessDeniedException("Kein Zugriff");
            }
        }
        $output = array();
        $parameter = array(
            'offset' => $this->max_threads * Request::int("offset"),
            'stream_time' => Request::int("stream_time"),
            'limit' => $this->max_threads + 1
        );
        if (Request::get("stream") === "course") {
            $parameter['seminar_id'] = $context_id;
        }
        if (Request::get("stream") === "profile") {
            $parameter['user_id'] = $context_id;
        }
        $threads = BlubberPosting::getThreads($parameter);
        $output['more'] = count($threads) > $this->max_threads;
        if ($output['more']) {
            $threads = array_slice($threads, 0, $this->max_threads);
        }
        $output['threads'] = array();
        $factory = new Flexi_TemplateFactory($this->plugin->getPluginPath()."/views");
        foreach ($threads as $posting) {
            $template = $factory->open("streams/thread.php");
            $template->set_attribute('thread', $posting);
            $template->set_attribute('course_id', $_SESSION['SessionSeminar']);
            $template->set_attribute('controller', $this);
            $output['threads'][] = array(
                'content' => studip_utf8encode($template->render()),
                'mkdate' => $posting['mkdate'],
                'posting_id' => $posting->getId()
            );
        }
        $this->render_json($output);
    }

    public function new_posting_action() {
        $context = Request::option("context");
        $context_type = Request::option("context_type");
        if ($context_type === "course") {
            $seminar = new Seminar($context);
            if ($seminar->write_level > 0 && !$GLOBALS['perm']->have_studip_perm("autor", $context)) {
                throw new AccessDeniedException("Kein Zugriff");
            }
        }
        BlubberPosting::$course_hashes = ($context_type === "course" ? $context : false);
        $output = array();
        $thread = new BlubberPosting(Request::option("thread"));
        $thread['seminar_id'] = $context_type === "course" ? $context : $GLOBALS['user']->id;
        $thread['context_type'] = $context_type;
        $thread['parent_id'] = 0;
        
        if ($thread->isNew() && !$thread->getId()) {
            $thread->store();
        }
        BlubberPosting::$mention_thread_id = $thread->getId();
        StudipTransformFormat::addStudipMarkup("mention1", '@\"[^\n\"]*\"', "", "BlubberPosting::mention");
        StudipTransformFormat::addStudipMarkup("mention2", '@[^\s]*[\d\w_]+', "", "BlubberPosting::mention");
        $content = transformBeforeSave(studip_utf8decode(Request::get("content")));
        
        if (strpos($content, "\n") !== false) {
            $thread['name'] = substr($content, 0, strpos($content, "\n"));
            $thread['description'] = $content;
        } else {
            if (strlen($content) > 255) {
                $thread['name'] = "";
            } else {
                $thread['name'] = $content;
            }
            $thread['description'] = $content;
        }
        if ($GLOBALS['user']->id !== "nobody") {
            $thread['user_id'] = $GLOBALS['user']->id;
        } else {
            if (Request::get("anonymous_security") === $_SESSION['blubber_anonymous_security']) {
                $contact_user = BlubberExternalContact::findByEmail(Request::get("anonymous_email"));
                $_SESSION['anonymous_email'] = Request::get("anonymous_email");
                $_SESSION['anonymous_name'] = $contact_user['name'] = Request::get("anonymous_name");
                $contact_user->store();
                $thread['user_id'] = $contact_user->getId();
                $thread['external_contact'] = 1;
            } else {
                throw new AccessDeniedException("No permission to write posting.");
            }
        }
        $thread['author_host'] = $_SERVER['REMOTE_ADDR'];
        $thread['root_id'] = $thread->getId();
        if ($thread->store()) {
            if ($context_type === "private") {
                $statement = DBManager::get()->prepare(
                    "INSERT IGNORE INTO blubber_mentions " .
                    "SET user_id = :user_id, " .
                        "topic_id = :thread_id, " .
                        "mkdate = UNIX_TIMESTAMP() " .
                "");
                $statement->execute(array(
                    'user_id' => $GLOBALS['user']->id,
                    'thread_id' => $thread->getId()
                ));
                $contact_groups = Request::getArray("contact_groups");
                foreach ($contact_groups as $gruppe_id) {
                    $users = DBManager::get()->query(
                        "SELECT user_id " .
                        "FROM statusgruppe_user " .
                            "INNER JOIN statusgruppen ON (statusgruppe_user.statusgruppe_id = statusgruppen.statusgruppe_id) " .
                        "WHERE statusgruppen.range_id = ".DBManager::get()->quote($GLOBALS['user']->id)." " .
                            "AND statusgruppe_user.statusgruppe_id = ".DBManager::get()->quote($gruppe_id)." " .
                    "")->fetchAll(PDO::FETCH_COLUMN, 0);
                    foreach ($users as $user_id) {
                        $statement->execute(array(
                            'user_id' => $user_id,
                            'thread_id' => $thread->getId()
                        ));
                        //Meldung oder nicht Meldung, das ist hier die Frage.
                    }
                }
            }
            $factory = new Flexi_TemplateFactory($this->plugin->getPluginPath()."/views");
            $template = $factory->open("streams/thread.php");
            $template->set_attribute('thread', $thread);
            $template->set_attribute('controller', $this);
            $output['content'] = studip_utf8encode($template->render());
            $output['mkdate'] = time();
            $output['posting_id'] = $thread->getId();
        }
        $this->render_json($output);
    }

    public function get_source_action() {
        $posting = new BlubberPosting(Request::get("topic_id"));
        $thread = new BlubberPosting($posting['root_id']);
        if (($thread['context_type'] === "course" && !$GLOBALS['perm']->have_studip_perm("autor", $posting['Seminar_id'])) 
                or ($thread['context_type'] === "private" && !$thread->isRelated())) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        echo studip_utf8encode(forum_kill_edit($posting['description']));
        $this->render_nothing();
    }

    public function edit_posting_action () {
        $posting = new BlubberPosting(Request::get("topic_id"));
        $thread = new BlubberPosting($posting['root_id']);
        if (($posting['user_id'] !== $GLOBALS['user']->id) 
                && (!$GLOBALS['perm']->have_studip_perm("tutor", $posting['Seminar_id']))) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        $old_content = $posting['description'];
        $messaging = new messaging();
        BlubberPosting::$mention_thread_id = $thread->getId();
        StudipTransformFormat::addStudipMarkup("mention1", '@\"[^\n\"]*\"', "", "BlubberPosting::mention");
        StudipTransformFormat::addStudipMarkup("mention2", '@[^\s]*[\d\w_]+', "", "BlubberPosting::mention");
        $new_content = transformBeforeSave(studip_utf8decode(Request::get("content")));
        //$new_content = preg_replace("/(@\"[^\n\"]*\")/e", "BlubberPosting::mention('\\1', '".$thread->getId()."')", $new_content);
        //$new_content = preg_replace("/(@[^\s]+)/e", "BlubberPosting::mention('\\1', '".$thread->getId()."')", $new_content);
        
        if ($new_content && $old_content !== $new_content) {
            $posting['description'] = $new_content;
            if ($posting['topic_id'] === $posting['root_id']) {
                if (strpos($new_content, "\n") !== false) {
                    $posting['name'] = substr($new_content, 0, strpos($new_content, "\n"));
                } else {
                    if (strlen($new_content) > 255) {
                        $posting['name'] = "";
                    } else {
                        $posting['name'] = $new_content;
                    }
                }
            }
            $posting->store();
            if ($posting['user_id'] !== $GLOBALS['user']->id) {
                $messaging->insert_message(
                    sprintf(
                        _("%s hat als Moderator gerade Ihren Beitrag im Blubberforum editiert.\n\nDie alte Version des Beitrags lautete:\n\n%s\n\nDie neue lautet:\n\n%s\n"),
                        get_fullname(), $old_content, $posting['description']
                    ),
                    get_username($posting['user_id']),
                    $GLOBALS['user']->id,
                    null, null, null, null,
                    _("Änderungen an Ihrem Posting.")
                );
            }
        } elseif(!$new_content) {
            if ($posting['user_id'] !== $GLOBALS['user']->id) {
                $messaging->insert_message(
                    sprintf(
                        _("%s hat als Moderator gerade Ihren Beitrag im Blubberforum GELÖSCHT.\n\nDer alte Beitrag lautete:\n\n%s\n"),
                        get_fullname(), $old_content
                    ),
                    get_username($posting['user_id']),
                    $GLOBALS['user']->id,
                    null, null, null, null,
                    _("Ihr Posting wurde gelöscht.")
                );
            }
            $posting->delete();
        }
        BlubberPosting::$course_hashes = ($thread['user_id'] !== $thread['Seminar_id'] ? $thread['Seminar_id'] : false);
        $this->render_text(studip_utf8encode(BlubberPosting::format($posting['description'])));
    }

    public function refresh_posting_action() {
        $posting = new BlubberPosting(Request::get("topic_id"));
        $thread = new BlubberPosting($posting['root_id']);
        if (($thread['context_type'] === "course" && !$GLOBALS['perm']->have_studip_perm("autor", $posting['Seminar_id'])) 
                or ($thread['context_type'] === "private" && !$thread->isRelated())) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        BlubberPosting::$course_hashes = ($thread['context_type'] === "course" ? $thread['Seminar_id'] : false);
        $this->render_text(studip_utf8encode(BlubberPosting::format($posting['description'])));
    }

    public function comment_action() {
        $context = Request::option("context");
        $thread = new BlubberPosting(Request::option("thread"));
        if ($thread['context_type'] === "course") {
            $seminar = new Seminar($context);
            if ($seminar->write_level > 0 && !$GLOBALS['perm']->have_studip_perm("autor", $context)) {
                throw new AccessDeniedException("Kein Zugriff");
            }
        }
        BlubberPosting::$course_hashes = ($thread['context_type'] === "course" ? $thread['Seminar_id'] : false);
        if (Request::option("thread") && $thread['Seminar_id'] === $context) {
            $output = array();
            $posting = new BlubberPosting();
            
            BlubberPosting::$mention_thread_id = $thread->getId();
            StudipTransformFormat::addStudipMarkup("mention1", '@\"[^\n\"]*\"', "", "BlubberPosting::mention");
            StudipTransformFormat::addStudipMarkup("mention2", '@[^\s]*[\d\w_]+', "", "BlubberPosting::mention");
            $content = transformBeforeSave(studip_utf8decode(Request::get("content")));
            
            //mentions einbauen:
            $content = preg_replace("/(@\"[^\n\"]*\")/e", "BlubberPosting::mention('\\1', '".$thread->getId()."')", $content);
            $content = preg_replace("/(@[^\s]+)/e", "BlubberPosting::mention('\\1', '".$thread->getId()."')", $content);
            
            $posting['description'] = $content;
            $posting['context_type'] = $thread['context_type'];
            $posting['seminar_id'] = $thread['Seminar_id'];
            $posting['root_id'] = $posting['parent_id'] = Request::option("thread");
            $posting['name'] = "Re: ".$thread['name'];
            if ($GLOBALS['user']->id !== "nobody") {
                $posting['user_id'] = $GLOBALS['user']->id;
            } else {
                if (Request::get("anonymous_security") === $_SESSION['blubber_anonymous_security']) {
                    $contact_user = BlubberExternalContact::findByEmail(Request::get("anonymous_email"));
                    $_SESSION['anonymous_email'] = Request::get("anonymous_email");
                    $_SESSION['anonymous_name'] = $contact_user['name'] = Request::get("anonymous_name");
                    $contact_user->store();
                    $posting['user_id'] = $contact_user->getId();
                    $posting['external_contact'] = 1;
                } else {
                    throw new AccessDeniedException("No permission to write posting.");
                }
            }
            $posting['author_host'] = $_SERVER['REMOTE_ADDR'];
            if ($posting->store()) {
                $factory = new Flexi_TemplateFactory($this->plugin->getPluginPath()."/views/streams");
                $template = $factory->open("comment.php");
                $template->set_attribute('posting', $posting);
                $template->set_attribute('course_id', $thread['Seminar_id']);
                $output['content'] = studip_utf8encode($template->render($template->render()));
                $output['mkdate'] = time();
                $output['posting_id'] = $posting->getId();
                
                //Notifications:
                if (class_exists("PersonalNotifications")) {
                    $user_ids = array();
                    if ($thread['user_id'] && $thread['user_id'] !== $GLOBALS['user']->id) {
                        $user_ids[] = $thread['user_id'];
                    }
                    foreach ((array) $thread->getChildren() as $comment) {
                        if ($comment['user_id'] && ($comment['user_id'] !== $GLOBALS['user']->id) && (!$comment['external_contact'])) {
                            $user_ids[] = $comment['user_id'];
                        }
                    }
                    $user_ids = array_unique($user_ids);
                    PersonalNotifications::add(
                        $user_ids,
                        PluginEngine::getURL(
                            $this->plugin,
                            array('cid' => $thread['context_type'] === "course" ? $thread['Seminar_id'] : null), 
                            "streams/thread/".$thread->getId()
                        ),
                        get_fullname()." hat einen Kommentar geschrieben",
                        "posting_".$posting->getId(),
                        Avatar::getAvatar($GLOBALS['user']->id)->getURL(Avatar::MEDIUM)
                    );
                }
            }
            $this->render_json($output);
        } else {
            $this->render_json(array(
                'error' => "Konnte thread nicht zuordnen."
            ));
        }
    }

    public function post_files_action() {
        $context = Request::option("context") ? Request::get("context") : $GLOBALS['user']->id;
        $context_type = Request::option("context_type");
        if (!Request::isPost()
                || ($context_type === "course" && !$GLOBALS['perm']->have_studip_perm("autor", $context))) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        //check folders
        $db = DBManager::get();
        $folder_id = md5("Blubber_".$context."_".$GLOBALS['user']->id);
        $parent_folder_id = md5("Blubber_".$context);
        if ($context_type !== "course") {
            $folder_id = $parent_folder_id;
        }
        $folder = $db->query(
            "SELECT * " .
            "FROM folder " .
            "WHERE folder_id = ".$db->quote($folder_id)." " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        if (!$folder) {
            $folder = $db->query(
                "SELECT * " .
                "FROM folder " .
                "WHERE folder_id = ".$db->quote($parent_folder_id)." " .
            "")->fetch(PDO::FETCH_COLUMN, 0);
            if (!$folder) {
                $db->exec(
                    "INSERT IGNORE INTO folder " .
                    "SET folder_id = ".$db->quote($parent_folder_id).", " .
                        "range_id = ".$db->quote($context).", " .
                        "user_id = ".$db->quote($GLOBALS['user']->id).", " .
                        "name = ".$db->quote("BlubberDateien").", " .
                        "permission = '7', " .
                        "mkdate = ".$db->quote(time()).", " .
                        "chdate = ".$db->quote(time())." " .
                "");
            }
            if ($context_type === "course") {
                $db->exec(
                    "INSERT IGNORE INTO folder " .
                    "SET folder_id = ".$db->quote($folder_id).", " .
                        "range_id = ".$db->quote($parent_folder_id).", " .
                        "user_id = ".$db->quote($GLOBALS['user']->id).", " .
                        "name = ".$db->quote(get_fullname()).", " .
                        "permission = '7', " .
                        "mkdate = ".$db->quote(time()).", " .
                        "chdate = ".$db->quote(time())." " .
                "");
            }
        }

        $output = array();

        foreach ($_FILES as $file) {
            $GLOBALS['msg'] = '';
            if ($context_type === "course") {
                validate_upload($file);
                if ($GLOBALS['msg']) {
                    $output['errors'][] = $file['name'] . ': ' . studip_utf8encode(html_entity_decode(trim(substr($GLOBALS['msg'],6), '§')));
                    continue;
                }
            }
            if ($file['size']) {
                $document['name'] = $document['filename'] = studip_utf8decode(strtolower($file['name']));
                $document['user_id'] = $GLOBALS['user']->id;
                $document['author_name'] = get_fullname();
                $document['seminar_id'] = $context;
                $document['range_id'] = $context_type === "course" ? $folder_id : $parent_folder_id;
                $document['filesize'] = $file['size'];
                if ($newfile = StudipDocument::createWithFile($file['tmp_name'], $document)) {
                    $type = null;
                    strpos($file['type'], 'image') === false || $type = "img";
                    strpos($file['type'], 'video') === false || $type = "video";
                    if (strpos($file['type'], 'audio') !== false || strpos($document['filename'], '.ogg') !== false) {
                         $type = "audio";
                    }
                    $url = GetDownloadLink($newfile->getId(), $newfile['filename']);
                    if ($type) {
                        $output['inserts'][] = "[".$type."]".$url;
                    } else {
                        $output['inserts'][] = "[".$newfile['filename']."]".$url;
                    }
                }
            }
        }
        $this->render_json($output);
    }
    
    public function thread_action($thread_id)
    {
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/autoresize.jquery.min.js"), "");
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/blubberforum.js"), "");
        PageLayout::addHeadElement("script", array('src' => $this->assets_url."/javascripts/formdata.js"), "");
        
        $this->thread = new BlubberPosting($thread_id);
        if ($this->thread['context_type'] === "course") {
            PageLayout::setTitle($GLOBALS['SessSemName']["header_line"]." - ".$this->plugin->getDisplayTitle());
        } elseif($this->thread['context_type'] === "public") {
            PageLayout::setTitle(get_fullname($this->thread['user_id'])." - Blubber");
        } elseif($this->thread['context_type'] === "private") {
            PageLayout::setTitle(_("Privater Blubber"));
        }

        if ($this->thread['context_type'] === "course") {
            Navigation::getItem("/course/blubberforum")->setImage($this->plugin->getPluginURL()."/assets/images/blubber.png");
            Navigation::activateItem('/course/blubberforum');
        } elseif($this->thread['context_type'] === "public") {
            Navigation::activateItem('/profile/blubber');
        } else {
            Navigation::activateItem('/community/blubber');
        }
        
        $this->course_id     = $_SESSION['SessionSeminar'];
        $this->single_thread = true;
        BlubberPosting::$course_hashes = ($thread['user_id'] !== $thread['Seminar_id'] ? $thread['Seminar_id'] : false);
    }

}