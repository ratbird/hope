<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* lit_import.inc.php
*
* Routinen zum Importieren von XML-Daten aus EndNote
*
*
* @author               Jan Kulmann <jankul@tzi.de>
*/

// +---------------------------------------------------------------------------+
// This file is NOT part of Stud.IP
// admin_foto_contest.php
// Copyright (C) 2005 Jan Kulmann <jankul@tzi.de>
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

require_once ("lib/classes/lit_import_plugins/StudipLitImportPluginAbstract.class.php");

function do_lit_import() {
    global $_msg, $cmd, $xmlfile, $xmlfile_size, $xmlfile_name, $username, $_range_id,$plugin_name;
    $cmd = Request::option('cmd');
    $xmlfile = $_FILES['xmlfile']['name'];
    $plugin_name = Request::quoted('plugin_name');
    if ($cmd) {
        if ($cmd=="import_lit_list" && $xmlfile) {
            StudipLitImportPluginAbstract::use_lit_import_plugins($xmlfile, $xmlfile_size, $xmlfile_name, $plugin_name, $_range_id);
            //header("Location: $PHP_SELF?_range_id=$_range_id&username=$username&_msg=".urlencode($_msg));
            //wozu dieses???
        }
    }
}

function print_lit_import_dlg() {
    global $username, $_range_id, $plugin_name, $LIT_IMPORT_PLUGINS;
    $plugin_name = Request::quoted('plugin_name');
    if (!$plugin_name) $plugin_name = "EndNote";

    $plugin = array();

    if ($plugin_name)
        foreach ($LIT_IMPORT_PLUGINS as $p) {
            if ($p["name"] == $plugin_name) {
                $plugin = $p;
                break;
            }
        }

    $template = $GLOBALS['template_factory']->open('literatur/import_lit_list');

    $template->set_attribute('plugin_name', $plugin_name);
    $template->set_attribute('plugin', $plugin);
    $template->set_attribute('_range_id', $_range_id);
    $template->set_attribute('username', Request::quoted('username'));

    echo $template->render();

}
