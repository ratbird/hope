<?php
/*
 * SettingsController - Controller for all setting related pages (formerly edit_about)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */

require_once 'settings.php';
require_once 'lib/classes/ModulesNotification.class.php';

class Settings_NotificationController extends Settings_SettingsController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (!get_config('MAIL_NOTIFICATION_ENABLE')) {
            $message = _('Die Benachrichtigungsfunktion wurde in den Systemeinstellungen nicht freigeschaltet.');
            throw new AccessDeniedException($message);
        }

        if (!$GLOBALS['auth']->is_authenticated() || $GLOBALS['perm']->have_perm('admin')) {
            throw new AccessDeniedException(_('Sie dürfen diesen Bereich nicht betreten.'));
        }

        PageLayout::setHelpKeyword('Basis.MyStudIPBenachrichtigung');
        PageLayout::setTitle(_('Benachrichtigung über neue Inhalte anpassen'));
        PageLayout::setTabNavigation('/links/settings');
        Navigation::activateItem('/links/settings/notification');
        SkipLinks::addIndex(_('Benachrichtigung über neue Inhalte anpassen'), 'layout_content', 100);

        $infobox_message = _('Stud.IP kann Sie bei Änderungen in den einzelnen Inhaltsbereichen Ihrer Veranstaltungen automatisch '
                             .'per Email informieren.<br>'
                             .'Geben Sie hier an, über welche Änderungen Sie informiert werden wollen.');
        $this->setInfoboxImage('infobox/messages.jpg');
        $this->addToInfobox(_('Informationen'), $infobox_message, 'icons/16/black/info');
    }

    public function index_action($action = null, $id = null)
    {
        $group_field = UserConfig::get($this->user->user_id)->MY_COURSES_GROUPING ?: 'not_grouped';

        if ($group_field == 'sem_tree_id'){
            $add_fields = ',sem_tree_id';
            $add_query = "LEFT JOIN seminar_sem_tree sst ON (sst.seminar_id=seminare.Seminar_id)";
        } else if ($group_field == 'dozent_id'){
            $add_fields = ', su1.user_id as dozent_id';
            $add_query = "LEFT JOIN seminar_user as su1 ON (su1.seminar_id=seminare.Seminar_id AND su1.status='dozent')";
        }

        $dbv = new DbView();

        $query = "SELECT seminare.VeranstaltungsNummer AS sem_nr, seminare.Name, seminare.Seminar_id,
                         seminare.status AS sem_status, seminar_user.gruppe, seminare.visible,
                         {$dbv->sem_number_sql} AS sem_number, {$dbv->sem_number_end_sql} AS sem_number_end
                         {$add_fields}
                  FROM seminar_user
                  LEFT JOIN seminare  USING (Seminar_id)
                  {$add_query}
                  WHERE seminar_user.user_id = ?";
        if (get_config('DEPUTIES_ENABLE')) {
            $query .= " UNION ".getMyDeputySeminarsQuery('notification', $dbv->sem_number_sql, $dbv->sem_number_end_sql, $add_fields, $add_query);
        }
        $query .= " ORDER BY sem_nr ASC";

        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user->user_id));
        $seminars = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!count($seminars)) {
            $message = sprintf(_('Sie haben zur Zeit keine Veranstaltungen abonniert, an denen Sie teilnehmen k&ouml;nnen. Bitte nutzen Sie %s<b>Veranstaltung suchen / hinzuf&uuml;gen</b>%s um neue Veranstaltungen aufzunehmen.'),
                               '<a href="sem_portal.php">', '</a>');
            PageLayout::postMessage(Messagebox::info($message));
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
            $this->render_nothing();
            return;
        }

        $modules = new ModulesNotification();
        $enabled_modules = $modules->getGlobalEnabledNotificationModules('sem');

        $groups = array();
        $my_sem = array();
        foreach ($seminars as $seminar) {
            $my_sem[$seminar['Seminar_id']] = array(
                'obj_type'       => "sem",
                'name'           => $seminar['Name'],
                'visible'        => $seminar['visible'],
                'gruppe'         => $seminar['gruppe'],
                'sem_status'     => $seminar['sem_status'],
                'sem_number'     => $seminar['sem_number'],
                'sem_number_end' => $seminar['sem_number_end']
            );
            if ($group_field){
                fill_groups($groups, $seminar[$group_field], array(
                    'seminar_id' => $seminar['Seminar_id'],
                    'name'       => $seminar['Name'],
                    'gruppe'     => $seminar['gruppe']
                ));
            }
        }

        if ($group_field == 'sem_number') {
            correct_group_sem_number($groups, $my_sem);
        } else {
            add_sem_name($my_sem);
        }

        sort_groups($group_field, $groups);
        $group_names = get_group_names($group_field, $groups);
        $notifications = $modules->getModuleNotification();
        $open = UserConfig::get($this->user->user_id)->MY_COURSES_OPEN_GROUPS;
        $checked = array();
        foreach ($groups as $group_id => $group_members){
            if (!isset($open[$group_id])) {
                continue;
            }
            foreach ($group_members as $member){
                $checked[$member['seminar_id']] = array();
                foreach (array_values($enabled_modules) as $index => $m_data) {
                    $checked[$member['seminar_id']][$index] = $modules->isBit($notifications[$member['seminar_id']], $m_data['id']);
                }
                $checked[$member['seminar_id']]['all'] = count($enabled_modules) === count(array_filter($checked[$member['seminar_id']]));
            }
        }

        $this->modules       = $enabled_modules;
        $this->groups        = $groups;
        $this->group_names   = $group_names;
        $this->group_field   = $group_field;
        $this->open          = $open;
        $this->seminars      = $my_sem;
        $this->notifications = $modules->getModuleNotification();
        $this->checked       = $checked;
    }

    public function store_action()
    {
        $this->check_ticket();

        $modules = new ModulesNotification();
        $modules->setModuleNotification(Request::getArray('m_checked'), 'sem');

        $this->reportSuccess(_('Die Einstellungen wurden gespeichert.'));
        $this->redirect('settings/notification');
    }

    public function open_action($id)
    {
        $open = UserConfig::get($this->user->user_id)->MY_COURSES_OPEN_GROUPS;
        $open[$id] = true;
        UserConfig::get($this->user->user_id)->store('MY_COURSES_OPEN_GROUPS', $open);
        $this->redirect('settings/notification');
    }

    public function close_action($id)
    {
        $open = UserConfig::get($this->user->user_id)->MY_COURSES_OPEN_GROUPS;
        unset($open[$id]);
        UserConfig::get($this->user->user_id)->store('MY_COURSES_OPEN_GROUPS', $open);
        $this->redirect('settings/notification');
    }
}
