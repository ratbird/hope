<?php
# Lifter010: TODO

/*
 * management.php - realises a redirector for administrative pages
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      tgloeggl <tgloeggl@uos.de>
 * @author      aklassen <andre.klassen@elan-ev.de>
 * @copyright   2010 ELAN e.V.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       1.10
 */

require_once 'app/controllers/authenticated_controller.php';

class Course_ManagementController extends AuthenticatedController
{
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$GLOBALS['SessSemName']['art_num']]['class']];
        if (!$sem_class->isModuleAllowed("CoreAdmin")) {
            throw new Exception(_('Dies ist eine Studiengruppe und kein Seminar!'));
        }
        if (!$GLOBALS['perm']->have_studip_perm("tutor", $GLOBALS['SessionSeminar'])) {
            throw new Trails_Exception(400);
        }
        PageLayout::setTitle(sprintf(_("%s - Verwaltung"), $GLOBALS['SessSemName']['header_line']));
        PageLayout::setHelpKeyword('Basis.InVeranstaltungVerwaltung');
    }

    /**
     * shows index page of course or institute management
     *
     * @return void
     */
    function index_action()
    {
        Navigation::activateItem('course/admin/main');

        //Todo: das sollte in die Hilfelasche
        if ($GLOBALS['SessSemName']['class'] == 'inst') {
            $this->infotext = _('Als Mitarbeiter Ihrer Einrichtung können Sie für diese Inhalte in mehreren Kategorien bereitstellen. Inhalte in Ihrer Einrichtung können von allen Stud.IP-Nutzern abgerufen werden.');
        } else {
            $this->infotext = _('Sie können hier Ihre Veranstaltung in mehreren Kategorien anpassen. Informationen wie Grunddaten oder Termine und Einstellungen, Zugangsbeschränkungen und Funktionen können Sie hier administrieren.');
        }
    }

    function change_visibility_action()
    {
        if (get_config('ALLOW_DOZENT_VISIBILITY') && !LockRules::Check($GLOBALS['SessionSeminar'], 'seminar_visibility') && Seminar_Session::check_ticket(Request::option('studip_ticket'))) {
            $course = Course::findCurrent();
            if (!$course->visible) {
                log_event("SEM_VISIBLE", $course->id);
                $course->visible = 1;
                $msg = _("Die Veranstaltung wurde sichtbar gemacht.");
            } else {
                log_event("SEM_INVISIBLE", $course->id);
                $course->visible = 0;
                $msg = _("Die Veranstaltung wurde versteckt.");
            }
            if ($course->store()) {
                PageLayout::postMessage(MessageBox::success($msg));
            }
        }
        $this->redirect($this->url_for('/index'));
    }
}
