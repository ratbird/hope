<?php
/**
 * coursewizardsteps.php
 * Controller for managing available course creation wizard steps.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @copyright   2015 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.3
 */

require_once 'app/controllers/authenticated_controller.php';

$stepdir = "../lib/classes/coursewizardsteps";
foreach (scandir($stepdir) as $file) {
    if (stripos($file, ".php") !== false) {
        require_once $stepdir . "/" . $file;
    }
}


class Admin_CourseWizardStepsController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // Check permissions to be on this site
        if (!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException(_('Sie haben keine Berechtigung, '.
                'auf diesen Teil des Systems zuzugreifen.'));
        }
        PageLayout::setTitle(_('Schritte im Veranstaltungsanlegeassistenten'));
        Navigation::activateItem('/admin/config/coursewizardsteps');
    }

    /**
     * Show all available course wizard steps and their status.
     */
    public function index_action()
    {
        $this->steps = CourseWizardStepRegistry::findBySQL("1 ORDER BY `number`");
        $this->has_enabled = CourseWizardStepRegistry::hasEnabledSteps();
    }

    /**
     * Shows a dialog for creating or editing a course wizard step.
     * @param string $id ID of step to edit, or null if new step
     */
    public function edit_action($id='')
    {
        if ($id) {
            $title = _('Schritt bearbeiten');
            $this->step = CourseWizardStepRegistry::find($id);
        } else {
            $title = _('Schritt hinzufügen');
            $this->step = new CourseWizardStepRegistry();
            $this->step->name = '';
            $this->step->classname = '';
            $this->step->number = 0;
            $this->step->enabled = false;

            $this->availableClasses = array();
            foreach (get_declared_classes() as $className) {
                if (is_a($className, "CourseWizardStep", true)
                        && $className !== "CourseWizardStep") {
                    $collection = new SimpleCollection(CourseWizardStepRegistry::findBySQL("1 ORDER BY `number`"));
                    if (!in_array($className, $collection->pluck("classname"))) {
                        $this->availableClasses[] = $className;
                    }
                }
            }
        }
        if (Request::isXhr()) {
            $this->response->add_header('X-Title', $title);
        }
    }

    /**
     * Saves data for a new or existing step.
     * @param string $id ID of the step to save; if empty, create new step.
     */
    public function save_action($id='')
    {
        CSRFProtection::verifyUnsafeRequest();
        if (Request::submitted('submit')) {
            if ($id) {
                $step = CourseWizardStepRegistry::find($id);
                $step->name = Request::get('name');
                $step->classname = Request::get('classname');
                $step->number = Request::int('number');
                $step->enabled = Request::option('enabled') ? 1 : 0;
                if ($step->store()) {
                    PageLayout::postMessage(MessageBox::success(_('Die Daten wurden gespeichert.')));
                } else {
                    PageLayout::postMessage(MessageBox::error(_('Die Daten konnten nicht gespeichert werden.')));
                }
            } else {
                $classname = Request::get('classname');
                // Check if given class name can be found in system.
                if (!class_exists($classname)) {
                    PageLayout::postMessage(MessageBox::error(
                        sprintf(_('Die angegebene PHP-Klasse "%s" wurde nicht gefunden.'), htmlReady($classname))));
                // Class found, now check if it implements the interface definition for wizard steps.
                } else if (!in_array('CourseWizardStep', class_implements($classname) ?: array())) {
                    PageLayout::postMessage(MessageBox::error(
                        sprintf(_('Die angegebene PHP-Klasse "%s" implementiert nicht das Interface CourseWizardStep.'),
                            htmlReady($classname))));
                // All ok, create new database entry.
                } else {
                    $step = new CourseWizardStepRegistry();
                    $step->name = Request::get('name');
                    $step->classname = $classname;
                    $step->number = Request::int('number');
                    $step->enabled = Request::option('enabled') ? 1 : 0;
                    if ($step->store()) {
                        PageLayout::postMessage(MessageBox::success(_('Die Daten wurden gespeichert.')));
                    } else {
                        PageLayout::postMessage(MessageBox::error(_('Die Daten konnten nicht gespeichert werden.')));
                    }
                }
            }
        }
        $this->redirect($this->url_for('admin/coursewizardsteps'));
    }

    /**
     * Asks for confirmation on deleting a step.
     * @param $id ID of the entry to delete
     */
    public function ask_delete_action($id)
    {
        if (Request::isXhr()) {
            $this->response->add_header('X-Title', _('Schritt löschen?'));
        }
        $this->step = CourseWizardStepRegistry::find($id);
    }

    /**
     * Deletes the given entry from step registry.
     * @param $id ID of the entry to delete
     */
    public function delete_action($id)
    {
        CSRFProtection::verifyUnsafeRequest();
        if (Request::submitted('delete')) {
            $step = CourseWizardStepRegistry::find($id);
            if ($step) {
                $name = $step->name;
                if (CourseWizardStepRegistry::unregisterStep($id)) {
                    PageLayout::postMessage(
                        MessageBox::success(sprintf(_('Der Schritt "%s" wurde gelöscht.'), $name)));
                } else {
                    PageLayout::postMessage(
                        MessageBox::error(sprintf(_('Der Schritt %s konnte nicht gelöscht werden.'), $name)));
                }
            }
        }
        $this->redirect($this->url_for('admin/coursewizardsteps'));
    }

}
