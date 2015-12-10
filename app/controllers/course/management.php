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
 * @author      dsiegfried <david.siegfried@uni-vechta.de>
 * @copyright   2010 ELAN e.V.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       1.10
 */

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

        if ($GLOBALS['SessSemName']['class'] == 'inst') {
            Helpbar::get()->addPlainText(_('Information'), _('Als Mitarbeiter Ihrer Einrichtung können Sie für diese Inhalte in mehreren Kategorien bereitstellen.Inhalte in Ihrer Einrichtung können von allen Stud.IP-Nutzern abgerufen werden.'));
        } else {
            Helpbar::get()->addPlainText(_('Information'), _('Sie können hier Ihre Veranstaltung in mehreren Kategorien anpassen. Informationen wie Grunddaten oder Termine und Einstellungen, Zugangsbeschränkungen und Funktionen können Sie hier administrieren.'));
        }
    }

    /**
     * change the visibility of a course
     *
     * @return void
     */
    function change_visibility_action()
    {
        if ((Config::get()->ALLOW_DOZENT_VISIBILITY || $GLOBALS['perm']->have_perm('admin')) &&
            !LockRules::Check($GLOBALS['SessionSeminar'], 'seminar_visibility') && Seminar_Session::check_ticket(Request::option('studip_ticket'))) {
            $course = Course::findCurrent();
            if (!$course->visible) {
                StudipLog::log('SEM_VISIBLE', $course->id);
                $course->visible = 1;
                $msg             = _("Die Veranstaltung wurde sichtbar gemacht.");
            } else {
                StudipLog::log('SEM_INVISIBLE', $course->id);
                $course->visible = 0;
                $msg             = _("Die Veranstaltung wurde versteckt.");
            }
            if ($course->store()) {
                PageLayout::postMessage(MessageBox::success($msg));
            }
        }
        $this->redirect($this->url_for('/index'));
    }

    /**
     * shows the lock rules
     *
     * @return void
     */
    public function lock_action()
    {
        Navigation::activateItem('course/admin/main');
        PageLayout::setTitle(_('Sperrebene ändern'));
        $course = Course::findCurrent();

        if (!$course) {
            $this->redirect($this->url_for('/index'));
            return;
        }

        $this->all_lock_rules    = array_merge(array(array('name' => ' -- ' . _("keine Sperrebene") . ' -- ', 'lock_id' => 'none')), LockRule::findAllByType('sem'));
        $this->current_lock_rule = LockRule::find($course->lock_rule);
    }

    /**
     * set the lock rule
     *
     * @return void
     */
    public function set_lock_rule_action()
    {
        CSRFProtection::verifyUnsafeRequest();
        if (!$GLOBALS['perm']->have_studip_perm('admin', $GLOBALS['SessionSeminar'])) {
            throw new AccessDeniedException();
        }
        $course = Course::findCurrent();

        if ($course) {
            $rule_id = Request::get('lock_sem') != 'none' ? Request::get('lock_sem') : null;

            $course->lock_rule = $rule_id;
            if ($course->store()) {
                if (!is_null($rule_id)) {
                    $lock_rule = LockRule::find($rule_id);
                    $msg       = sprintf(_('Die Sperrebene %s wurde erfolgreich übernommen!'), $lock_rule->name);
                } else {
                    $msg = _('Die Sperrebene wurde erfolgreich zurückgesetzt!');
                }
                PageLayout::postMessage(MessageBox::success($msg));
            }
        }
        $this->relocate($this->url_for('/index'));
    }
}
