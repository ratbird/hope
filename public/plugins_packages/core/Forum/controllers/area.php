<?php

/*
 * Copyright (C) 2011 - Till Gl�ggler     <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class AreaController extends ForumController
{
    function add_action($category_id)
    {
        ForumPerm::check('add_area', $this->getId());

        $new_id = md5(uniqid(rand()));

        ForumEntry::insert(array(
            'topic_id'    => $new_id,
            'seminar_id'  => $this->getId(),
            'user_id'     => $GLOBALS['user']->id,
            'name'        => Request::get('name', _('Kein Titel')),
            'content'     => Request::get('content'),
            'author'      => get_fullname($GLOBALS['user']->id),
            'author_host' => getenv('REMOTE_ADDR')
        ), $this->getId());

        ForumCat::addArea($category_id, $new_id);

        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->entry = array_pop(ForumEntry::parseEntries(array(ForumEntry::getEntry($new_id))));
            $this->visitdate = ForumVisit::getLastVisit($this->getId());
        } else {
            $this->redirect(PluginEngine::getLink('coreforum/index/index/'));
        }
    }

    function edit_action($area_id)
    {
        ForumPerm::check('edit_area', $this->getId(), $area_id);

        if (Request::isAjax()) {
            ForumEntry::update($area_id, studip_utf8decode(Request::get('name')), studip_utf8decode(Request::get('content')));
            $this->render_nothing();
        } else {
            ForumEntry::update($area_id, Request::get('name'), Request::get('content'));
            $this->flash['messages'] = array('success' => _('Die �nderungen am Bereich wurden gespeichert.'));
            $this->redirect(PluginEngine::getLink('coreforum/index/index'));
        }

    }

    function save_order_action()
    {
        ForumPerm::check('sort_area', $this->getId());

        foreach (Request::getArray('areas') as $category_id => $areas) {
            $pos = 0;
            foreach ($areas as $area_id) {
                ForumPerm::checkCategoryId($this->getId(), $category_id);
                ForumPerm::check('sort_area', $this->getId(), $area_id);

                ForumCat::addArea($category_id, $area_id);
                ForumCat::setAreaPosition($area_id, $pos);
                $pos++;
            }
        }

        $this->render_nothing();
    }
}