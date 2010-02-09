<?php
# Lifter002: TEST
# Lifter007: TODO
# Lifter003: TODO

/**
 * datenschutz.php
 *
 * privacy guidelines for Stud.IP
 *
 * PHP version 5
 *
 * @author 		Elmar Ludwig
 * @author  	Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright 	2009 Stud.IP
 * @license 	http://www.gnu.org/licenses/gpl.html GPL Licence 3
 * @package 	studip_core
 * @access 		public
 */

page_open(array(
	'sess' => 'Seminar_Session',
	'auth' => 'Seminar_Default_Auth',
	'perm' => 'Seminar_Perm',
	'user' => 'Seminar_User'
));

$_language_path = init_i18n($_language);

$CURRENT_PAGE = _('Erläuterungen zum Datenschutz');

$template = $template_factory->open('privacy');
$template->set_layout('layouts/base_without_infobox');

echo $template->render();
?>
