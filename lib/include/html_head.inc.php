<?php
# Lifter002: TODO
# Lifter005: TODO - studipim
# Lifter007: TODO
# Lifter003: TODO
/**
* html_head.inc.php
*
* output of html-head for all Stud.IP pages<br>
* parameter <b>$_include_stylesheet</b>
* <ul><li>if not set, use default stylesheet</li>
* <li>if empty, use no stylesheet</li>
* <li>else use set stylesheet</li></ul><br>
* parameter <b>$_html_head_title</b><br>
* <ul><li>if not set use default</li>
* <li> if set use as title </li></ul>
*
* @author       Stefan Suchi <suchi@data-quest.de>
* @access       public
* @package      studip_core
* @modulegroup  library
* @module       html_head.inc.php
*/

# necessary if you want to include html_head.inc.php in function/method scope
global  $AUTH_LIFETIME, $FAVICON, $HTML_HEAD_TITLE, $CURRENT_PAGE;

global  $auth, $user;

global  $_html_head_title,
        $_include_additional_header,
        $_include_extra_stylesheet,
        $_include_stylesheet,
        $my_messaging_settings,
        $seminar_open_redirected;


// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// html_head.inc.php
// Copyright (c) 2002 Stefan Suchi <suchi@data-quest.de>
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
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=WINDOWS-1252">
        <? if (basename($_SERVER['SCRIPT_NAME']) !== 'logout.php' && $AUTH_LIFETIME > 0 && $auth->auth["uid"]!="" && $auth->auth["uid"] != "nobody" && $auth->auth["uid"] != "form") : ?>
            <meta http-equiv="REFRESH" CONTENT="<?= $AUTH_LIFETIME * 60 ?>; URL=<?= $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] ?>logout.php">
        <? endif ?>

<?

if (isset($FAVICON))
        echo "\t\t".'<link rel="SHORTCUT ICON" href="'. $FAVICON.'">'."\n";

if (isset($_html_head_title))
    $title = $_html_head_title;
else if (isset($CURRENT_PAGE))
    $title = $HTML_HEAD_TITLE.' - '.$CURRENT_PAGE;
else
    $title = $HTML_HEAD_TITLE;
echo "\t\t".'<title>'.$title.'</title>'."\n";

if (!isset($_include_stylesheet))  // if not set, use default stylesheet
    $_include_stylesheet = 'style.css';

if ($_include_stylesheet != '')  // if empty, use no stylesheet
    echo "\t\t".'<link rel="stylesheet" href="'.$GLOBALS['ASSETS_URL'].'stylesheets/'.$_include_stylesheet.'" type="text/css">'."\n";

if (isset ($_include_extra_stylesheet))
    echo "\t\t".'<link rel="stylesheet" href="'.$GLOBALS['ASSETS_URL'].'stylesheets/'.$_include_extra_stylesheet.'" type="text/css">'."\n";
echo "\t\t".'<link rel="stylesheet" href="'.$GLOBALS['ASSETS_URL'].'stylesheets/header.css" type="text/css">'."\n";
echo "\t\t".'<link rel="stylesheet" href="'.$GLOBALS['ASSETS_URL'].'stylesheets/jquery-ui.1.8.css" type="text/css">'."\n";

unset ($_include_extra_stylesheet);
unset ($_include_stylesheet);
unset ($_html_head_title);

//start messenger, if set
if ($my_messaging_settings['start_messenger_at_startup'] && $auth->auth['jscript'] && !$_SESSION['messenger_started'] && !$seminar_open_redirected) {

    ?>
    <script language="Javascript">
        {fenster=window.open("studipim.php","im_<?=$user->id?>","scrollbars=yes,width=400,height=300","resizable=no");}
    </script>
    <?
    $_SESSION['messenger_started'] = TRUE;
}
?>
    <?= Assets::script('jquery-1.4.2.min.js', 'jquery-ui-1.8.custom.min.js',
                       'jquery.metadata.js', 'application') ?>
    <script type="text/javascript" language="javascript">
    // <![CDATA[
        STUDIP.ABSOLUTE_URI_STUDIP = "<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>";
        STUDIP.ASSETS_URL = "<?= $GLOBALS['ASSETS_URL'] ?>";
    // ]]>
    </script>
    <? if (isset ($_include_additional_header)) : ?>
        <?= $_include_additional_header ?>
    <? endif ?>
    <? unset($_include_additional_header) ?>
    </head>
    <body<?= (isset($GLOBALS['body_id']) ? ' id="'.htmlReady($GLOBALS['body_id']).'"' : '') .
             (isset($GLOBALS['body_class']) ? ' class="'.htmlReady($GLOBALS['body_class']).'"' : '' ) ?>>
      <?= isset($GLOBALS['_include_additional_html']) ? $GLOBALS['_include_additional_html'] : '' ?>
      <div id="overdiv_container"></div>
      <div id="ajax_notification" style="display: none;"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/ajax_indicator.gif" alt="AJAX indicator" align="middle">&nbsp;Working...</div>
