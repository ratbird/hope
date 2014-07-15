<?php
# Lifter010: TODO
/**
 * studycourse.php - controller class for the studycourses
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Nico Müller <nico.mueller@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     studycourses
 * @since       2.0
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/studycourse.php';

/**
 *
 *
 */
class Admin_StudycourseController extends AuthenticatedController
{
    /**
     * Common tasks for all actions.
     */
    public function before_filter(&$action, &$args)
    {
        global $perm;

        parent::before_filter($action, $args);

        // user must have root permission
        $perm->check('root');

        // set navigation
        Navigation::activateItem('/admin/locations/studycourse');
    }

    /**
     * Maintenance view for profession with the degrees
     */
    public function profession_action()
    {
        //set title
        PageLayout::setTitle(_('Verwaltung der Studiengänge'));

        //get data
        $this->studycourses = StudycourseModel::getStudyCourses();
        $this->infobox = $this->setSidebar();

        //sorting
        if(Request::get('sortby') == 'users') {
            usort($this->studycourses, array('Admin_StudycourseController', 'sortByUsers'));
        } elseif (Request::get('sortby') == 'seminars') {
            usort($this->studycourses, array('Admin_StudycourseController', 'sortBySeminars'));
        }
    }

    /**
     * Maintenance view for degrees with the professions
     */
    public function degree_action()
    {
        // set title
        PageLayout::setTitle(_('Gruppierung von Studienabschlüssen'));

        //get data
        $this->studydegrees = StudycourseModel::getStudyDegrees();
        $this->infobox = $this->setSidebar();

        //sorting
        if(Request::get('sortby') == 'users') {
            usort($this->studydegrees, array('Admin_StudycourseController', 'sortByUsers'));
        }
    }

    /**
     * Edit the selected profession
     * @param $prof_id
     */
    public function edit_profession_action($prof_id)
    {
        //save changes
        if (Request::submitted('uebernehmen')) {
            if (Request::get('professionname')) {
                $prof_name = Request::get('professionname');
                $prof_desc = Request::get('description');
                StudycourseModel::saveEditProfession($prof_id, $prof_name, $prof_desc);
                $this->flash['success'] = sprintf(_('Das Studienfach "%s" wurde erfolgreich aktualisiert!'), htmlReady($prof_name));
                $this->flash['success_detail'] = array(_("Beschreibung: ") . htmlReady($prof_desc));
                $this->redirect('admin/studycourse/profession');
            } else {
                $this->flash['error'] = _("Bitte geben Sie mindestens einen Namen für das Fach ein!");
            }
        }

        PageLayout::setTitle(_("Fächer editieren"));
        $this->edit = StudycourseModel::getStudyCourseInfo($prof_id);
        $this->infobox = $this->setSidebar();
    }

    /**
     * Edit the selected degree
     * @param $deg_id
     */
    public function edit_degree_action($deg_id)
    {
        if (Request::submitted('uebernehmen')) {
            if (Request::get('degreename')) {
                $deg_name = Request::get('degreename');
                $deg_desc = Request::get('description');
                StudycourseModel::saveEditDegree($deg_id, $deg_name, $deg_desc);
                $this->flash['success'] = sprintf(_('Der Abschluss "%s" wurde erfolgreich aktualisiert!'), htmlReady($deg_name));
                $this->flash['success_detail'] = array(_("Beschreibung: ") . htmlReady($deg_desc));
                $this->redirect('admin/studycourse/degree');
            } else {
                $this->flash['error'] = _("Bitte geben Sie mindestens einen Namen für den Abschluss ein!");
            }
        }

        PageLayout::setTitle(_("Abschlüsse editieren"));
        $this->edit = StudycourseModel::getStudyDegreeInfo($deg_id);
        $this->infobox = $this->setSidebar();
    }

    /**
     * Delete a profession
     * Only if count_user=0
     *
     * @param string $prof_id
     */
    function delete_profession_action($prof_id)
    {
        if (Request::int('delete') == 1) {
            $profession = StudycourseModel::getStudyCourses($prof_id);
            //Check ob studiengang leer ist
            if ($profession[0]['count_user'] == 0) {
                if (StudycourseModel::deleteStudyCourse($prof_id)) {
                    $this->flash['success'] = _("Der Studiengang wurde erfolgreich gelöscht!");
                } else {
                    $this->flash['error'] = _("Interner Fehler im Löschvorgang! Bitte probieren Sie es erneut.");
                }
            } else {
                $this->flash['error']=_("Zu löschende Studiengänge müssen leer sein!");
            }
        } elseif (!Request::get('back')) {
            $this->flash['delete'] = StudycourseModel::getStudyCourses($prof_id);
        }
        $this->redirect('admin/studycourse/profession');
    }

    /**
     * Delete a degree
     * Only if count_user = 0
     *
     * @param string $deg_id
     */
    function delete_degree_action($deg_id)
    {
        if (Request::int('delete') == 1) {
            $degree = StudycourseModel::getStudyDegrees($deg_id);
            //Check ob Abschluss leer ist
            if ($degree[0][count_user] == 0) {
                if (StudycourseModel::deleteStudyDegree($deg_id)) {
                    $this->flash['success'] = _("Der Abschluss wurde erfolgreich gelöscht!");
                } else {
                    $this->flash['error'] = _("Interner Fehler im Löschvorgang! Bitte probieren Sie es erneut.");
                }
            } else {
                $this->flash['error'] = _("Zu löschende Abschlüsse müssen leer sein!");
            }
        } elseif (!Request::get('back')) {
            $this->flash['delete'] = StudycourseModel::getStudyDegrees($deg_id);
        }
        $this->redirect('admin/studycourse/degree');
    }

    /**
     * Create a new profession
     */
    function newprofession_action()
    {
        if (Request::submitted('anlegen')) {
            if (Request::get('professionname')) {
                $prof_name = Request::get('professionname');
                $prof_desc = Request::get('description');
                if (!StudycourseModel::checkProfession($prof_name)) {
                    StudycourseModel::saveNewProfession($prof_name, $prof_desc);
                    $this->flash['success'] = sprintf(_('Das Studienfach "%s" wurde erfolgreich angelegt!'), htmlReady($prof_name));
                    $this->redirect('admin/studycourse/profession');
                } else {
                    $this->flash['error'] = sprintf(_('Ein Studienfach mit dem Namen "%s" existiert bereits!'), htmlReady($prof_name));
                }
            } else {
                $this->flash['error'] = _("Bitte geben Sie eine mindestens einen Namen für das Fach ein!");
            }
        }

        PageLayout::setTitle(_("Anlegen von Studienfächern"));
        $this->infobox = $this->setSidebar();
    }

    /**
     * Create a new degree
     */
    function newdegree_action()
    {
        if (Request::submitted('anlegen')) {
            if (Request::get('degreename')) {
                $deg_name = Request::get('degreename');
                $deg_desc = Request::get('description');
                if (!StudycourseModel::checkDegree($deg_name)) {
                    StudycourseModel::saveNewDegree($deg_name, $deg_desc);
                    $this->flash['success'] = sprintf(_('Der Studienabschluss "%s" wurde erfolgreich angelegt!'), htmlReady($deg_name));
                    $this->redirect('admin/studycourse/degree');
                } else {
                    $this->flash['error'] = sprintf(_('Ein Studienabschluss mit dem Namen "%s" existiert bereits!'), htmlReady($deg_name));
                }
            } else {
                $this->flash['error'] = _("Bitte geben Sie mindestens einen Namen für den Abschluss ein!");
            }
        }

        PageLayout::setTitle(_("Anlegen von Studienabschlüssen"));

        $this->infobox = $this->setSidebar();
    }

    /**
     * Create the messagebox
     */
    private function setSidebar()
    {
        $sidebar = Sidebar::Get();
        $sidebar->setTitle(_('Studiengänge'));
        $sidebar->setImage(Assets::image_path('sidebar/admin-sidebar.png'));

        $links = new ActionsWidget();
        $links->addLink(_('Gruppierung nach Fächer'), $this->url_for('admin/studycourse/profession'), 'icons/16/blue/visibility-visible.png');
        $links->addLink(_('Gruppierung nach Abschlüssen'), $this->url_for('admin/studycourse/degree'), 'icons/16/blue/visibility-visible.png');
        $links->addLink(_('Neue Fächer anlegen'), $this->url_for('admin/studycourse/newprofession'), 'icons/16/blue/add.png');
        $links->addLink(_('Neue Abschlüsse anlegen'), $this->url_for('admin/studycourse/newdegree'), 'icons/16/blue/add.png');
        $sidebar->addWidget($links);
    }

    private static function sortByUsers($a, $b)
    {
        if ($a['count_user'] == $b['count_user']) {
            return 0;
        }
        return ($a['count_user'] > $b['count_user']) ? -1 : 1;
    }

    private static function sortBySeminars($a, $b)
    {
        if ($a['count_sem'] == $b['count_sem']) {
            return 0;
        }
        return ($a['count_sem'] > $b['count_sem']) ? -1 : 1;
    }
}
