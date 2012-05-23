<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
 * siteinfo - display information about Stud.IP
 *
 * Copyright (c) 2008  Ansgar Bockstiegel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/models/siteinfo.php';
require_once 'app/controllers/studip_controller.php';

class SiteinfoController extends StudipController
{
    private $si;

    /**
     * common tasks for all actions
     */
    function before_filter (&$action, &$args)
    {
        global $perm, $template_factory;

        # open session
        page_open(array('sess' => 'Seminar_Session',
                        'auth' => 'Seminar_Default_Auth',
                        'perm' => 'Seminar_Perm',
                        'user' => 'Seminar_User'));

        // set up user session
        include 'lib/seminar_open.php';

        // allow only "word" characters in arguments
        $this->validate_args($args);

        //Siteinfo-Class is defined in models/siteinfo.php
        $this->si = new Siteinfo();

        $this->populate_ids($args);
        $this->add_navigation($action);

        //if the user has root-permissions the infobox with edit-links should be displayed
        if ($perm->have_perm('root')) {
            $this->layout = $template_factory->open('layouts/base');
            $this->layout->set_attribute('infobox', $this->infobox_content());
        } else {
            $action = "show";
            $this->layout = $template_factory->open('layouts/base_without_infobox');
        }
        $this->set_layout($this->layout);
        PageLayout::setTitle(_('Impressum'));
        PageLayout::setTabNavigation('/footer/siteinfo');
    }

    function populate_ids($args)
    {
        //the first element of the unconsumed trails-path determines the rubric
        //the second element defines the page(detail)
        //if they are missing the first detail/rubric is the fallback
        if (isset($args[0]) && is_numeric($args[0])) {
            $this->currentrubric = $args[0];
            if (isset($args[1]) && is_numeric($args[1])) {
                $this->currentdetail = $args[1];
            } else {
                $this->currentdetail = $this->si->first_detail_id($args[0]);
            }
        } else {
            $this->currentrubric = $this->si->first_rubric_id();
            $this->currentdetail = $this->si->first_detail_id();
        }
    }

    function add_navigation($action)
    {
        foreach ($this->si->get_all_rubrics() as $rubric) {
            $rubric[1] = language_filter($rubric[1]);
            if ($rubric[1] == '') {
                $rubric[1] = _('unbenannt');
            }
            Navigation::addItem('/footer/siteinfo/'.$rubric[0],
                new Navigation($rubric[1], $this->url_for('siteinfo/show/'.$rubric[0])));
        }

        foreach ($this->si->get_all_details() as $detail) {
            $detail[2] = language_filter($detail[2]);
            if ($detail[2] == '') {
                $detail[2] = _('unbenannt');
            }
            Navigation::addItem('/footer/siteinfo/'.$detail[1].'/'.$detail[0],
                new Navigation($detail[2], $this->url_for('siteinfo/show/'.$detail[1].'/'.$detail[0])));
        }

        if ($action != 'new') {
            if ($this->currentdetail > 0) {
                Navigation::activateItem('/footer/siteinfo/'.$this->currentrubric.'/'.$this->currentdetail);
            } else {
                Navigation::activateItem('/footer/siteinfo/'.$this->currentrubric);
            }
        }
    }

    function infobox_content()
    {
        global $rubrics_empty;
        if (!$rubrics_empty) {
            if ($this->currentrubric > 0) {
                $infobox_actions[] = array('icon' => 'icons/16/black/plus.png',
                                           'text' => '<a href="'.$this->url_for('siteinfo/new/'.$this->currentrubric).'">'._('Neue Seite anlegen').'</a>');
            }
            if ($this->currentdetail > 0) {
                $infobox_actions[] = array('icon' => 'icons/16/black/edit.png',
                                           'text' => '<a href="'.$this->url_for('siteinfo/edit/'.$this->currentrubric.'/'.$this->currentdetail).'">'._('Seite bearbeiten').'</a>');
                $infobox_actions[] = array('icon' => 'icons/16/black/trash.png',
                                           'text' => '<a href="'.$this->url_for('siteinfo/delete/'.$this->currentrubric.'/'.$this->currentdetail).'">'._('Seite löschen').'</a>');
            }
        }
        $infobox_actions[] = array('icon' => 'icons/16/black/plus.png',
                                   'text' => '<a href="'.$this->url_for('siteinfo/new').'">'._('Neue Rubrik anlegen').'</a>');
        if ($this->currentrubric > 0) {
            $infobox_actions[] = array('icon' => 'icons/16/black/edit.png',
                                       'text' => '<a href="'.$this->url_for('siteinfo/edit/'.$this->currentrubric).'">'._('Rubrik bearbeiten').'</a>');
            $infobox_actions[] = array('icon' => 'icons/16/black/trash.png',
                                       'text' => '<a href="'.$this->url_for('siteinfo/delete/'.$this->currentrubric).'">'._('Rubrik löschen').'</a>');
        }
        return array('picture' => 'infobox/impressum.jpg',
                     'content' => array(array('kategorie' => _("Administration des Impressums"),
                                              'eintrag' => $infobox_actions))
                    );
    }

    /**
     * common tasks for all actions
     */
    function after_filter ($action, $args)
    {
        page_close();
    }

    /**
     * Display the siteinfo
     */
    function show_action ()
    {
        $this->output = $this->si->get_detail_content_processed($this->currentdetail);
    }

    function new_action ($givenrubric=NULL)
    {
        if($givenrubric===NULL){
            Navigation::addItem('/footer/siteinfo/rubric_new',
                new AutoNavigation(_('Neue Rubrik'), $this->url_for('siteinfo/new')));
            $this->edit_rubric = TRUE;
        } else {
            Navigation::addItem('/footer/siteinfo/'.$this->currentrubric.'/detail_new',
                new AutoNavigation(_('Neue Seite'), $this->url_for('siteinfo/new/'.$this->currentrubric)));
            $this->rubrics = $this->si->get_all_rubrics();
        }
    }

    function edit_action ($givenrubric=NULL, $givendetail=NULL)
    {
        if (is_numeric($givendetail)) {
            $this->rubrics = $this->si->get_all_rubrics();
            $this->rubric_id = $this->si->rubric_for_detail($this->currentdetail);
            $this->detail_name = $this->si->get_detail_name($this->currentdetail);
            $this->content = $this->si->get_detail_content($this->currentdetail);
        } else {
            $this->edit_rubric = TRUE;
            $this->rubric_id = $this->currentrubric;
       }
        $this->rubric_name = $this->si->rubric_name($this->currentrubric);
    }

    function save_action ()
    {
        $detail_name = remove_magic_quotes($_POST['detail_name']);
        $rubric_name = remove_magic_quotes($_POST['rubric_name']);
        $content = remove_magic_quotes($_POST['content']);
        if (isset($_POST['rubric_id'])) {
            $rubric_id = (int) $_POST['rubric_id'];
            if (isset($_POST['detail_id'])) {
                $detail_id = (int) $_POST['detail_id'];
                list($rubric, $detail) = $this->si->save("update_detail", array("rubric_id" => $rubric_id,
                                                                                "detail_name" => $detail_name,
                                                                                "content" => $content,
                                                                                "detail_id" => $detail_id));
            } else {
                if (isset($_POST['content'])) {
                list($rubric, $detail) = $this->si->save("insert_detail", array("rubric_id" => $rubric_id,
                                                                                "detail_name" => $detail_name,
                                                                                "content" => $content));
                } else {
                    list($rubric, $detail) = $this->si->save("update_rubric", array("rubric_id" => $rubric_id,
                                                                         "rubric_name" => $rubric_name));
                }
            }
        } else {
            list($rubric, $detail) = $this->si->save("insert_rubric", array("rubric_name" => $rubric_name));
        }
        $this->redirect('siteinfo/show/'.$rubric.'/'.$detail);
    }

    function delete_action ($givenrubric=NULL, $givendetail=NULL, $execute=FALSE)
    {
        if ($execute) {
            if ($givendetail == "all") {
                $this->si->delete("rubric", $this->currentrubric);
                $this->redirect('siteinfo/show/');
            } else {
                $this->si->delete("detail", $this->currentdetail);
                $this->redirect('siteinfo/show/'.$this->currentrubric);
            }
        } else {
            if (is_numeric($givendetail)) {
                $this->detail = TRUE;
            }
            $this->output = $this->si->get_detail_content_processed($this->currentdetail);
        }
    }
}
?>
