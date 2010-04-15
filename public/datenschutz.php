<?php
# Lifter007: TODO

/**
 * datenschutz.php
 *
 * privacy guidelines for Stud.IP
 *
 * PHP version 5
 *
 * @author      Elmar Ludwig
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright   2009 Stud.IP
 * @license     http://www.gnu.org/licenses/gpl.html GPL Licence 3
 * @package     studip_core
 * @access      public
 */


require '../lib/bootstrap.php';

page_open(array(
    'sess' => 'Seminar_Session',
    'auth' => 'Seminar_Default_Auth',
    'perm' => 'Seminar_Perm',
    'user' => 'Seminar_User'
));

// set up user session
include 'lib/seminar_open.php';

$CURRENT_PAGE = _('Erläuterungen zum Datenschutz');

$template = $template_factory->open('privacy');
$template->set_layout('layouts/base_without_infobox');

echo $template->render();
?>
