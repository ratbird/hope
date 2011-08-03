<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
// admin_vote.php
//
// Show the admin pages
//
// +--------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +--------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +--------------------------------------------------------------------------+


/**
 * admin_vote.php
 *
 *
 * @author  Michael Cohrs <michael@cohrs.de>
 * @version 10. Juni 2003
 * @access  public
 * @package vote
 */

require '../lib/bootstrap.php';

ob_start(); // start output buffering

page_open (array ("sess" => "Seminar_Session", "auth" => "Seminar_Auth",
          "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check ("autor");

if (Request::get('admin_inst_id')) {
    $showrangeID = Request::get('admin_inst_id');
    $view = 'vote_inst';
}

require_once 'lib/functions.php';
include_once 'lib/seminar_open.php';

PageLayout::setHelpKeyword("Basis.Votings");
PageLayout::setTitle(_("Verwaltung von Umfragen und Tests"));

require_once 'lib/admin_search.inc.php';

if ($list || $view && !(isDeputyEditAboutActivated() && isDeputy($auth->auth["uid"], get_userid(Request::get('cid')), true))) {
    if ($perm->have_perm('admin')) {
        if ($links_admin_data['topkat'] == 'sem') {
            Navigation::activateItem('/admin/course/vote');
        } else {
            Navigation::activateItem('/admin/institute/vote');
        }
    } else {
        Navigation::activateItem('/course/admin/vote');
    }
} else {
    Navigation::activateItem('/tools/vote');
}

include_once('lib/include/html_head.inc.php');
include_once('lib/include/header.php');

if ($list || $view) {
    include 'lib/include/admin_search_form.inc.php';
}

if ($page == "edit")
    include 'lib/vote/vote_edit.inc.php';
else
    include 'lib/vote/vote_overview.inc.php';

include 'lib/include/html_end.inc.php';
page_close ();
?>
