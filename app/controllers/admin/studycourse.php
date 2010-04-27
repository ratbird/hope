<?php
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
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     studycourses
 * @since       Stud.IP version 1.12
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/studycourse.php';
#require_once 'lib/messaging.inc.php';
#require_once 'lib/user_visible.inc.php';
#require_once 'lib/classes/AdminModules.class.php';
#require_once 'lib/classes/Config.class.php';

/**
 *
 *
 */
class Admin_StudycourseController extends AuthenticatedController
{
    /**
     * Maintenance view for profession with the degrees
     */
    function profession_action()
    {
        global $perm;
        $perm->check("root");

        // set variables for view
        $GLOBALS['CURRENT_PAGE'] = _('Verwaltung der Studiengänge');
        Navigation::activateItem('/admin/config/studycourse');

        $this->studycourses = StudycourseModel::getStudyCourses();
        //sorting
        if(Request::get('sortby') == 'users') {
            usort($this->studycourses, array('Admin_StudycourseController', 'sortByUsers'));
        } elseif (Request::get('sortby') == 'seminars') {
            usort($this->studycourses, array('Admin_StudycourseController', 'sortBySeminars'));
        }

        $this->infobox = $this->getInfobox();

        if (Request::submitted('delete')) {
            $this->delete_profession_action();
        }
    }

    /**
     * Maintenance view for degrees with the professions
     */
    function degree_action()
    {
        global $perm;
        $perm->check("root");

        // set variables for view
        $GLOBALS['CURRENT_PAGE'] = _('Gruppierung von Studienabschlüssen');
        Navigation::activateItem('/admin/config/studycourse');

        $this->studydegrees = StudycourseModel::getStudyDegrees();
        //sorting
        if(Request::get('sortby') == 'users') {
            usort($this->studydegrees, array('Admin_StudycourseController', 'sortByUsers'));
        }

        $this->infobox = $this-> getInfobox();

        if (Request::submitted('delete')) {
            $this->delete_degree_action();
        }
    }

    /**
     * Edit the selected profession
     * @param $prof_id
     */
    function edit_profession_action($prof_id)
    {
        global $perm;
        $perm->check("root");

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

        $GLOBALS['CURRENT_PAGE'] = _("Fächer editieren");
        Navigation::activateItem('/admin/config/studycourse');

        // set variables for view
        $this->edit = StudycourseModel::getStudyCourseInfo($prof_id);
        $this->infobox = $this->getInfobox();
    }

    /**
     * Edit the selected degree
     * @param $deg_id
     */
    function edit_degree_action($deg_id)
    {
        global $perm;
        $perm->check("root");

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

        $GLOBALS['CURRENT_PAGE'] = _("Abschlüsse editieren");
        Navigation::activateItem('/admin/config/studycourse');

        // set variables for view
        $this->edit = StudycourseModel::getStudyDegreeInfo($deg_id);
        $this->infobox = $this->getInfobox();
    }

    /**
     * Delete a profession
     * Only if count_user=0
     * @param $delete_course
     */
    function delete_profession_action()
    {
        global $perm;
        $perm->check("root");

        $prof_id = Request::get('prof_id');
        if (Request::submitted('delete')) {
            $profession = StudycourseModel::getStudyCourses($prof_id);
            //Check ob studiengang leer ist
            if ($profession[0][count_user] == 0) {
                if (StudycourseModel::deleteStudyCourse($prof_id)) {
                    $this->flash['success'] = _("Der Studiengang wurde erfolgreich gelöscht!");
                } else {
                    $this->flash['error'] = _("Interner Fehler im Löschvorgang! Bitte probieren Sie es erneut.");
                }
            } else {
                $this->flash['error']=_("Zu löschende Studiengänge müssen leer sein!");
            }
            $this->redirect('admin/studycourse/profession');
        } else {
            //Sicherheitsabfrage
            $this->flash['delete'] = StudycourseModel::getStudyCourses($prof_id);
            $this->redirect('admin/studycourse/profession');
        }
    }

    /**
     * Delete a degree
     * Only if count_user = 0
     * @param $delete_degree
     */
    function delete_degree_action()
    {
        global $perm;
        $perm->check("root");

        $deg_id = Request::get('deg_id');
        if (Request::submitted('delete')) {
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
            $this->redirect('admin/studycourse/degree');
        } else {
            //Sicherheitsabfrage
            $this->flash['delete']=StudycourseModel::getStudyDegrees($deg_id);
            $this->redirect('admin/studycourse/degree');
        }
    }

    /**
     * Create a new profession
     */
    function newprofession_action()
    {
        global $perm;
        $perm->check("root");

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

        $GLOBALS['CURRENT_PAGE'] = _("Anlegen von Studienfächern");
        Navigation::activateItem('/admin/config/studycourse');

        $this->infobox = $this-> getInfobox();
    }

    /**
     * Create a new degree
     */
    function newdegree_action()
    {
        global $perm;
        $perm->check("root");

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

        $GLOBALS['CURRENT_PAGE'] = _("Anlegen von Studienabschlüssen");
        Navigation::activateItem('/admin/config/studycourse');

        $this->infobox = $this-> getInfobox();
    }

    /**
     * Create the messagebox
     */
    private function getInfobox()
    {
        $infobox = array('picture' => 'browse.jpg');
        $aktionen[] = array(
            "text" => '<a href="'.$this->url_for('admin/studycourse/profession').'">'._('Gruppierung nach Fächer').'</a>',
            "icon" => "icon-cont.gif"
        );
        $aktionen[] = array(
            "text" => '<a href="'.$this->url_for('admin/studycourse/degree').'">'._('Gruppierung nach Abschlüssen').'</a>',
            "icon" => "icon-cont.gif"
        );
        $aktionen[] = array(
            "text" => '<a href="'.$this->url_for('admin/studycourse/newprofession').'">'._('Neue Fächer anlegen').'</a>',
            "icon" => "admin.gif"
        );
        $aktionen[] = array(
            "text" => '<a href="'.$this->url_for('admin/studycourse/newdegree').'">'._('Neue Abschlüsse anlegen').'</a>',
            "icon" => "admin.gif"
        );

        $infobox['content'] = array(
            array(
                'kategorie' => _("Aktionen"),
                'eintrag'   => $aktionen
            ),
            array(
                'kategorie' => _("Information"),
                'eintrag'   => array(
                    array(
                        "text" => _("Auf dieser Seite können Sie die Studiengänge verwalten. Zusätzlich können Sie allen Benutzern eines Studiengangs eine Nachricht senden."),
                        "icon" => "ausruf_small2.gif"
                    ),
                    array(
                        "text" => _("Studiengänge bestehen aus einem Fach und einem oder mehreren Abschlüssen. Bestehende Studiengänge und Abschlüsse können nur gelöscht werden, wenn diese keine Nutzer haben."),
                        "icon" => "ausruf_small2.gif"
                    )
                )
            )
        );
        return $infobox;
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
?>