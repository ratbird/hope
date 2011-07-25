<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* header
*
* head line of Stud.IP
*
* @author       Stefan Suchi <suchi@data-quest.de>
* @author       Michael Riehemann <michael.riehemann@uni-oldenburg.de>
* @access       public
* @modulegroup  visual
* @module       header.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// header.php
// head line of Stud.IP
// Copyright (C) 2000 Ralf Stockmann <rstockm@gwdg.de>, Stefan Suchi <suchi@data-quest.de>
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
require_once ('lib/visual.inc.php');

/* --- 
 * Mögliche Datenschutz-/Sichtbarkeitsentscheidung: Beim ersten Login wird ein
 * informierender Text mit Entscheidungsmöglichkeit: "Ich will sichtbar sein" oder
 * "Ich will unsichtbar sein" angezeigt.
 *
 * Bei Nutzung dieser Funktion unbedingt die Texte unter locale/de/LC_HELP/visibility_decision.php bzw.
 * locale/en/LC_HELP/visibility_decision.php an die lokalen Verhältnisse anpassen!
 */
if ($GLOBALS['USER_VISIBILITY_CHECK']) 
{
   require_once('lib/user_visible.inc.php');
   first_decision($GLOBALS['user']->id);
}

if (PageLayout::isHeaderEnabled()) //Einige Seiten benötigen keinen Header, sprich Navigation (Evaluation usw.)
{
    $header_template = $GLOBALS['template_factory']->open('header');
    $header_template->current_page = PageLayout::getTitle();
    $header_template->navigation = PageLayout::getTabNavigation();
    $header_template->link_params = array_fill_keys(array_keys(URLHelper::getLinkParams()), NULL);

    if (is_object($GLOBALS['user']) && $GLOBALS['user']->id != 'nobody') {
        // only mark course if user is logged in and free access enabled
        if (get_config('ENABLE_FREE_ACCESS') &&
            Navigation::hasItem('/course') && Navigation::getItem('/course')->isActive()) {
            // indicate to the template that this course is publicly visible
            // need to handle institutes separately (always visible)
            if ($GLOBALS['SessSemName']['class'] == 'inst') {
                $header_template->public_hint = _('öffentliche Einrichtung');
            } else if ($GLOBALS['SemSecLevelRead'] == 0) {
                $header_template->public_hint = _('öffentliche Veranstaltung');
            }
        }

        if ($GLOBALS['user']->cfg->getValue('ACCESSKEY_ENABLE')){
            $header_template->accesskey_enabled = true;
        }
        // fetch semester for quick search box in the link bar
        $semester_data = SemesterData::GetSemesterArray();
        $default_semester = $_SESSION['_default_sem'] ?
                            SemesterData::GetSemesterIndexById($_SESSION['_default_sem']) :
                            'all';
        $header_template->search_semester_nr = $default_semester;
        $header_template->search_semester_name = $default_semester != 'all' ?
                                                 $semester_data[$default_semester]['name'] :
                                                 _("alle Semester");
    }
}
else
{
    $header_template = $GLOBALS['template_factory']->open('noheader');
}

echo $header_template->render();

if ($GLOBALS['SHOW_TERMS_ON_FIRST_LOGIN'] && $GLOBALS['auth']->is_authenticated() && $GLOBALS['user']->id != 'nobody')
{
    require_once('lib/terms.inc.php');
    check_terms($GLOBALS['user']->id, $GLOBALS['_language_path']);
}

?>
