<?php
# Lifter010: TODO
/**
 * SeminarSearch.class.php
 * class to adapt StudipSemSearch to Quicksearch
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest>, Suchi & Berg GmbH <info@data-quest.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

require_once 'lib/classes/StudipSemSearchHelper.class.php';
require_once 'lib/classes/searchtypes/SearchType.class.php';

class SeminarSearch extends SearchType
{
    private $styles = array(
                    'name' => 'Name',
                    'number-name' => "TRIM(CONCAT_WS(' ', VeranstaltungsNummer, Name))",
                    'number-name-lecturer' => "CONCAT_WS(' ', TRIM(CONCAT_WS(' ', VeranstaltungsNummer, Name)), CONCAT('(', GROUP_CONCAT(Nachname ORDER BY position,Nachname SEPARATOR ', '),')'))"
                    );
    private $resultstyle;

    function __construct($resultstyle = 'name')
    {
        $this->resultstyle = $resultstyle;
    }

    /**
     * title of the search like "search for courses" or just "courses"
     * @return string
     */
    public function getTitle() {
        return _("Veranstaltungen suchen");
    }

    /**
     * Returns the results to a given keyword. To get the results is the
     * job of this routine and it does not even need to come from a database.
     * The results should be an array in the form
     * array (
     *   array($key, $name),
     *   array($key, $name),
     *   ...
     * )
     * where $key is an identifier like user_id and $name is a displayed text
     * that should appear to represent that ID.
     * @param keyword: string
     * @param array $contextual_data an associative array with more variables
     * @param int $limit maximum number of results (default: all)
     * @param int $offset return results starting from this row (default: 0)
     * @return array
     */
     public function getResults($keyword, $contextual_data = array(), $limit = PHP_INT_MAX, $offset = 0) {
         $search_helper = new StudipSemSearchHelper();
         $search_helper->setParams(
             array(
                 'quick_search' => $keyword,
                 'qs_choose' => $contextual_data['search_sem_qs_choose'] ? $contextual_data['search_sem_qs_choose'] : 'all',
                 'sem' => isset($contextual_data['search_sem_sem']) ? $contextual_data['search_sem_sem'] : 'all',
                 'category' => $contextual_data['search_sem_category'],
                 'scope_choose' => $contextual_data['search_sem_scope_choose'],
                 'range_choose' => $contextual_data['search_sem_range_choose']),
             !(is_object($GLOBALS['perm'])
                 && $GLOBALS['perm']->have_perm(
                     Config::Get()->SEM_VISIBILITY_PERM)));
         $search_helper->doSearch();
         $result = $search_helper->getSearchResultAsArray();

         if (empty($result)) {
             return array();
         }
         $style = $this->styles[$this->resultstyle] ?: $this->styles['name'];

         $query = "SELECT s.Seminar_id, {$style}, Name
                   FROM seminare AS s
                   LEFT JOIN seminar_user AS su ON (su.Seminar_id = s.Seminar_id AND su.status='dozent')
                   LEFT JOIN auth_user_md5 USING (user_id)
                   WHERE s.Seminar_id IN (?)
                   GROUP BY s.Seminar_id";
         $statement = DBManager::get()->prepare($query);
         $statement->execute(array(
            array_slice($result, $offset, $limit) ?: ''
         ));
         return $statement->fetchAll(PDO::FETCH_NUM);
     }


    /**
     * Returns the path to this file, so that this class can be autoloaded and is
     * always available when necessary.
     * Should be: "return __file__;"
     *
     * @return string   path to this file
     */
    public function includePath()
    {
        return __FILE__;
    }
}
