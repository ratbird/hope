<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
reiter.php - 0.8.20020327
Klasse zum Erstellen des Reitersystems
Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

require_once ('lib/visual.inc.php');

class reiter {

    /**
     * Activates that element of the structure that corresponds to the given view
     * argument.
     *
     * @access private
     *
     * @param  array      the link structure from lib/include/links_*.inc.php
     * @param  string     the key of the link to activate
     *
     * @return void
     */
    function activateStructure(&$structure, $view, $activeBottomkat) {

        # view is empty, use the first item
        if (!$view) {
            reset($structure);
            $view = key($structure);
        }

        $structure[$view]["active"] = TRUE;

        # activate it's topKat
        if ($structure[$view]["topKat"]) {
            $structure[$structure[$view]["topKat"]]["active"] = TRUE;
        }

        # or the topKat itself
        else if ($activeBottomkat) {
            foreach ($structure as $key => $value) {
                if ($structure[$key]["topKat"] == $view) {
                    $structure[$key]["active"] = TRUE;
                    break;
                }
            }
        }
    }


    /**
     * Outputs the tabs.
     *
     * @param  array      an associative array describing the tabs' structure
     * @param  string     the key of the single tab to activate
     *
     * @return void
     */
    function create($structure, $view) {

        $activeBottomkat = true;

        if (preg_match('/^\((.*)\)$/', $view, $matches)) {
            $activeBottomkat = false;
            $view = $matches[1];
        }

        $this->activateStructure($structure, $view, $activeBottomkat);

        Navigation::addItem('/reiter', new Navigation(''));

        foreach ($structure as $key => $item) {
            $navigation = new Navigation($item['name'],
                html_entity_decode($item['link']));

            if ($item['disabled']) {
                $navigation->setEnabled(false);
            } else if ($item['active']) {
                $navigation->setActive(true);
            }

            if ($item['topKat'] && isset($structure[$item['topKat']])) {
                $path = '/reiter/' . $item['topKat'] . '/' . $key;
                Navigation::addItem($path, $navigation);
            } else if ($item['topKat'] == '') {
                $path = '/reiter/' . $key;
                Navigation::addItem($path, $navigation);
            }
        }

        $navigation = Navigation::getItem('/')->activeSubNavigation();

        if (isset($navigation)) {
            echo $GLOBALS['template_factory']->render('tabs', compact('navigation'));
        }
    }
}
