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

        if ($GLOBALS['SessSemName']['class'] == 'sem') {
            if (SeminarCategories::GetBySeminarId($GLOBALS['SessSemName'][1])->studygroup_mode) {
                throw new Exception(_('Dies ist eine Studiengruppe und kein Seminar!'));
            }
        }
        PageLayout::setTitle(sprintf(_("%s - Verwaltung"), $GLOBALS['SessSemName']['header_line']));
        PageLayout::setHelpKeyword('Basis.InVeranstaltungVerwaltung');

        mark_public_course();
    }

    /**
     * shows index page of course or institute management
     *
     * @return void
     */
    function index_action()
    {
        Navigation::activateItem('course/admin/main');

        if ($GLOBALS['SessSemName']['class'] == 'inst') {
            $this->infotext = _('Als Mitarbeiter Ihrer Einrichtung können Sie für diese Inhalte in mehreren Kategorien bereitstellen. Inhalte in Ihrer Einrichtung können von allen Stud.IP-Nutzern abgerufen werden.');
        } else {
            $this->infotext = _('Sie können hier Ihre Veranstaltung in mehreren Kategorien anpassen. Informationen wie Grunddaten oder Termine und Einstellungen, Zugangsbeschränkungen und Funktionen können Sie hier administrieren.');
        }
    }
}
