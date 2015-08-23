<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author   Rasmus Fuhse <fuhse@data-quest.de>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category Stud.IP
 * @since    3.1
 */

require_once 'app/controllers/authenticated_controller.php';

class Search_CoursesController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        PageLayout::setHelpKeyword("Basis.VeranstaltungenAbonnieren");
        PageLayout::setTitle(_("Veranstaltungssuche"));
        if (Request::option('view')) {
            $_SESSION['sem_portal']['bereich'] = Request::option('view');
        }

        if (!$_SESSION['sem_portal']['bereich']) {
            $_SESSION['sem_portal']['bereich'] = "all";
        }

        Request::set('view', $_SESSION['sem_portal']['bereich']);
        Navigation::activateItem('/search/courses/'.$_SESSION['sem_portal']['bereich']);

        if (Request::option('choose_toplist')) {
            $_SESSION['sem_portal']['toplist'] = Request::option('choose_toplist');
        }

        if (!$_SESSION['sem_portal']['toplist']) {
            $_SESSION['sem_portal']['toplist'] = 4;
        }
    }

    public function index_action()
    {
        if ($_SESSION['sem_portal']['bereich'] != "all") {
            $class = $GLOBALS['SEM_CLASS'][$_SESSION['sem_portal']['bereich']];
            $this->anzahl_seminare_class = $class->countSeminars();
            $sem_status = array_keys($class->getSemTypes());
        } else {
            $sem_status = false;
        }

        $init_data = array( "level" => "f",
            "cmd"=>"qs",
            "show_class"=>$_SESSION['sem_portal']['bereich'],
            "group_by"=>0,
            "default_sem"=> ( ($default_sem = SemesterData::GetSemesterIndexById($_SESSION['_default_sem'])) !== false ? $default_sem : "all"),
            "sem_status"=> $sem_status);

        if (Request::option('reset_all')) $_SESSION['sem_browse_data'] = null;
        $this->sem_browse_obj = new SemBrowse($init_data);
        $sem_browse_data['show_class'] = $_SESSION['sem_portal']['bereich'];

        if (!$GLOBALS['perm']->have_perm("root")){
            $this->sem_browse_obj->target_url="dispatch.php/course/details/";
            $this->sem_browse_obj->target_id="sem_id";
        } else {
            $this->sem_browse_obj->target_url="seminar_main.php";
            $this->sem_browse_obj->target_id="auswahl";
        }

        $this->toplist_entries = $this->getToplistEntries($sem_status);
        $this->controller = $this;
    }

    public function export_results_action()
    {
        $sem_browse_obj = new SemBrowse();
        $tmpfile = basename($sem_browse_obj->create_result_xls());
        if ($tmpfile) {
            $this->redirect(getDownloadLink( $tmpfile, _("ErgebnisVeranstaltungssuche.xls"), 4));
        } else {
            $this->render_nothing();
        }
    }


    protected function getToplistEntries($sem_status) {
        $sql_where_query_seminare = " WHERE 1 ";
        $parameters = array();

        if (!$GLOBALS['perm']->have_perm(get_config('SEM_VISIBILITY_PERM'))) {
            $sql_where_query_seminare .= " AND seminare.visible = 1 ";
        }

        if ($_SESSION['sem_portal']['bereich'] != 'all' && count($sem_status)) {
            $sql_where_query_seminare .= " AND seminare.status IN (?) ";
            $parameters[] = $sem_status;
        }
        switch ($_SESSION['sem_portal']["toplist"]) {
            case 4:
            default:
                $query = "SELECT seminar_id, name, mkdate AS count
                      FROM seminare
                      {$sql_where_query_seminare}
                      ORDER BY mkdate DESC
                      LIMIT 5";
                $statement = DBManager::get()->prepare($query);
                $statement->execute($parameters);
                $top_list = $statement->fetchAll(PDO::FETCH_ASSOC);
                //$toplist =  $this->getToplist(_('neueste Veranstaltungen'), $query, 'date', $parameters);
                break;
            case 1:
                $query = "SELECT seminare.seminar_id, seminare.name, COUNT(seminare.seminar_id) AS count
                      FROM seminare
                      LEFT JOIN seminar_user USING (seminar_id)
                      {$sql_where_query_seminare}
                      GROUP BY seminare.seminar_id
                      ORDER BY count DESC
                      LIMIT 5";
                $statement = DBManager::get()->prepare($query);
                $statement->execute($parameters);
                $top_list = $statement->fetchAll(PDO::FETCH_ASSOC);
                //$toplist = $this->getToplist(_('Teilnehmeranzahl'), $query, 'count', $parameters);
                break;
            case 2:
                $query = "SELECT dokumente.seminar_id, seminare.name, COUNT(dokumente.seminar_id) AS count
                      FROM seminare
                      INNER JOIN dokumente USING (seminar_id)
                      {$sql_where_query_seminare}
                      GROUP BY dokumente.seminar_id
                      ORDER BY count DESC
                      LIMIT 5";
                $statement = DBManager::get()->prepare($query);
                $statement->execute($parameters);
                $top_list = $statement->fetchAll(PDO::FETCH_ASSOC);

                //$toplist =  $this->getToplist(_('die meisten Materialien'), $query, 'count', $parameters);
                break;
            case 3:
                $cache = StudipCacheFactory::getCache();
                $hash  = '/sem_portal/most_active';
                $top_list = unserialize($cache->read($hash));
                if (!$top_list) {
                    // get TopTen of seminars from all ForumModules and add up the
                    // count for seminars with more than one active ForumModule
                    // to get a combined toplist
                    $seminars = array();
                    foreach (PluginEngine::getPlugins('ForumModule') as $plugin) {
                        $new_seminars = $plugin->getTopTenSeminars();
                        foreach ($new_seminars as $sem) {
                            if (!isset($seminars[$sem['seminar_id']])) {
                                $seminars[$sem['seminar_id']] = $sem;
                                $seminars[$sem['seminar_id']]['name'] = $seminars[$sem['seminar_id']]['display'];
                            } else {
                                $seminars[$sem['seminar_id']]['count'] += $sem['count'];
                            }
                        }
                    }
                    // sort the seminars by the number of combined postings
                    usort($seminars, function($a, $b) {
                        if ($a['count'] == $b['count']) {
                            return 0;
                        }
                        return ($a['count'] > $b['count']) ? -1 : 1;
                    });
                    $top_list = $seminars;
                    // use only the first five seminars
                    $top_list = array_slice($top_list, 0, 5);

                    $cache->write($hash, serialize($top_list), 5 * 60);
                }
                break;
        }
        return $top_list;
    }
}
