<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
 * tour.php - Stud.IP-Tour controller
 *
 * Copyright (C) 2013 - Arne Schröder <schroeder@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Arne Schröder <schroeder@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     help
*/

require_once 'lib/functions.php';
require_once 'app/controllers/authenticated_controller.php';

class TourController extends AuthenticatedController
{
    
    /**
     * Callback function being called before an action is executed.
     */
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $this->orientation_options = array(
            'TL' => _('oben (links)'), 
            'T'  => _('oben (mittig)'), 
            'TR' => _('oben (rechts)'), 
            'BL' => _('unten (links)'), 
            'B'  => _('unten (mittig)'), 
            'BR' => _('unten (rechts)'), 
            'LT' => _('links (oben)'), 
            'L'  => _('links (mittig)'), 
            'LB' => _('links (unten)'), 
            'RT' => _('rechts (oben)'),
            'R'  => _('rechts (mittig)'),
            'RB' => _('rechts (unten)')
        );
        
        // AJAX request, so no page layout.
        if (Request::isXhr()) {
            $this->via_ajax = true;
            $this->set_layout(null);
            $request = Request::getInstance();
            foreach ($request as $key => $value) {
                $request[$key] = studip_utf8decode($value);
            }
        // Open base layout for normal view
        } else {
            $layout = $GLOBALS['template_factory']->open('layouts/base');
            $this->set_layout($layout);
        }
        $this->set_content_type('text/html;charset=windows-1252');
    }

    /**
     * sends tour object as json data
     *
     * @param  string  route
     */
    function get_data_action($tour_id, $step_nr = 1)
    {
        $this->route = get_route(Request::get('route'));
        $this->tour = new HelpTour($tour_id);
        if (!$this->tour->isVisible() OR (!$this->route))
            return $this->render_nothing();
            
        $this->user_visit = new HelpTourUser(array($tour_id, $GLOBALS['user']->user_id));
        if (($this->user_visit->step_nr > 1) AND !$_SESSION['active_tour']['step_nr'] AND ($this->tour->type == 'tour')) {
            $data['last_run'] = sprintf(_('Wollen Sie die Tour "%s" an der letzten Position fortsetzen?'), $this->tour->name);
            $data['last_run_step'] = $this->user_visit->step_nr;
            $data['last_run_href'] = URLHelper::getURL($this->tour->steps[$this->user_visit->step_nr-1]->route, NULL, true);
        } else {
            $_SESSION['active_tour'] = array(
                'tour_id' => $tour_id, 
                'step_nr' => $step_nr,
                'last_route' => $this->tour->steps[$step_nr-1]->route,
                'previous_route' => '',
                'next_route' => ''
            );
            $this->user_visit->step_nr = $step_nr;
            $this->user_visit->store();
        }
        $first_step = $step_nr;
        while (($first_step > 1) AND ($this->route == $this->tour->steps[$first_step-2]->route))
            $first_step--;
        if (($first_step > 1) AND ($this->tour->type == 'tour')) {
            $data['back_link'] = URLHelper::getURL($this->tour->steps[$first_step-2]->route, NULL, true);
            $_SESSION['active_tour']['previous_route'] = $this->tour->steps[$first_step-2]->route;
        }
        $data['route_step_nr'] = $first_step;
        $next_first_step = $first_step;
        while ($this->route == $this->tour->steps[$next_first_step-1]->route) {
            $data['data'][] = array(
                'step_nr' => $this->tour->steps[$next_first_step-1]->step, 
                'element' => $this->tour->steps[$next_first_step-1]->css_selector, 
                'title' => htmlReady($this->tour->steps[$next_first_step-1]->title), 
                'tip' => formatReady($this->tour->steps[$next_first_step-1]->tip), 
                'route' => $this->tour->steps[$next_first_step-1]->route, 
                'action_next' => $this->tour->steps[$next_first_step-1]->action_next, 
                'action_prev' => $this->tour->steps[$next_first_step-1]->action_prev, 
                'interactive' => ($this->tour->steps[$next_first_step-1]->interactive ? '1' : ''), 
                'orientation' => $this->tour->steps[$next_first_step-1]->orientation);
            $next_first_step++;
        }
        if ($this->tour->steps[$step_nr-1]->route != $this->route)
            $data['redirect'] = URLHelper::getURL($this->tour->steps[$step_nr-1]->route, NULL, true);
        elseif (!count($data['data']))
            return $this->render_nothing();
        if ($next_first_step <= count($this->tour->steps)) {
            if ($this->tour->type == 'tour')
                $data['proceed_link'] = URLHelper::getURL($this->tour->steps[$next_first_step-1]->route, NULL, true);
            $_SESSION['active_tour']['next_route'] = $this->tour->steps[$next_first_step-1]->route;
        }
        
        $data['step_count'] = count($this->tour->steps);
        $data['controls_position'] = 'BR';
        $data['tour_type'] = $this->tour->type;
        $data['tour_title'] = htmlReady($this->tour->name);
        $template = $GLOBALS['template_factory']->open('tour/tour.php');
        $template->set_layout(null);
        $data['tour_html'] = $template->render();
        $this->set_content_type('application/json; charset=UTF-8');
        return $this->render_text(json_encode(studip_utf8encode($data)));
    }
    
    /**
     * sets session data for active tour
     *
     * @param  string  route
     */
    function set_status_action($tour_id, $step_nr, $status)
    {
        // check permission
        $GLOBALS['perm']->check('user');
        $this->tour = new HelpTour($tour_id);
        if (!$this->tour->isVisible())
            return $this->render_nothing();
        $this->user_visit = new HelpTourUser(array($tour_id, $GLOBALS['user']->user_id));
        $this->user_visit->step_nr = $step_nr;
        if ($status == 'off') {
            unset($_SESSION['active_tour']);
            if ($step_nr == count($this->tour->steps)) {
                $this->user_visit->completed = 1;
                $this->user_visit->step_nr = 0;
            }
            $this->user_visit->store();
        } else {
            $_SESSION['active_tour'] = array(
                'tour_id' => $tour_id, 
                'step_nr' => $step_nr,
                'last_route' => $this->tour->steps[$step_nr-1]->route
            );
            $this->user_visit->store();
            while (($this->tour->steps[$step_nr-1]->route == $_SESSION['active_tour']['last_route']) AND ($step_nr < count($this->tour->steps)))
                $step_nr++;
            if ($this->tour->steps[$step_nr-1]->route != $_SESSION['active_tour']['last_route'])
                $_SESSION['active_tour']['next_route'] = $this->tour->steps[$step_nr-1]->route;
        }
        $this->render_nothing();
    }

    /**
     * Administration page for tours
     */
    function admin_overview_action()
    {
        // check permission
        if (!$GLOBALS['auth']->is_authenticated() || $GLOBALS['user']->id === 'nobody') {
            throw new AccessDeniedException();
        }
        $GLOBALS['perm']->check('root');

        // initialize
        PageLayout::setTitle(_('Verwalten von Touren'));
        PageLayout::setHelpKeyword('Basis.TourAdmin');
        // set navigation
        Navigation::activateItem('/admin/config/tour');

        if (Request::get('tour_filter') == 'set') {
            $this->tour_searchterm = Request::option('tour_filter_term');
        }
        if (Request::submitted('reset_filter')) {
            $this->tour_searchterm = '';
        }
        if (Request::submitted('apply_tour_filter')) {
            if (Request::get('tour_searchterm') AND (strlen(trim(Request::get('tour_searchterm'))) < 3))
                PageLayout::postMessage(MessageBox::error(_('Der Suchbegriff muss mindestens 3 Zeichen lang sein.')));
            if (strlen(trim(Request::get('tour_searchterm'))) >= 3) {
                $this->tour_searchterm = htmlReady(Request::get('tour_searchterm'));
                $this->filter_text = sprintf(_('Angezeigt werden Touren zum Suchbegriff "%s".'), $this->tour_searchterm);
            }
        }
        // load tours
        $this->tours = HelpTour::GetToursByFilter($this->tour_searchterm);

        // save settings
        if (Request::submitted('save_tour_settings')) {
            foreach($this->tours as $tour_id => $tour) {
                // set status as chosen
                if ((Request::get('tour_status_'.$tour_id) == '1') AND (!$this->tours[$tour_id]->settings->active)) {
                    $this->tours[$tour_id]->settings->active = 1;
                    $this->tours[$tour_id]->store();
                } elseif ((Request::get('tour_status_'.$tour_id) != '1') AND ($this->tours[$tour_id]->settings->active)) {
                    $this->tours[$tour_id]->settings->active = 0;
                    $this->tours[$tour_id]->store();
                }
            }
        }
    }

    /**
     * delete tour step
     */
    function delete_step($tour_id, $step_nr)
    {
        $output = '';
        if (Request::submitted('yes')) {
            CSRFProtection::verifySecurityToken();
            header('X-Action: complete');
            $this->tour->deleteStep($step_nr);
        } elseif (Request::submitted('no')) {
            header('X-Action: complete');
        } else {
            header('X-Action: question');
            $output = createQuestion2(sprintf(_('Wollen Sie Schritt %s wirklich löschen?'), $step_nr), array('confirm_delete_tour_step' => 1, 'tour_id' => $tour_id, 'step_nr' => $step_nr), array(), '');
        }
        return $output;
    }
    
    /**
     * delete tour step (ajax call)
     */
    function delete_step_action($tour_id, $step_nr)
    {
        $GLOBALS['perm']->check('root');
        $this->tour = new HelpTour($tour_id);
        $this->render_text($this->delete_step($tour_id, $step_nr));
    }
    
    /**
     * edit tour step
     */
    function edit_step_action($tour_id, $step_nr, $mode = 'edit')
    {
        $GLOBALS['perm']->check('root');
        // Output as dialog (Ajax-Request) or as Stud.IP page?
        if ($this->via_ajax) {
            header('X-Title: ' . _('Schritt bearbeiten'));
            header('X-No-Buttons: 1');
        }
        // save step position
        if ($mode == 'save_position') {
            $temp_step = new HelpTourStep(array($tour_id, $step_nr));
            $temp_step->css_selector = trim(Request::get('position'));
            if ($temp_step->validate() AND ! $temp_step->isNew()) {
                $temp_step->store();
                header('X-Action: close');
            }
            return $this->render_nothing();
        }
        // save step
        if ($mode == 'save') {
            CSRFProtection::verifySecurityToken();
            if (Request::option('tour_step_editmode') == 'new') {
                $this->tour = new HelpTour($tour_id);
                if ($tour_id AND $this->tour->isNew())
                    throw new AccessDeniedException(_('Die Tour mit der angegebenen ID existiert nicht.'));
                $step_data = array(
                    'title'         => trim(Request::get('step_title')),
                    'tip'           => trim(Request::get('step_tip')),
                    'route'         => trim(Request::get('step_route')),
                    'css_selector'  => trim(Request::get('step_css')),
                    'orientation'   => trim(Request::get('step_orientation')),
                    'mkdate'        => time(),
                    'author_id'     => $GLOBALS['user']->user_id
                );
                if ($this->tour->addStep($step_data, $step_nr)) {
                    //PageLayout::postMessage(MessageBox::success(_('Der Schritt wurde hinzugefügt.')));
                    header('X-Action: close');
                    return $this->render_nothing();
                } else 
                    $mode = 'new';
            } else {
                $temp_step = new HelpTourStep(array($tour_id, $step_nr));
                $temp_step->title = trim(Request::get('step_title'));
                $temp_step->tip = trim(Request::get('step_tip'));
                $temp_step->route = trim(Request::get('step_route'));
                $temp_step->css_selector = trim(Request::get('step_css'));
                $temp_step->orientation = Request::option('step_orientation');
                if ($temp_step->validate()) {
                    $temp_step->store();
                    header('X-Action: close');
                    //PageLayout::postMessage(MessageBox::success(_('Der Schritt wurde gespeichert.')));
                    return $this->render_nothing();
                } else
                    $mode = 'edit';
            }
        }

        // prepare edit dialog
        $this->tour_id = $tour_id;
        if ($mode == 'new') {
            $this->step = new HelpTourStep();
            $this->step->step = $step_nr;
            $temp_step = new HelpTourStep(array($tour_id, $step_nr-1));
            if (! $temp_step->isNew())
                $this->step->route = $temp_step->route;
        } else    
            $this->step = new HelpTourStep(array($tour_id, $step_nr));
        if (Request::option('hide_route'))
            $this->force_route = $this->step->route;
        $this->mode = $mode;
    }

    /**
     * Administration page for single tour
     */
    function admin_details_action($tour_id = '')
    {
        // check permission
        $GLOBALS['perm']->check('admin');
        // initialize
        PageLayout::setTitle(_('Verwalten von Touren'));
        PageLayout::setHelpKeyword('Basis.TourAdmin');
        Navigation::activateItem('/admin/config/tour');
        
        $this->tour = new HelpTour($tour_id);
        if ($tour_id AND $this->tour->isNew())
            throw new AccessDeniedException(_('Die Tour mit der angegebenen ID existiert nicht.'));
        if (Request::option('confirm_delete_tour_step')) {
            CSRFProtection::verifySecurityToken();
            $this->delete_tour_step(Request::option('tour_id'), Request::option('step_nr'));
        }
        foreach($this->tour->steps as $step) {
            if (Request::submitted('delete_tour_step_'.$step->step))
                $this->delete_question = $this->delete_tour_step($this->tour->tour_id, $step->step);
        }
        if (Request::submitted('save_tour_details')) {
            CSRFProtection::verifySecurityToken();
            $this->tour->name = trim(Request::get('tour_name'));
            $this->tour->description = trim(Request::get('tour_description'));
            $this->tour->type = Request::option('tour_type');
            $this->tour->settings->access = Request::option('tour_access');
            $this->tour->roles = implode(',', Request::getArray('tour_roles'));
            $this->tour->version = Request::int('tour_version');
            if ($this->tour->validate()) {
                $this->tour->store();
                PageLayout::postMessage(MessageBox::success(_('Die Angaben wurden gespeichert.')));
            }
        }
    }
}