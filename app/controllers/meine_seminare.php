<?php
/**
 * meine_seminare.php - Controller for user and seminar related
 * pages under "Meine Veranstaltungen"
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2 or later
 * @category    Stud.IP
 * @since       2.4
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/meine_seminare_func.inc.php';

class MeineSeminareController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (!$GLOBALS['auth']->is_authenticated() || $GLOBALS['user']->id === 'nobody' || $GLOBALS['perm']->have_perm('admin')) {
            throw new AccessDeniedException();
        }

        $GLOBALS['perm']->check('user');
    }

    /**
     * Seminar group administration - cluster your seminars by colors or
     * change grouping mechanism
     */
    public function groups_action()
    {
        PageLayout::setTitle(_('Meine Veranstaltungen') . ' - ' . _('Gruppenzuordnung'));
        PageLayout::setHelpKeyword('Basis.VeranstaltungenOrdnen');
        Navigation::activateItem('/browse/my_courses/groups');

        $forced_grouping     = get_config('MY_COURSES_FORCE_GROUPING');
        $no_grouping_allowed = ($forced_grouping == 'not_grouped' || !in_array($forced_grouping, getValidGroupingFields()));

        $group_field  = $GLOBALS['user']->cfg->MY_COURSES_GROUPING ?: $forced_grouping;
        $_my_sem_open = $GLOBALS['user']->cfg->MY_COURSES_OPEN_GROUPS;

        $groups = array();
        $add_fields = '';
        $add_query  = '';

        if (Request::option('open_my_sem')) {
            $_my_sem_open[Request::option('open_my_sem')] = true;
            $GLOBALS['user']->cfg->store('MY_COURSES_OPEN_GROUPS', $_my_sem_open);
        }
        if (Request::option('close_my_sem')) {
            unset($_my_sem_open[Request::option('close_my_sem')]);
            $GLOBALS['user']->cfg->store('MY_COURSES_OPEN_GROUPS', $_my_sem_open);
        }

        if ($group_field == 'sem_tree_id'){
            $add_fields = ', sem_tree_id';
            $add_query = "LEFT JOIN seminar_sem_tree sst ON (sst.seminar_id=seminare.Seminar_id)";
        } else if ($group_field == 'dozent_id'){
            $add_fields = ', su1.user_id as dozent_id';
            $add_query = "LEFT JOIN seminar_user as su1 ON (su1.seminar_id=seminare.Seminar_id AND su1.status='dozent')";
        }

        $dbv = new DbView();

        $query = "SELECT seminare.VeranstaltungsNummer AS sem_nr, seminare.Name, seminare.Seminar_id, 
                         seminare.status AS sem_status, seminar_user.gruppe, seminare.visible,
                         {$dbv->sem_number_sql} AS sem_number,
                         {$dbv->sem_number_end_sql} AS sem_number_end {$add_fields}
                  FROM seminar_user
                  LEFT JOIN seminare USING (Seminar_id)
                  {$add_query}
                  WHERE seminar_user.user_id = ?";
        if (get_config('DEPUTIES_ENABLE')) {
            $query .= " UNION "
                    . getMyDeputySeminarsQuery('gruppe', $dbv->sem_number_sql, $dbv->sem_number_end_sql, $add_fields, $add_query);
        }
        $query .= " ORDER BY sem_nr ASC";

        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($GLOBALS['user']->id));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $my_sem[$row['Seminar_id']] = array(
                'obj_type'       => 'sem',
                'name'           => $row['Name'],
                'visible'        => $row['visible'],
                'gruppe'         => $row['gruppe'],
                'sem_status'     => $row['sem_status'],
                'sem_number'     => $row['sem_number'],
                'sem_number_end' => $row['sem_number_end'],
            );
            if ($group_field) {
                fill_groups($groups, $row[$group_field], array(
                    'seminar_id' => $row['Seminar_id'],
                    'name'       => $row['Name'],
                    'gruppe'     => $row['gruppe']
                ));
            }
        }

        if ($group_field == 'sem_number') {
            correct_group_sem_number($groups, $my_sem);
        } else {
            add_sem_name($my_sem);
        }

        sort_groups($group_field, $groups);

        // Ensure that a seminar is never in multiple groups
        $sem_ids = array();
        foreach ($groups as $group_id => $seminars) {
            foreach ($seminars as $index => $seminar) {
                if (in_array($seminar['seminar_id'], $sem_ids)) {
                    unset($seminars[$index]);
                } else {
                    $sem_ids[] = $seminar['seminar_id'];
                }
            }
            if (empty($seminars)) {
                unset($groups[$group_id]);
            } else {
                $groups[$group_id] = $seminars;
            }
        }

        $this->no_grouping_allowed = $no_grouping_allowed;
        $this->groups              = $groups;
        $this->group_names         = get_group_names($group_field, $groups);
        $this->group_field         = $group_field;
        $this->my_sem              = $my_sem;
        $this->_my_sem_open        = $_my_sem_open;

        // Add infobox
        $this->setInfoBoxImage('sidebar/seminar-sidebar.png');
        $this->addToInfobox(_('Informationen'),
                            _('Hier können Sie Ihre Veranstaltungen in Farbgruppen einordnen und '
                             .'eine Gliederung nach Kategorien festlegen.'),
                            'icons/16/black/info');
        $this->addToInfobox(_('Informationen'),
                            _('Die Darstellung unter <b>meine Veranstaltungen</b> wird entsprechend '
                             .'den Gruppen sortiert bzw. entsprechend der gewählten Kategorie gegliedert.'));
        $groupables = array(
            'sem_number'  => _('Semester'),
            'sem_tree_id' => _('Studienbereich'),
            'sem_status'  => _('Typ'),
            'gruppe'      => _('Farbgruppen'),
            'dozent_id'   => _('Dozenten'),
        );
        $groupselect = '<form action="'.$this->url_for('meine_seminare/store_groups').'" method="post"><select name="select_group_field">';
        if ($no_grouping_allowed) {
            $groupselect .= '<option value="not_grouped" '.($group_field == 'not_grouped' ? 'selected' : '').'>';
            $groupselect .= _('keine Gliederung');
            $groupselect .= '</option>';
        }
        foreach ($groupables as $key => $label) {
           $groupselect .= "<option value='$key'".($group_field == $key ? 'selected':'').">$label</option>";
        }
        $groupselect .= '</select>';
        $groupselect .= Assets::input('icons/16/blue/accept.png', array('class' => 'middle', 'title' => _('Gruppierung ändern')));
        $groupselect .= CSRFProtection::tokenTag();
        $groupselect .= '<form>';
        $this->addToInfobox(_('Kategorie zur Gliederung'), $groupselect);
    }

    /**
     * Storage function for the groups action.
     * Stores selected grouping category and actual group settings.
     */
    public function store_groups_action()
    {
        CSRFProtection::verifyUnsafeRequest();
        if (Request::isPost()) {
            $GLOBALS['user']->cfg->store('MY_COURSES_GROUPING', Request::get('select_group_field'));
            $gruppe = Request::getArray('gruppe');
            if (!empty($gruppe)){
                $query = "UPDATE seminar_user SET gruppe = ? WHERE Seminar_id = ? AND user_id = ?";
                $user_statement = DBManager::get()->prepare($query);

                $query = "UPDATE deputies SET gruppe = ? WHERE range_id = ? AND user_id = ?";
                $deputy_statement = DBManager::get()->prepare($query);

                foreach ($gruppe as $key => $value) {
                    $user_statement->execute(array($value, $key, $GLOBALS['user']->id));
                    $updated = $user_statement->rowCount();

                    if ($deputies_enabled && !$updated) {
                        $deputy_statement->execute(array($value, $key, $GLOBALS['user']->id));
                    }
                }
            }
            PageLayout::postMessage(MessageBox::success(_('Ihre Einstellungen wurden übernommen.')));
        }
        $this->redirect('meine_seminare/groups');
    }

    /**
     * Overview for achived courses
     */
    public function archive_action()
    {
        // Auch im Adminbereich gesetzte Veranstaltungen muessen geloescht werden.
        $_SESSION['links_admin_data'] = ''; // TODO: Still neccessary?

        PageLayout::setTitle(_('Meine archivierten Veranstaltungen'));
        PageLayout::setHelpKeyword('Basis.MeinArchiv');
        Navigation::activateItem('/browse/my_courses/archive');
        SkipLinks::addIndex(_('Hauptinhalt'), 'layout_content', 100);

        $sortby = Request::option('sortby', 'name');

        $query = "SELECT semester, name, seminar_id, status, archiv_file_id,
                         LENGTH(forumdump) > 0 AS forumdump, # Test for existence
                         LENGTH(wikidump) > 0 AS wikidump    # Test for existence
                  FROM archiv_user
                  LEFT JOIN archiv USING (seminar_id)
                  WHERE user_id = :user_id
                  GROUP BY seminar_id
                  ORDER BY start_time DESC, :sortby";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':user_id', $GLOBALS['user']->id);
        $statement->bindValue(':sortby', $sortby, StudipPDO::PARAM_COLUMN);
        $statement->execute();
        $this->seminars = $statement->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC); // Groups by semester

        $count = DBManager::get()->query("SELECT COUNT(*) FROM archiv")->fetchColumn();

        $this->setInfoboxImage('sidebar/seminar-archive-sidebar.png');
        $this->addToInfobox(_('Information:'),
                            sprintf(_('Es befinden sich zur Zeit %s Veranstaltungen im Archiv.'), $count),
                            'icons/16/black/info.png');
        $this->addToInfobox(_('Aktionen:'),
                            sprintf(_('Um Informationen über andere archivierte Veranstaltungen '
                                     .'anzuzeigen nutzen Sie die %sSuche im Archiv%s.'),
                                    '<a href="' . URLHelper::getLink('archiv.php') . '">',
                                    '</a>'),
                            'icons/16/black/search.png');

        // TODO: This should be removed as soon as archive_assi uses PageLayout::postMessage() 
        if (isset($_SESSION['archive_message'])) {
            $this->message = $_SESSION['archive_message'];
            unset($_SESSION['archive_message']);
        }
    }
}
