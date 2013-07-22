<?php
/**
 * Simple Content Module von Stud.IP
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author   Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author   Andr� Noack <anoack@mcis.de>
 * @author   Cornelis Kater <ckater@gwdg.de>
 * @author   Stefan Suchi <suchi@gmx.de>
 * @author   Tobias Thelen <tthelen@uni-osnabrueck.de>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category Stud.IP
 * @since    2.5
 */

require_once 'app/controllers/studip_controller.php';

class Course_ScmController extends StudipController
{
    /**
     * Sets the page title. Page title always includes the course name.
     *
     * @param mixed $title Title of the page (optional)
     */
    private function set_title($title = '')
    {
        $title_parts   = func_get_args();
        $title_parts[] = $GLOBALS['SessSemName']['header_line'];
        $page_title    = implode(' - ', $title_parts);

        PageLayout::setTitle($page_title);
    }

    /**
     * Before filter, set up the page by initializing the session and checking
     * all conditions.
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // open session
        page_open(array('sess' => 'Seminar_Session',
                        'auth' => 'Seminar_Default_Auth',
                        'perm' => 'Seminar_Perm',
                        'user' => 'Seminar_User'));

        // set up user session
        include 'lib/seminar_open.php';

        if (!Config::Get()->SCM_ENABLE) {
            throw new AccessDeniedException(_('Die freien Informationsseiten sind nicht aktiviert.'));
        }

        $GLOBALS['auth']->login_if(Request::get('again')
                                   && $GLOBALS['auth']->auth['uid'] == 'nobody');
        $this->priviledged = $GLOBALS['perm']->have_studip_perm('tutor', $GLOBALS['SessSemName'][1]);

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));

        if (!in_array($action, words('index create edit move delete'))) {
            array_unshift($args, $action);
            $action = 'index';
        }

        if (in_array($action, words('create edit move delete')) && !$this->priviledged) {
            throw new AccessDeniedException(_('Sie sind nicht berechtigt, auf diesen Bereich zuzugreifen'));
        }

        PageLayout::setHelpKeyword('Basis.Informationsseite');
        Navigation::activateItem('/course/scm');

        checkObject(); // do we have an open object?
        checkObjectModule('scm');
        object_set_visit_module('scm');
    }

    /**
     * Displays a page.
     *
     * @param mixed $id Id of the page to display, optional - defaults to
     *                  first page in set.
     */
    public function index_action($id = null)
    {
        $temp       = StudipScmEntry::findByRange_id($GLOBALS['SessSemName'][1], 'ORDER BY position ASC');
        $this->scms = SimpleORMapCollection::createFromArray($temp);
        $this->scm  = $id ? $this->scms->find($id) : $this->scms->first();

        if (!$this->scm) {
            throw new Trails_Exception(404, _('Es konnte keine freie Informationsseite mit der angegebenen Id gefunden werden.'));
        }

        if (Request::get('verify') == 'delete') {
            $this->verification = $GLOBALS['template_factory']->open('shared/question')->render(array(
                'approvalLink'    => $this->url_for('course/scm/delete/' . $this->scm->id . '?ticket=' . get_ticket()),
                'disapprovalLink' => $this->url_for('course/scm/' . $this->scm->id),
                'question'        => _('Wollen Sie diese Seite wirklich l�schen?'),
            ));
        }

        $this->set_title($this->scm->tab_name);
        Navigation::activateItem('/course/scm/' . $this->scm->id);
    }

    /**
     * Create a page, more or less an alias for the edit action.
     */
    public function create_action()
    {
        $this->scm = new StudipScmEntry();
        $this->scm->user_id = $GLOBALS['user']->id;
        $this->scm->chdate  = time();

        $this->first_entry = StudipScmEntry::countBySql('range_id = ?', array($GLOBALS['SessSemName'][1])) == 0;
        
        $this->set_title(_('Neue Informationsseite anlegen'));
        Navigation::activateItem('/course/scm/new_entry');

        $this->render_action('edit');
    }

    /**
     * Edits or creates a page.
     *
     * @param mixed $id Id of the page to edit; a new page will be created if
     *                  this parameter is omitted.
     */
    public function edit_action($id = null)
    {
        if (Request::submitted('submit')) {
            CSRFProtection::verifyUnsafeRequest();

            $scm = new StudipScmEntry($id);

            $scm->tab_name = Request::get('tab_name_template') ?: Request::get('tab_name');
            $scm->content  = Request::get('content');
            $scm->user_id  = $GLOBALS['user']->id;
            $scm->range_id = $GLOBALS['SessSemName'][1];

            if ($scm->isNew()) {
                $temp = StudipScmEntry::findByRange_id($GLOBALS['SessSemName'][1], 'ORDER BY position ASC');
                $scms = SimpleORMapCollection::createFromArray($temp);
                $max  = max($scms->pluck('position'));

                $scm->position = $max + 1;
            }

            if ($scm->store() !== false) {
                $message = MessageBox::success(_('Die �nderungen wurden �bernommen.'));
                PageLayout::postMessage($message);
            }

            $this->redirect('course/scm/' . $scm->id);
        }

        $this->scm = new StudipScmEntry($id);

        $this->set_title(_('Informationsseite bearbeiten') . ': ' . $this->scm->tab_name);
        Navigation::activateItem('/course/scm/' . $this->scm->id);
    }

    /**
     * Moves a page to the front so it becomes the first page the user will
     * see.
     *
     * @param String $id Id of the page to move
     */
    public function move_action($id)
    {
        $scm = new StudipScmEntry($id);
        if (!$scm->isNew() && $scm->range_id == $GLOBALS['SessSemName'][1]){
            $query = "UPDATE scm
                      SET position = position + 1
                      WHERE range_id = :range_id AND position < :position";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':range_id', $scm->range_id);
            $statement->bindValue(':position', $scm->position);
            $statement->execute();

            $scm->position = 0;
            if ($scm->store()) {
                PageLayout::postMessage(MessageBox::success(_('Der Eintrag wurde an die erste Position verschoben.')));
            }
        }
        $this->redirect('course/scm/' . $id);
    }

    /**
     * Deletes a page.
     *
     * @param String $id Id of the page to delete
     */
    public function delete_action($id)
    {
        $ticket = Request::option('ticket');
        if ($ticket && check_ticket($ticket)) {
            $scm = new StudipScmEntry($id);
            if (!$scm->isNew() && $scm->range_id == $GLOBALS['SessSemName'][1]){
                $scm->delete();
                PageLayout::postMessage(MessageBox::success(_('Der Eintrag wurde gel�scht.')));
            }
            $this->redirect('course/scm');
            return;
        }
        
        PageLayout::postMessage(MessageBox::error(_('Es ist ein Fehler aufgetreten. Bitte versuchen Sie erneut, diese Seite zu l�schen.')));
        $this->redirect('course/scm/' . $id);
    }

    /**
     * After filter, closes the page.
     *
     * @param String $action Name of the action that has been invoked
     * @param Array  $args   Arguments that were passed to the action method
     */
    public function after_filter($action, $args)
    {
        parent::after_filter($action, $args);

        page_close();
    }
}
