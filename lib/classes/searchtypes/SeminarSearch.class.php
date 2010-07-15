<?php
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
     * @return array
     */
     public function getResults($keyword, $contextual_data = array()) {
         $search_helper = new StudipSemSearchHelper();
         $search_helper->setParams(
             array(
                 'quick_search' => $keyword,
                 'qs_choose' => $contextual_data['search_sem_qs_choose'] ? $contextual_data['search_sem_qs_choose'] : 'all',
                 'sem' => isset($contextual_data['search_sem_sem']) ? $contextual_data['search_sem_sem'] : 'all',
                 'category' => $options['search_sem_category'],
                 'scope_choose' => $options['search_sem_scope_choose'],
                 'range_choose' => $options['search_sem_range_choose']),
             !(is_object($GLOBALS['perm'])
                 && $GLOBALS['perm']->have_perm(
                     Config::Get()->SEM_VISIBILITY_PERM)));
         $search_helper->doSearch();
         $result = $search_helper->getSearchResultAsArray();
         
         if (empty($result)) {
             return array();
         }
         
         $db = DBManager::get();
         return $db->query("SELECT Seminar_id, Name FROM seminare WHERE Seminar_id IN ('".join("','", $result)."')")->fetchAll(PDO::FETCH_NUM);
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
