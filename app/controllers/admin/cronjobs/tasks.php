<?
/**
 * Admin_Cronjobs_Tasks_Controller - Controller class for cronjob tasks
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// tasks.php
//
// Copyright (C) 2013 Jan-Hendrik Willms <tleilax+studip@gmail.com>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

require_once 'app/controllers/authenticated_controller.php';

class Admin_Cronjobs_TasksController extends AuthenticatedController
{
    /**
     * Set up this controller.
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        Navigation::activateItem('/admin/config/cronjobs');
        PageLayout::setTitle(_('Cronjob-Verwaltung') . ' - ' . _('Aufgaben'));

        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;charset=windows-1252');
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }
    }

    /**
     * Displays all available tasks.
     *
     * @param int $page Which page to display
     */
    public function index_action($page = 1)
    {
        $this->max_per_page = Config::get()->ENTRIES_PER_PAGE;
        $this->total        = CronjobTask::countBySql('1');
        $this->page         = max(1, min($page, ceil($this->total / $this->max_per_page)));

        $limit = sprintf(" LIMIT %u, %u", ($this->page - 1) * $this->max_per_page, $this->max_per_page);
        $this->tasks = CronjobTask::findBySQL('1' . $limit);

        // Infobox image was produced from an image by Robbert van der Steeg
        // http://www.flickr.com/photos/robbie73/5924985913/
        $this->setInfoboxImage(Assets::image_path('infobox/time.jpg'));

        // Navigation
        $cronjobs = sprintf('<a href="%s">%s</a>',
                            $this->url_for('admin/cronjobs/schedules'),
                            _('Cronjobs verwalten'));
        $this->addToInfobox(_('Navigation'), $cronjobs);

        $tasks = sprintf('<a href="%s"><strong>%s</strong></a>',
                         $this->url_for('admin/cronjobs/tasks'),
                         _('Aufgaben verwalten'));
        $this->addToInfobox(_('Navigation'), $tasks, 'icons/16/red/arr_1right');

        $logs = sprintf('<a href="%s">%s</a>',
                        $this->url_for('admin/cronjobs/logs'),
                        _('Logs anzeigen'));
        $this->addToInfobox(_('Navigation'), $logs);
    }

    /**
     * Activates a tasks.
     *
     * @param String $id Id of the task in question
     * @param int    $page Return to this page after activating (optional)
     */
    public function activate_action($id, $page = 1)
    {
        $task = CronjobTask::find($id);
        $task->active = 1;
        $task->store();

        if (!Request::isXhr()) {
            // Report how many actual cronjobs were activated
            $activated = $task->schedules->filter(function ($schedule) { return $schedule->active; })->count();

            $message = sprintf(_('Die Aufgabe und %u Cronjob(s) wurden aktiviert.'), $activated);
            PageLayout::postMessage(MessageBox::success($message));
        }
        $this->redirect('admin/cronjobs/tasks/index/' . $page . '#task-' . $id);
    }

    /**
     * Deactivates a tasks.
     *
     * @param String $id Id of the task in question
     * @param int    $page Return to this page after deactivating (optional)
     */
    public function deactivate_action($id, $page = 1)
    {
        $task = CronjobTask::find($id);
        $task->active = 0;
        $task->store();

        if (!Request::isXhr()) {
            // Report how many actual cronjobs were activated
            $deactivated = $task->schedules->filter(function ($schedule) { return $schedule->active; })->count();

            $message = sprintf(_('Die Aufgabe und %u Cronjob(s) wurden deaktiviert.'), $deactivated);
            PageLayout::postMessage(MessageBox::success($message));
        }
        $this->redirect('admin/cronjobs/tasks/index/' . $page . '#task-' . $id);
    }

    /**
     * Deletes a tasks.
     *
     * @param String $id Id of the task in question
     * @param int    $page Return to this page after deleting (optional)
     */
    public function delete_action($id, $page = 1)
    {
        $task = CronjobTask::find($id);
        $deleted = $task->schedules->count();
        $task->delete();

        $message = sprintf(_('Die Aufgabe und %u Cronjob(s) wurden gel�scht.'), $deleted);
        PageLayout::postMessage(MessageBox::success($message));

        $this->redirect('admin/cronjobs/tasks/index/' . $page);
    }

    /**
     * Performs a bulk operation on a set of tasks. Operation can be either
     * activating, deactivating or deleting.
     *
     * @param int    $page Return to this page afterwarsd (optional)
     */
    public function bulk_action($page = 1)
    {
        $action = Request::option('action');
        $ids    = Request::optionArray('ids');
        $tasks  = CronjobTask::findMany($ids);

        if ($action === 'activate') {
            $tasks = array_filter($tasks, function ($item) { return !$item->active; });
            foreach ($tasks as $task) {
                $task->active = 1;
                $task->store();
            }

            $n = count($tasks);
            $message = sprintf(ngettext('%u Aufgabe wurde aktiviert.', '%u Aufgaben wurden aktiviert.', $n), $n);
            PageLayout::postMessage(MessageBox::success($message));
        } else if ($action === 'deactivate') {
            $tasks = array_filter($tasks, function ($item) { return $item->active; });
            foreach ($tasks as $task) {
                $task->active = 0;
                $task->store();
            }

            $n = count($tasks);
            $message = sprintf(ngettext('%u Aufgabe wurde deaktiviert.', '%u Aufgaben wurden deaktiviert.', $n), $n);
            PageLayout::postMessage(MessageBox::success($message));
        } else if ($action === 'delete') {
            foreach ($tasks as $task) {
                $task->delete();
            }

            $n = count($tasks);
            $message = sprintf(ngettext('%u Aufgabe wurde gel�scht.', '%u Aufgaben wurden gel�scht.', $n), $n);
            PageLayout::postMessage(MessageBox::success($message));
        }

        $this->redirect('admin/cronjobs/tasks/index/' . $page);
    }

    /**
     * Executes a single task
     *
     * @param String $task_id Id of the task to be executed
     */
    public function execute_action($task_id)
    {
        $this->task = new CronjobTask($task_id);

        if (Request::isPost()) {
            $parameters = Request::getArray('parameters');
            $parameters = $parameters[$task->id];

            ob_start();
            $this->task->engage(null, $parameters);
            $this->result = ob_get_clean();
        } else {
            header('X-Title: ' . _('Cronjob-Aufgabe ausf�hren'));
            $this->schedule = new CronjobSchedule();
        }
    }
}