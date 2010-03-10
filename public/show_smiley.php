<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// show_smiley.php
//
// Copyright (c) 2002 André Noack <andre.noack@gmx.net>
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
page_open(array('sess' => 'Seminar_Session', 'auth' => 'Seminar_Default_Auth', 'perm' => 'Seminar_Perm', 'user' => 'Seminar_User'));

include_once('lib/seminar_open.php');
require_once('config.inc.php');
require_once('lib/classes/smiley.class.php');

function my_comp($a, $b){
    return strcasecmp($a[1], $b[1]);
}

$sm = new smiley(false);

if ($sm->error) { // old code is used

    $path = realpath($GLOBALS['DYNAMIC_CONTENT_PATH'] . '/smile');
    $folder=dir($path);
    $SMILE_SHORT_R=array_flip($SMILE_SHORT);
    $i_smile = array();
    while ($entry=$folder->read()){
        $dot = strrpos($entry,".");
        $l = strlen($entry) - $dot;
        $name = substr($entry,0,$dot);
        $ext = strtolower(substr($entry,$dot+1,$l));
        if ($dot AND !is_dir($path."/".$entry) AND $ext=="gif"){
            $i_smile[] = array($entry,$name);
        }
    }
    $folder->close();
    usort($i_smile, "my_comp");
    ?>
    <html>
    <head>
    <title><?=_("Alle Smilies")?> (<?=count($i_smile)?>)</title>
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>stylesheets/style.css" type="text/css">
    </head>
    <body>
    <div align="center"><b><?=_("Aktuelle Smiley Anzahl: ") . count($i_smile)?></b></div>
    <table align="center"><tr><td valign="top" align="center"><table><tr>
    <?
    $table_head = '<th>' . _("Bild") . '</th><th>' . _("Schreibweise") . '</th><th>' . _("Kürzel") . '</th></tr>';
    echo $table_head;
    ob_start();
    $tabspalten = 1;
    for($i=0;$i < count($i_smile);++$i){
            echo "\n".'<tr><td class="blank" align="center"><img src="'.$GLOBALS['DYNAMIC_CONTENT_URL'].'/smile/'.$i_smile[$i][0].'"></td>';
            echo "\n".'<td class="blank" align="center">:'.$i_smile[$i][1].':</td>';
            echo "\n".'<td class="blank" align="center">';
            echo ($SMILE_SHORT_R[$i_smile[$i][1]])?  $SMILE_SHORT_R[$i_smile[$i][1]] : '&nbsp';
            echo "</td>\n</tr>";
            $max = ceil(count($i_smile)/3)+1;
            if (!(($i+1) % $max )) {
                ?>
                </table></td><td valign="top">
                <table align="center"><tr>
                <?
                $tabspalten++;
                echo $table_head;
                ob_end_flush();
                ob_start();
            }
    }
    echo '</table></td></tr></table>', "\n";

} else { // new class is used
    $info = $sm->get_info();
    echo '<html><head><title>',_("Smiley-&Uuml;bersicht"),' (',$info['count_all'],')</title>', "\n";
    echo '<link rel="stylesheet" href="'.$GLOBALS['ASSETS_URL'].'stylesheets/style.css" type="text/css">', "\n";
    echo '</head>', "\n";
    echo '<body>', "\n";
    $cmd = (isset($_REQUEST['cmd']))? $_REQUEST['cmd']:'';

    switch ($cmd) {
        case 'delfav':
            $sm->del_favorite(); break;
        case 'addfav':
            $sm->add_favorite(); break;
        default:
        ;
    }

    $sm->show_favorite();

    echo '<table width="100%" class="blank" border="0" cellpadding="0" cellspacing="0" >', "\n";
    echo '<tr><td class="topic"><b>&nbsp;' . _("Smiley-&Uuml;bersicht") . '</b></td></tr>', "\n";
    echo '<tr><td class="blank" valign="top" align="center">';



    $txt = sprintf(_("%s Smileys vorhanden - Auswahl:"),$info['count_all']);
    $sm->user_menue($txt);
    echo '</td></tr><tr><td class="blank" valign="top" align="center">', "\n";
    $sm->user_smiley_list();
    echo '<br>&nbsp;</td></tr></table>', "\n";


}
include ('lib/include/html_end.inc.php');
?>
