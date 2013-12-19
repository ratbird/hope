<?php

/*
 * Copyright (C) 2011 - Till Gl�ggler     <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
require_once 'lib/classes/AdminModules.class.php';
require_once 'lib/classes/Config.class.php';

class IndexController extends ForumController
{
    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /*  V   I   E   W   -   A   C   T   I   O   N   S  */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    function enter_seminar_action() {
        if (ForumPerm::has('fav_entry', $this->getId())
            && ForumVisit::getCount($this->getId(), ForumVisit::getVisit($this->getId())) > 0) {
            $this->redirect(PluginEngine::getLink('coreforum/index/newest'));
        } else {
            $this->redirect(PluginEngine::getLink('coreforum/index/index'));
        }
    }

    /**
     * the main action for the forum. May be called with a topic_id to be displayed
     * and optionally the page to display
     * 
     * @param type $topic_id the topic to display, defaults to the main
     *                       view of the current seminar
     * @param type $page the page to be displayed (for thread-view)
     */
    function index_action($topic_id = null, $page = null)
    {
        $nav = Navigation::getItem('course/forum2');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum2/index');

        // check, if the root entry is present
        ForumEntry::checkRootEntry($this->getId());

        /* * * * * * * * * * * * * * * * * * *
         * V A R I A B L E N   F U E L L E N *
         * * * * * * * * * * * * * * * * * * */

        $this->section   = 'index';

        $this->topic_id   = $topic_id ? $topic_id : $this->getId();
        $this->constraint = ForumEntry::getConstraints($this->topic_id);
        
        // check if there has been submitted an invalid id and use seminar_id in case
        if (!$this->constraint) {
            $this->topic_id   = $this->getId();
            $this->constraint = ForumEntry::getConstraints($this->topic_id);
        }

        $this->highlight_topic = Request::option('highlight_topic', null);

        // set page to which we shall jump
        if ($page) {
            ForumHelpers::setPage($page);
        }

        // we do not crawl deeper than level 2, we show a page chooser instead
        if ($this->constraint['depth'] > 2) {
            ForumHelpers::setPage(ForumEntry::getPostingPage($this->topic_id, $this->constraint));

            $path               = ForumEntry::getPathToPosting($this->topic_id);
            array_shift($path);array_shift($path);$path_element = array_shift($path);
            $this->child_topic  = $this->topic_id;
            $this->topic_id     = $path_element['id'];
            $this->constraint   = ForumEntry::getConstraints($this->topic_id);
        }

        // check if the topic_id matches the currently selected seminar
        ForumPerm::checkTopicId($this->getId(), $this->topic_id);
        

        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * B E R E I C H E / T H R E A D S / P O S T I N G S   L A D E N *
         * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

        // load list of areas for use in thread-movement
        if (ForumPerm::has('move_thread', $this->getId())) {
            $this->areas = ForumEntry::getList('flat', $this->getId());
        }

        if ($this->constraint['depth'] > 1) {   // POSTINGS
            $list = ForumEntry::getList('postings', $this->topic_id);
            if (!empty($list['list'])) {
                $this->postings          = $list['list'];
                $this->number_of_entries = $list['count'];
            }
        } else {
            if ($this->constraint['depth'] == 0) {  // BEREICHE
                $list = ForumEntry::getList('area', $this->topic_id);
            } else {
                $list = ForumEntry::getList('list', $this->topic_id);
            }

            if ($this->constraint['depth'] == 0) {  // BEREICHE
                $new_list = array();
                // iterate over all categories and add the belonging areas to them
                foreach ($categories = ForumCat::getListWithAreas($this->getId(), false) as $category) {
                    if ($category['topic_id']) {
                        $new_list[$category['category_id']][$category['topic_id']] = $list['list'][$category['topic_id']];
                        unset($list['list'][$category['topic_id']]);
                    } else if (ForumPerm::has('add_area', $this->seminar_id)) {
                        $new_list[$category['category_id']] = array();
                    }
                    $this->categories[$category['category_id']] = $category['entry_name'];
                }

                if (!empty($list['list'])) {
                    // append the remaining entries to the standard category
                    $new_list[$this->getId()] = array_merge((array)$new_list[$this->getId()], $list['list']);
                }

                // check, if there are any orphaned entries
                foreach ($new_list as $key1 => $list_item) {
                    foreach ($list_item as $key2 => $contents) {
                        if (empty($contents)) {
                            // remove the orphaned entry from the list and from the database
                            unset($new_list[$key1][$key2]); 
                            ForumCat::removeArea($key2);
                        }
                    }
                }

                $this->list = $new_list;
                
            } else if ($this->constraint['depth'] == 1) {   // THREADS
                if (!empty($list['list'])) {
                    $this->list = array($list['list']);
                }
            }
            $this->number_of_entries = $list['count'];
        }

        // set the visit-date and get the stored last_visitdate
        $this->visitdate = ForumVisit::getLastVisit($this->getId());
        
        $this->seminar_id = $this->getId();

        // highlight text if passed some words to highlight
        if (Request::getArray('highlight')) {
            $this->highlight = Request::optionArray('highlight');
        }
    }

    function newest_action($page = null)
    {
        $nav = Navigation::getItem('course/forum2');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum2/newest');
        
        // set page to which we shall jump
        if ($page) {
            ForumHelpers::setPage($page);
        }

        $this->section = 'newest';
        $this->topic_id = $this->getId();

        // set the visitdate of the seminar as the last visitdate
        $this->visitdate = ForumVisit::getLastVisit($this->getId());

        $list = ForumEntry::getList('newest', $this->topic_id);
        $this->postings          = $list['list'];
        $this->number_of_entries = $list['count'];
        $this->show_full_path    = true;

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        if (empty($this->postings)) {
            $this->no_entries = true;
        }

        $this->render_action('index');
    }
    

    function latest_action($page = null)
    {
        $nav = Navigation::getItem('course/forum2');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum2/latest');
        
        // set page to which we shall jump
        if ($page) {
            ForumHelpers::setPage($page);
        }

        $this->section = 'latest';
        $this->topic_id = $this->getId();

        // set the visitdate of the seminar as the last visitdate
        $this->visitdate = ForumVisit::getLastVisit($this->getId());

        $list = ForumEntry::getList('latest', $this->topic_id);
        $this->postings          = $list['list'];
        $this->number_of_entries = $list['count'];
        $this->show_full_path    = true;

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        if (empty($this->postings)) {
            $this->no_entries = true;
        }

        $this->render_action('index');
    }

    function favorites_action($page = null)
    {
        $nav = Navigation::getItem('course/forum2');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum2/favorites');

        // set page to which we shall jump
        if ($page) {
            ForumHelpers::setPage($page);
        }

        $this->section = 'favorites';
        $this->topic_id = $this->getId();

        $list = ForumEntry::getList('favorites', $this->topic_id);
        $this->postings          = $list['list'];
        $this->number_of_entries = $list['count'];
        $this->show_full_path    = true;

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        if (empty($this->postings)) {
            $this->no_entries = true;
        }

        // exploit the visitdate for this view
        $this->visitdate = ForumVisit::getLastVisit($this->getId());

        $this->render_action('index');
    }

    function search_action($page = null)
    {
        ForumPerm::check('search', $this->getId());
        
        $nav = Navigation::getItem('course/forum2');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum2/index');

        // set page to which we shall jump
        if ($page) {
            ForumHelpers::setPage($page);
        }

        $this->section = 'search';
        $this->topic_id = $this->getId();
        $this->show_full_path    = true;

        // parse filter-options
        foreach (array('search_title', 'search_content', 'search_author') as $option) {
            $this->options[$option] = Request::option($option);
        }
        
        $this->searchfor = Request::get('searchfor');
        if (strlen($this->searchfor) < 3) {
            $this->flash['messages'] = array('error' => _('Ihr Suchbegriff muss mindestens 3 Zeichen lang sein und darf nur Buchstaben und Zahlen enthalten!'));
        } else {
            // get search-results
            $list = ForumEntry::getSearchResults($this->getId(), $this->searchfor, $this->options);

            $this->postings          = $list['list'];
            $this->number_of_entries = $list['count'];
            $this->highlight         = $list['highlight'];

            if (empty($this->postings)) {
                $this->flash['messages'] = array('info' => _('Es wurden keine Beitr�ge gefunden, die zu Ihren Suchkriterien passen!'));
            }
        }

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        // exploit the visitdate for this view
        $this->visitdate = ForumVisit::getLastVisit($this->getId());

        $this->render_action('index');
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * *   P O S T I N G - A C T I O N S     * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * this action renders a preview of the submitted text
     */
    function preview_action() {
        if (Request::isAjax()) {
            $this->set_content_type('text/html; charset=UTF-8');
            $this->render_text(studip_utf8encode(formatReady(transformBeforeSave(studip_utf8decode(Request::get('posting'))))));
        } else {
            $this->render_text(formatReady(ForumEntry::parseEdit(transformBeforeSave(Request::get('posting')))));
        }
    }

    function add_entry_action()
    {
        // Schutz vor Spambots - diese f�llen meistens alle Felder aus, auch "versteckte".
        // Ist dieses Feld gef�llt, war das vermutlich kein Mensch
        if (Request::get('nixda')) {
            throw new Exception('Access denied!');
        }

        if (!$parent_id = Request::option('parent')) {
            throw new Exception('missing seminar_id/topic_id while adding a new entry!');
        }
        
        ForumPerm::check('add_entry', $this->getId(), $parent_id);
        
        $constraints = ForumEntry::getConstraints($parent_id);
        
        // if we are answering/citing a posting, we want to add it to the thread
        // (which is the parent of passed posting id)
        if ($constraints['depth'] == 3) {
            $parent_id = ForumEntry::getParentTopicId($parent_id);
        }

        $new_id = md5(uniqid(rand()));

        if ($GLOBALS['user']->id == 'nobody') {
            $fullname = Request::get('author', 'unbekannt');
        } else {
            $fullname = get_fullname($GLOBALS['user']->id);
        }
        
        ForumEntry::insert(array(
            'topic_id'    => $new_id,
            'seminar_id'  => $this->getId(),
            'user_id'     => $GLOBALS['user']->id,
            'name'        => Request::get('name') ?: '',
            'content'     => Request::get('content'),
            'author'      => $fullname,
            'author_host' => getenv('REMOTE_ADDR'),
            'anonymous'   => Config::get()->FORUM_ANONYMOUS_POSTINGS ? Request::get('anonymous') ? : 0 : 0
        ), $parent_id);

        $this->flash['notify'] = $new_id;

        $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $new_id .'#'. $new_id));
    }

    function delete_entry_action($topic_id)
    {
        // get the page of the posting to be able to jump there again
        $page = ForumEntry::getPostingPage($topic_id);
        URLHelper::addLinkParam('page', $page);
        
        if (ForumPerm::hasEditPerms($topic_id) || ForumPerm::check('remove_entry', $this->getId(), $topic_id)) {
            $path = ForumEntry::getPathToPosting($topic_id);
            $topic  = array_pop($path);
            $parent = array_pop($path);

            if ($topic_id != $this->getId()) {
                // only delete directly if passed by ajax, otherwise ask for confirmation
                if (Request::isXhr() || Request::get('approve_delete')) {
                    ForumEntry::delete($topic_id);
                    $this->flash['messages'] = array('success' => sprintf(_('Der Eintrag %s wurde gel�scht!'), $topic['name']));
                } else {
                    $this->flash['messages'] = array('info_html' => 
                        sprintf(_('Sind sie sicher dass Sie den Eintrag %s l�schen m�chten?'), $topic['name'])
                        . '<br>'. \Studip\LinkButton::createAccept(_('Ja'), PluginEngine::getUrl('coreforum/index/delete_entry/'. $topic_id .'?approve_delete=1'))
                        . \Studip\LinkButton::createCancel(_('Nein'), PluginEngine::getUrl('coreforum/index/index/'. ForumEntry::getParentTopicId($topic_id) .'/'. $page))
                    );
                }
            } else {
                $this->flash['messages'] = array('success' => _('Sie k�nnen nicht die gesamte Veranstaltung l�schen!'));
            }
        }

        if (Request::isAjax()) {
            $this->render_template('messages');
            $this->flash['messages'] = null;
        } else {
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $parent['id'] .'/'. $page));
        }
    }

    function update_entry_action($topic_id)
    {
        $name    = studip_utf8decode(Request::get('name', _('Kein Titel')));
        $content = studip_utf8decode(Request::get('content', _('Keine Beschreibung')));

        ForumPerm::check('add_entry', $this->getId(), $topic_id);

        if (ForumPerm::hasEditPerms($topic_id)) {
            ForumEntry::update($topic_id, $name, $content);
        } else {
            $this->flash['messages']['error'] = 'Keine Berechtigung!';
            $this->render_template('messages');
            return;
        }

        if (Request::isXhr()) {
            $this->render_text(json_encode(array(
                'name'    => studip_utf8encode(htmlReady($name)),
                'content' => studip_utf8encode(formatReady($content))
            )));
        } else {
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $topic_id .'#'. $topic_id));
        }
    }

    function move_thread_action($thread_id, $destination) {
        ForumPerm::check('move_thread', $this->getId(), $thread_id);
        ForumPerm::check('move_thread', $this->getId(), $destination);

        ForumEntry::move($thread_id, $destination);

        $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $thread_id .'#'. $thread_id));
    }

    function set_favorite_action($topic_id)
    {
        ForumPerm::check('fav_entry', $this->getId(), $topic_id);
        
        ForumFavorite::set($topic_id);
        
        if (Request::isXhr()) {
            $this->topic_id = $topic_id;
            $this->favorite = true;
            $this->render_template('index/_favorite');
        } else {
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $topic_id .'#'. $topic_id));
        }
    }
    
    function unset_favorite_action($topic_id) {
        ForumPerm::check('fav_entry', $this->getId(), $topic_id);
        
        ForumFavorite::remove($topic_id);
        
        if (Request::isXhr()) {
            $this->topic_id = $topic_id;
            $this->favorite = false;
            $this->render_template('index/_favorite');
        } else {
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $topic_id .'#'. $topic_id));
        }
    }

    function goto_page_action($topic_id, $section, $page)
    {
        switch ($section) {
            case 'index':
                $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $topic_id .'/'. (int)$page .'#'. $topic_id));
                break;

            case 'search':
                $optionlist = array();

                foreach (array('search_title', 'search_content', 'search_author') as $option) {
                    if (Request::option($option)) {
                        $optionlist[] = $option .'='. 1;
                    }
                }

                $this->redirect(PluginEngine::getURL('coreforum/index/'. $section .'/'. (int)$page 
                    .'/?searchfor='. Request::get('searchfor') .'&'. implode('&', $optionlist)));
                break;

            default:
                $this->redirect(PluginEngine::getLink('coreforum/index/'. $section .'/'. (int)$page));
                break;
        }
    }

    function like_action($topic_id)
    {
        ForumPerm::check('like_entry', $this->getId(), $topic_id);
        
        ForumLike::like($topic_id);

        if (Request::isAjax()) {
            $this->topic_id   = $topic_id;
            $this->render_template('index/_like');
        } else {
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $topic_id .'#'. $topic_id));
        }
    }

    function dislike_action($topic_id)
    {
        ForumPerm::check('like_entry', $this->getId(), $topic_id);

        ForumLike::dislike($topic_id);
        
        if (Request::isAjax()) {
            $this->topic_id   = $topic_id;
            $this->render_template('index/_like');
        } else {
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $topic_id .'#'. $topic_id));
        }
    }
    
    /**
     * This action is used to close a thread.
     * 
     * @param string $topic_id the topic which will be closed
     * @param string $redirect the topic which will be shown after closing the thread
     * @param int    $page the page number of the topic $redirect
     */
    function close_thread_action($topic_id, $redirect, $page = 0)
    {
        ForumPerm::check('close_thread', $this->getId(), $topic_id);
        
        ForumEntry::close($topic_id);
        
        $success_text = _('Das Thema wurde erfolgreich geschlossen.');

        if (Request::isAjax()) {
            $this->render_text(MessageBox::success($success_text));
        } else {
            $this->flash['messages'] = array('success' => $success_text);
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $redirect . '/' . $page));
        }
    }
    
    /**
     * This action is used to open a thread.
     * 
     * @param string $topic_id the topic which will be opened
     * @param string $redirect the topic which will be shown after opening the thread
     * @param int    $page the page number of the topic $redirect
     */
    function open_thread_action($topic_id, $redirect, $page = 0)
    {
        ForumPerm::check('close_thread', $this->getId(), $topic_id);
        
        ForumEntry::open($topic_id);
        
        $success_text = _('Das Thema wurde erfolgreich ge�ffnet.');

        if (Request::isAjax()) {
            $this->render_text(MessageBox::success($success_text));
        } else {
            $this->flash['messages'] = array('success' => $success_text);
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $redirect . '/' . $page));
        }
    }
    
    /**
     * This action is used to mark a thread as sticky.
     * 
     * @param string $topic_id the topic which will be marked as sticky.
     * @param string $redirect the topic which will be shown afterwards
     * @param int    $page the page number of the topic $redirect
     */
    function make_sticky_action($topic_id, $redirect, $page = 0)
    {
        ForumPerm::check('make_sticky', $this->getId(), $topic_id);
        
        ForumEntry::sticky($topic_id);
        
        $success_text = _('Das Thema wurde erfolgreich in der Themenliste hervorgehoben.');

        if (Request::isAjax()) {
            $this->render_text(MessageBox::success($success_text));
        } else {
            $this->flash['messages'] = array('success' => $success_text);
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $redirect . '/' . $page));
        }
    }
    
    /**
     * This action is used to remove the sticky attribute from a topic.
     * 
     * @param string $topic_id the topic which will be marked as unsticky.
     * @param string $redirect the topic which will be shown afterwards
     * @param int    $page the page number of the topic $redirect
     */
    function make_unsticky_action($topic_id, $redirect, $page = 0)
    {
        ForumPerm::check('make_sticky', $this->getId(), $topic_id);
        
        ForumEntry::unsticky($topic_id);
        
        $success_text = _('Die Hervorhebung des Themas in der Themenliste wurde entfernt.');

        if (Request::isAjax()) {
            $this->render_text(MessageBox::success($success_text));
        } else {
            $this->flash['messages'] = array('success' => $success_text);
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $redirect . '/' . $page));
        }
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * *     C O N F I G - A C T I O N S     * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    function cite_action($topic_id)
    {
        ForumPerm::check('add_entry', $this->getId(), $topic_id);

        $topic = ForumEntry::getConstraints($topic_id);

        $this->flash['edit_entry'] = true;
        $this->flash['new_entry_title'] = $topic['name'];
        $this->flash['new_entry_content'] = "[quote=". ($topic['anonymous'] ? _('Anonym') : $topic['author']) ."]\n" . $topic['content'] . "\n[/quote]\n\n";

        $this->redirect(PluginEngine::getLink('coreforum/index/index/'. $topic_id .'#create'));
    }

    function new_entry_action($topic_id)
    {
        ForumPerm::check('add_entry', $this->getId(), $topic_id);

        $this->flash['edit_entry'] = true;
        $this->redirect(PluginEngine::getLink('coreforum/index/index/'. $topic_id .'#create'));
    }

    function add_category_action()
    {
        ForumPerm::check('add_category', $this->getId());

        $category_id = ForumCat::add($this->getId(), Request::get('category'));
        
        ForumPerm::checkCategoryId($this->getId(), $category_id);
                    
        $this->redirect(PluginEngine::getLink('coreforum/index#cat_'. $category_id));
    }

    function remove_category_action($category_id)
    {
        ForumPerm::checkCategoryId($this->getId(), $category_id);
        ForumPerm::check('remove_category', $this->getId());
        
        $this->flash['messages'] = array('success' => _('Die Kategorie wurde gel�scht!'));
        ForumCat::remove($category_id, $this->getId());

        if (Request::isAjax()) {
            $this->render_template('messages');
        } else {
            $this->redirect(PluginEngine::getLink('coreforum/index/index'));
        }

    }

    function edit_category_action($category_id) {
        ForumPerm::checkCategoryId($this->getId(), $category_id);
        ForumPerm::check('edit_category', $this->getId());
        
        if (Request::isAjax()) {
            ForumCat::setName($category_id, studip_utf8decode(Request::get('name')));
            $this->render_nothing();
        } else {
            ForumCat::setName($category_id, Request::get('name'));
            $this->flash['messages'] = array('success' => _('Der Name der Kategorie wurde ge�ndert.'));
            $this->redirect(PluginEngine::getLink('coreforum/index/index#cat_' . $category_id));
        }

    }

    function savecats_action()
    {
        ForumPerm::check('sort_category', $this->getId());

        $pos = 0;
        foreach (Request::getArray('categories') as $category_id) {
            ForumPerm::checkCategoryId($this->getId(), $category_id);
            ForumCat::setPosition($category_id, $pos);
            $pos++;
        }

        $this->render_nothing();
    }

    function abo_action($topic_id)
    {
        ForumPerm::check('abo', $this->getId(), $topic_id);
            
        ForumAbo::add($topic_id);
        $this->constraint = ForumEntry::getConstraints($topic_id);

        if (Request::isXhr()) {
            $this->render_template('index/_abo_link');
        } else {
            switch ($constraint['depth']) {
                case 0:  $msg = _('Sie haben das gesamte Forum abonniert!');break;
                case 1:  $msg = _('Sie haben diesen Bereich abonniert.');break;
                default: $msg = _('Sie haben dieses Thema abonniert');break;
            }
            $this->flash['messages'] = array('success' => $msg .' '. _('Sie werden nun �ber jeden neuen Beitrag informiert.'));
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $topic_id));
        }
    }

    function remove_abo_action($topic_id)
    {
        ForumPerm::check('abo', $this->getId(), $topic_id);

        ForumAbo::delete($topic_id);
        
        if (Request::isXhr()) {
            $this->constraint = ForumEntry::getConstraints($topic_id);
            $this->render_template('index/_abo_link');
        } else {
            $this->flash['messages'] = array('success' => _('Ihr Abonnement wurde aufgehoben.'));
            $this->redirect(PluginEngine::getLink('coreforum/index/index/' . $topic_id));
        }
    }

    function pdfexport_action($parent_id = null)
    {
        ForumPerm::check('pdfexport', $this->getId(), $parent_id);
        
        ForumHelpers::createPDF($this->getId(), $parent_id);
    }
}