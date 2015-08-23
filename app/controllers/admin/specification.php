<?php
# Lifter010: TODO
/**
 * specification.php - controller class for the specification
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
 * @package     admin
 * @since       Stud.IP version 2.1
 */

//Imports
require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/DataFieldEntry.class.php';
require_once 'lib/classes/DataFieldEntry.class.php';

class Admin_SpecificationController extends AuthenticatedController
{
    /**
     * Common tasks for all actions.
     */
    public function before_filter(&$action, &$args)
    {
        global $perm;

        parent::before_filter($action, $args);

        # user must have special permission
        if (!$perm->have_perm(get_config('AUX_RULE_ADMIN_PERM') ? get_config('AUX_RULE_ADMIN_PERM') : 'admin')) {
            throw new AccessDeniedException(_("Keine Berechtigung."));
        }

        //setting title and navigation
        Navigation::activateItem('/admin/config/specification');
        PageLayout::setTitle(_('Verwaltung von Zusatzangaben'));
    }

    /**
     * Maintenance view for the specification parameters
     *
     */
    function index_action()
    {
        $this->allrules = AuxLockRules::getAllLockRules();
    }

    /**
     * Edit or create a rule
     *
     * @param md5 $edit_id
     */
    function edit_action($id = null)
    {
        //get data
        $user_field = 'user';
        $semdata_field = 'usersemdata';
        $this->semFields = AuxLockRules::getSemFields();
        $this->entries_user = DataFieldStructure::getDataFieldStructures($user_field);
        $this->entries_semdata = DataFieldStructure::getDataFieldStructures($semdata_field);
        $this->rule = (is_null($id)) ? false : AuxLockRules::getLockRuleByID($id);

        if ($GLOBALS['perm']->have_perm('root') && count($this->entries_semdata) == 0) {
            $this->flash['info'] = sprintf(_('Sie müssen zuerst im Bereich %sDatenfelder%s in der Kategorie '
            .'<i>Datenfelder für Nutzer-Zusatzangaben in Veranstaltungen</i> einen neuen Eintrag erstellen.'),
            '<a href="' . URLHelper::getLink('dispatch.php/admin/datafields') . '">', '</a>');
        }

        // save action
        if (Request::submitted('erstellen') || Request::submitted('uebernehmen')) {

            //checking for errors
            $errors = array();
            if (!Request::get('rulename')) {
                array_push($errors, _("Bitte geben Sie der Regel mindestens einen Namen!"));
            }
            if (!AuxLockRules::checkLockRule(Request::getArray('fields'))) {
                array_push($errors, _('Bitte wählen Sie mindestens ein Feld aus der Kategorie "Zusatzinformationen" aus!'));
            }
            if (!empty($errors)) {
                $this->flash['error'] = _("Ihre Eingaben sind ungültig.");
                $this->flash['error_detail'] = $errors;

            // save
            } else {
                //new
                if(is_null($id)) {
                    AuxLockRules::createLockRule(Request::get('rulename'), Request::get('description'), Request::getArray('fields'), Request::getArray('order'));
                //edit
                } else {
                    AuxLockRules::updateLockRule($id, Request::get('rulename'), Request::get('description'), Request::getArray('fields'), Request::getArray('order'));
                }
                $this->flash['success'] = sprintf(_('Die Regel "%s" wurde erfolgreich gespeichert!'), htmlReady(Request::get('rulename')));
                $this->redirect('admin/specification');
            }
        }
    }

    /**
     * Delete a rule, using a modal dialog
     *
     * @param md5 $rule_id
     */
    function delete_action($rule_id)
    {
        $this->flash['delete'] = AuxLockRules::getLockRuleByID($rule_id);

        //sicherheitsabfrage
        if (Request::get('delete') == 1) {
            if (AuxLockRules::deleteLockRule($rule_id)) {
                $this->flash['success'] = _("Die Regel wurde erfolgreich gelöscht!");
            } else {
                $this->flash['error'] = _("Es können nur nicht verwendete Regeln gelöscht werden!");
                unset($this->flash['delete']);
            }
        } elseif(Request::get('back')) {
             unset($this->flash['delete']);
        }
        $this->redirect('admin/specification');
    }
}
