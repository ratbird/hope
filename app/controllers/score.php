<?php
/**
 * score.php - Stud.IP Highscore List
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
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2 or later
 * @category    Stud.IP
 * @since       2.4
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/score.class.php';

class ScoreController extends AuthenticatedController
{
    /**
     * Set up this controller.
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     */
    public function before_filter(&$action, &$args)
    {
        // Interpret every action other than 'index', 'publish' or 'unpublish'
        // as page number
        if (!in_array($action, words('index publish unpublish'))) {
            array_unshift($args, (int)$action);
            $action = 'index';
        }

        parent::before_filter($action, $args);
        
        if (!Config::Get()->SCORE_ENABLE) {
            throw new AccessDeniedException(_('Die Rangliste und die Score-Funktion sind nicht aktiviert.'));
        }

        PageLayout::setHelpKeyword('Basis.VerschiedenesScore'); // external help keyword
        PageLayout::setTitle(_('Rangliste'));
        Navigation::activateItem('/community/score');

        $this->score = new Score($GLOBALS['user']->id);
    }

    /**
     * Displays the global ranking list.
     *
     * @param int $page Page of the ranking list to be displayed.
     */
    public function index_action($page = 1)
    {
        $vis_query = get_vis_query('b');

        $query = "SELECT COUNT(*)
                  FROM user_info AS a
                  LEFT JOIN auth_user_md5 AS b USING (user_id)
                  WHERE score > 0 AND locked = 0 AND {$vis_query}";
        $statement = DBManager::get()->query($query);
        $count        = $statement->fetchColumn();

        // Calculate offsets
        $max_per_page = get_config('ENTRIES_PER_PAGE');
        $max_pages    = ceil($count / $max_per_page);

        if ($page < 1) {
            $page = 1;
        } elseif ($page > $max_pages) {
            $page = $max_pages;
        }

        $offset = max(0, ($page - 1) * $max_per_page);

        // Liste aller die mutig (oder eitel?) genug sind
        $query = "SELECT a.user_id,username,score,geschlecht, {$GLOBALS['_fullname_sql']['full']} AS fullname
                  FROM user_info AS a
                  LEFT JOIN auth_user_md5 AS b USING (user_id)
                  WHERE score > 0 AND locked = 0 AND {$vis_query}
                  ORDER BY score DESC
                  LIMIT " . (int)$offset . "," . (int)$max_per_page;
        $result = DBManager::get()->query($query);

        $persons = array();
        while ($row = $result->fetch()) {
            $row['is_king'] = StudipKing::is_king($row['user_id'], true);
            $persons[] = $row;
        }

        $this->persons         = $persons;
        $this->numberOfPersons = $count;
        $this->page            = $page;
        $this->offset          = $offset;
        $this->max_per_page    = $max_per_page;
        
        // Set up sidebar and helpbar
        
        $sidebar = Sidebar::get();
        $sidebar->setImage('sidebar/medal-sidebar.png');

        $actions = new ActionsWidget();
        $published = $this->score->ReturnPublik();
        $actions->addLink(_('Ihren Wert veröffentlichen'),
                          $this->url_for($published ? 'score/unpublish' : 'score/publish'),
                          $published ? 'icons/16/black/checkbox-checked.png' : 'icons/16/black/checkbox-unchecked.png');
        $sidebar->addWidget($actions);

        $helpbar = Helpbar::get();
        $helpbar->addPlainText(_('Information:'),
                               _('Auf dieser Seite können Sie abrufen, wie weit Sie in der '
                                .'Stud.IP-Rangliste aufgestiegen sind. Je aktiver Sie sich '
                                .'im System verhalten, desto höher klettern Sie!'),
                               'icons/16/white/info.png');

        $helpbar->addPlainText('',
                               _('Sie erhalten auf der Profilseite von MitarbeiternInnen an '
                                .'Einrichtungen auch weiterführende Informationen, wie '
                                .'Sprechstunden und Raumangaben.'));
    }

    /**
     * Publish user's score / add user's score to the ranking list.
     */
    public function publish_action()
    {
        $this->score->PublishScore();
        PageLayout::postMessage(MessageBox::success(_('Ihr Wert wurde auf der Rangliste veröffentlicht.')));
        $this->redirect('score');
    }

    /**
     * Removes the user's score from the ranking list.
     */
    public function unpublish_action()
    {
        $this->score->KillScore();
        PageLayout::postMessage(MessageBox::success(_('Ihr Wert wurde von der Rangliste gelöscht.')));
        $this->redirect('score');
    }
}
