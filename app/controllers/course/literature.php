<?php
/**
 * Literaturübersicht von Stud.IP
 *
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
require_once('lib/classes/StudipLitList.class.php');

class Course_LiteratureController extends AuthenticatedController
{
    /**
     * Before filter, set up the page by initializing the session and checking
     * all conditions.
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (!Config::Get()->LITERATURE_ENABLE ) {
            throw new AccessDeniedException(_('Die Literaturübersicht ist nicht aktiviert.'));
        }

        PageLayout::setHelpKeyword('Basis.Literatur');
        Navigation::activateItem('/course/literature/view');
        PageLayout::setTitle($SessSemName["header_line"]. " - " . _("Literatur"));

        checkObject(); // do we have an open object?
        checkObjectModule('literature');
        object_set_visit_module('literature');
    }

    /**
     * Displays a page.
     */
    public function index_action($id = null)
    {
        $this->list = StudipLitList::GetFormattedListsByRange($_SESSION["SessionSeminar"], object_get_visit($_SESSION["SessionSeminar"], "literature"));
        
    }
    
}
