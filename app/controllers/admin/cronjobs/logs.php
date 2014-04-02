<?
/**
 * Admin_Cronjobs_LogsController - Controller class for the logs of cronjobs
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// logs.php
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

class Admin_Cronjobs_LogsController extends AuthenticatedController
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
        PageLayout::setTitle(_('Cronjob-Verwaltung') . ' - ' . _('Log-Eintr�ge'));

        if (empty($_SESSION['cronlog-filter'])) {
            $_SESSION['cronlog-filter'] = array(
                'where'  => '1',
                'values' => array(),
            );
        }

        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }
    }

    /**
     * Displays all available log entries according to the set filters.
     *
     * @param int $page Which page to display
     */
    public function index_action($page = 1)
    {
        $filter = $_SESSION['cronlog-filter'];
        
        $this->max_per_page   = Config::get()->ENTRIES_PER_PAGE;
        $this->total          = CronjobLog::countBySql();
        $this->total_filtered = CronjobLog::countBySql($filter['where']);
        $this->page           = max(1, min($page, ceil($this->total_filtered / $this->max_per_page)));

        $order = " ORDER BY executed DESC";
        $limit = sprintf(" LIMIT %u, %u", ($this->page - 1) * $this->max_per_page, $this->max_per_page);
        $this->logs = CronjobLog::findBySQL($filter['where'] . $order . $limit);

        // Filters
        $this->schedules  = CronjobSchedule::findBySql('1');
        $this->tasks      = CronjobTask::findBySql('1');
        $this->filter     = $filter['values'];

        // Infobox image was produced from an image by Robbert van der Steeg
        // http://www.flickr.com/photos/robbie73/5924985913/
        $this->setInfoboxImage(Assets::image_path('sidebar/admin-sidebar.png'));

        // Navigation
        $cronjobs = sprintf('<a href="%s">%s</a>',
                            $this->url_for('admin/cronjobs/schedules'),
                            _('Cronjobs verwalten'));
        $this->addToInfobox(_('Navigation'), $cronjobs);

        $tasks = sprintf('<a href="%s">%s</a>',
                         $this->url_for('admin/cronjobs/tasks'),
                         _('Aufgaben verwalten'));
        $this->addToInfobox(_('Navigation'), $tasks);

        $logs = sprintf('<a href="%s"><strong>%s</strong></a>',
                        $this->url_for('admin/cronjobs/logs'),
                        _('Logs anzeigen'));
        $this->addToInfobox(_('Navigation'), $logs, 'icons/16/red/arr_1right');
    }

    /**
     * Sets the filters for the log view.
     * Filters are stored in the session.
     */
    public function filter_action()
    {
        $filter     = array_filter(Request::optionArray('filter'));
        $conditions = array();

        if (!empty($filter['status'])) {
            $conditions[] = ($filter['status'] === 'passed')
                          ? "exception = 'N;'"
                          : "exception != 'N;'";
        }

        if (!empty($filter['schedule_id'])) {
            $conditions[] = "schedule_id = " . DBManager::get()->quote($filter['schedule_id']);
        }

        if (!empty($filter['task_id'])) {
            $temp = CronjobSchedule::findByTask_id($filter['task_id']);
            $temp = SimpleORMapCollection::createFromArray($temp);
            $schedule_ids = $temp->pluck('schedule_id') ?: null;
            $conditions[] = "schedule_id IN (" . DBManager::get()->quote($schedule_ids). ")";
        }

        $_SESSION['cronlog-filter'] = array(
            'where'  => implode(" AND " , $conditions) ?: '1',
            'values' => $filter,
        );
        $this->redirect('admin/cronjobs/logs');
    }

    /**
     * Sets the filters for the schedule view to a specific schedule id.
     *
     * @param String $schedule_id Id of the schedule in question
     */
    public function schedule_action($schedule_id)
    {
        $this->redirect('admin/cronjobs/logs/filter?filter[schedule_id]=' . $schedule_id);
    }

    /**
     * Sets the filters for the schedule view to a specific task id.
     *
     * @param String $task_id Id of the task in question
     */
    public function task_action($task_id)
    {
        $this->redirect('admin/cronjobs/logs/filter?filter[task_id]=' . $task_id);
    }

    /**
     * Displays a log entry.
     *
     * @param String $id Id of the log entry in question
     */
    public function display_action($id)
    {
        $this->log = CronjobLog::find($id);

        $title = sprintf(_('Logeintrag f�r Cronjob "%s" anzeigen'),
                         $this->log->schedule->title);
        if (Request::isXhr()) {
            header('X-Title: ' . $title);
        } else {
            PageLayout::setTitle($title);
        }
    }

    /**
     * Deletes a log entry.
     *
     * @param String $id Id of the schedule in question
     */
    public function delete_action($id, $page = 1)
    {
        CronjobLog::find($id)->delete();

        $message = sprintf(_('Der Logeintrag wurde gel�scht.'), $deleted);
        PageLayout::postMessage(MessageBox::success($message));

        $this->redirect('admin/cronjobs/logs/index/' . $page);
    }

    /**
     * Performs a bulk operation on a set of log entries. The only supported
     * operation at the moment is deleting.
     *
     * @param int    $page Return to this page afterwarsd (optional)
     */
    public function bulk_action($page = 1)
    {
        $action = Request::option('action');
        $ids    = Request::optionArray('ids');
        $logs   = CronjobLog::findMany($ids);

        if ($action === 'delete') {
            foreach ($logs as $log) {
                $log->delete();
            }

            $n = count($logs);
            $message = sprintf(ngettext('%u Logeintrag wurde gel�scht.', '%u Logeintr�ge wurden gel�scht.', $n), $n);
            PageLayout::postMessage(MessageBox::success($message));
        }

        $this->redirect('admin/cronjobs/logs/index/' . $page);
    }

}