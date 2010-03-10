<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// kategorien.inc.php
//
// Copyright (C) 2000
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

require_once 'visual.inc.php';

function print_freie($username) {
    
    global $view,$PHP_SELF,$auth;
    $db=new DB_Seminar;
    $cssSw=new cssClassSwitcher;

    $cssSw->switchClass();

    $db->query("SELECT * FROM auth_user_md5 LEFT JOIN kategorien ON(range_id=user_id) WHERE username='$username' AND NOT ISNULL(range_id) ORDER BY priority ");

    echo '<tr><td align="left" valign="top" class="blank"><blockquote><br>'. "\n";
    echo _("Hier können Sie beliebige eigene Kategorien anlegen. Diese Kategorien erscheinen auf Ihrer pers&ouml;nlichen Homepage. Mit den Pfeilsymbolen k&ouml;nnen sie die Reihenfolge, in der die Kategorien angezeigt werden, ver&auml;ndern.");
    echo "<br>\n";
    echo _("Verwenden Sie die Option \"f&uuml;r andere unsichtbar\", um Memos anzulegen, die nur f&uuml;r Sie selbst auf der Homepage sichtbar werden - andere Nutzer k&ouml;nnen diese Daten nicht einsehen.");
    echo "\n<br><br></blockquote></td></tr>\n".'<tr><td class="blank">';
    echo '<form action="'. URLHelper::getLink('?freie=update_freie&username='.$username.'&view='.$view) .'" method="POST" name="edit_freie">';
    echo '<table width="100%" class="blank" border="0" cellpadding="0" cellspacing="0">';
    if (!$db->num_rows())
        echo '<tr><td class="'.$cssSw->getClass().'"><font size="-1"><b><blockquote>' . _("Es existieren zur Zeit keine eigenen Kategorien.") . "</blockquote></b></font></td></tr>\n";
    echo '<tr><td class="'.$cssSw->getClass().'"> <blockquote>' . _("Kategorie") . '&nbsp; <a href="'.URLHelper::getLink('?freie=create_freie&view='.$view.'&username='.$username).'">' . makeButton("neuanlegen") . "</a></blockquote></td></tr>\n";
    $count = 0;
    $hidden_count = 0;
    while ($db->next_record() ){

        IF ((($auth->auth["perm"] == "root") OR ($auth->auth["perm"] == "admin")) AND $db->f("hidden") == '1' AND $username != $auth->auth["uname"]) {
            $hidden_count++;
            }
        ELSE {
            $cssSw->switchClass();
            $id = $db->f("kategorie_id");
            echo '<tr><td class="'.$cssSw->getClass().'">';
            if ($count)
                echo "<br>\n";
            echo '<input type="hidden" name="freie_id[]" value="'.$db->f("kategorie_id")."\">\n";
            echo '<blockquote><input type="text" name="freie_name[]" style="width: 50%" value="' . htmlReady($db->f("name")).'" size="40">';
            echo '&nbsp; &nbsp; &nbsp; <input type=checkbox name="freie_secret['.$count.']" value="1"';
            IF ($db->f("hidden") == '1')
                echo " checked";
            echo ">" . _("f&uuml;r andere unsichtbar") . "&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;";
            if ($count){
                echo "\n".'<a href="'.URLHelper::getLink('?freie=order_freie&direction=up&username='.$username.'&view='.$view.'&cat_id=' . $db->f('kategorie_id'))
                . '"><img src="'. $GLOBALS['ASSETS_URL'] . 'images/move_up.gif" hspace="4" width="13" height="11" border="0" '
                . tooltip(_("Kategorie nach oben verschieben")) .'></a>';
            }
            if (($count+$hidden_count) != ($db->num_rows()-1) ){
                echo "\n".'<a href="'.URLHelper::getLink('?freie=order_freie&direction=down&username='.$username.'&view='.$view.'&cat_id=' . $db->f("kategorie_id"))
                . '"><img src="'. $GLOBALS['ASSETS_URL'] . 'images/move_down.gif" hspace="4" width="13" height="11" border="0" '
                . tooltip(_("Kategorie nach unten verschieben")) .'></a>';
            }
            echo "<br>\n&nbsp;</blockquote></td></tr>\n";
            // Breite für textarea
            $cols = ($auth->auth["jscript"])? ceil($auth->auth["xres"]/13):50;
            echo '<tr><td class="'.$cssSw->getClass(). '"><blockquote><textarea  name="freie_content[]" style="width: 90%" cols="' . $cols . '" rows="7" wrap="virtual">' . htmlReady($db->f('content')) . '</textarea>';
            echo '<br><br><input type="IMAGE" name="update" border="0" align="absmiddle" ' . makeButton("uebernehmen", "src") . ' value="' . _("ver&auml;ndern") . '">';
            echo '&nbsp;<a href="'.URLHelper::getLink('?freie=verify_delete_freie&freie_id='.$id.'&view='.$view.'&username='.$username).'">';
            echo makeButton("loeschen") . "</a><br>\n&nbsp; </blockquote></td></tr>\n";
            $count++;
            }
        }
    if ($hidden_count) {
        echo '<tr><td class="'.$cssSw->getClass().'"><font size="-1"><b><blockquote>';
        if ($hidden_count > 1) {
            printf(_("Es existiereren zus&auml;tzlich %s Kategorien, die Sie nicht einsehen und bearbeiten k&ouml;nnen."), $hidden_count);
        } else {
            print(_("Es existiert zus&auml;tzlich eine Kategorie, die Sie nicht einsehen und bearbeiten k&ouml;nnen."));

        }
        echo '</blockquote></b></font></td></tr>'."\n";
    }
    echo '</td></tr></table></form></td></tr>'."\n";
}

function create_freie() {
    global $username;

    $db=new DB_Seminar;
    $now = time();
    $kategorie_id=md5(uniqid("blablubburegds4"));
    $db->query ("SELECT user_id FROM auth_user_md5 WHERE username = '$username'");
    $db->next_record();
    $user_id = $db->f("user_id");
    $db->query("UPDATE kategorien SET priority=priority+1 WHERE range_id='$user_id'");
    $db->query("INSERT INTO kategorien (kategorie_id,name, content, mkdate, chdate, range_id,priority) VALUES ('$kategorie_id','" . _("neue Kategorie") . "','" . _("Inhalt der Kategorie") . "','$now','$now','$user_id',0)");
    if ($db->affected_rows() == 0) {
        parse_msg ("info§" . _("Anlegen fehlgeschlagen"));
        die;
    }
}

function delete_freie($kategorie_id) {
    
    global $username;

    $db=new DB_Seminar;

    $db->query("DELETE FROM kategorien WHERE kategorie_id='$kategorie_id'");
    if ($db->affected_rows() == 1) {
        parse_msg ("msg§" . _("Kategorie gel&ouml;scht!"));
    }
}

function verify_delete_freie($kategorie_id) {
    
    global $username;
   

    $db=new DB_Seminar;

    $db->query ("SELECT * FROM kategorien LEFT JOIN auth_user_md5 ON(range_id=user_id) WHERE username = '$username' and kategorie_id='$kategorie_id'");
    if (!$db->next_record()) { //hier wollte jemand schummeln
        parse_msg ("info§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion."));
        die;
    } else {
        $db->query("SELECT name FROM kategorien WHERE kategorie_id='$kategorie_id'"); 
        while($db->next_record()) { 
            $name = $db->f("name"); 
        }
        $msg = _('Möchten Sie wirklich die Kategorie **'.$name.'** löschen?');
        echo createQuestion($msg,array('freie' => 'delete_freie', "freie_id" => $kategorie_id,'username'=> $username, 'view' => 'Sonstiges'),array('username'=> $username, 'view' => 'Sonstiges'));
    }
    
    
}

function update_freie() {
    global $freie_id,$freie_name,$freie_content,$freie_secret;
    $db = new DB_Seminar;
    $max = sizeof($freie_id);
    FOR ($i=0; $i < $max; $i++) {
        $now = time();
        $name = $freie_name[$i];
        if ($name === '') {
            parse_msg ('error§' . _("Kategorien ohne Namen k&ouml;nnen nicht gespeichert werden!"));
            continue;
        }
        $content = $freie_content[$i];
        $secret = $freie_secret[$i];
        if ($content === '' && !$secret) {
            $secret = 1;
            parse_msg ('info§' . _("Kategorie ohne Inhalt wurde versteckt!"));
        }
        $id = $freie_id[$i];
        $db->query("UPDATE kategorien SET name='$name', content='$content', hidden='$secret', chdate='$now' WHERE kategorie_id='$id'");
    }
    parse_msg ("msg§" . _("Kategorien ge&auml;ndert!"));
}

function order_freie($cat_id,$direction,$username){
    $items_to_order = array();
    $user_id = get_userid($username);
    $db = new DB_Seminar("SELECT kategorie_id FROM kategorien WHERE range_id='$user_id' ORDER BY priority");
    while($db->next_record()) {
        $items_to_order[] = $db->f("kategorie_id");
    }
    for ($i = 0; $i < count($items_to_order); ++$i) {
        if ($cat_id == $items_to_order[$i])
            break;
    }
    if ($direction == "up" && isset($items_to_order[$i-1])) {
        $items_to_order[$i] = $items_to_order[$i-1];
        $items_to_order[$i-1] = $cat_id;
    } elseif (isset($items_to_order[$i+1])) {
        $items_to_order[$i] = $items_to_order[$i+1];
        $items_to_order[$i+1] = $cat_id;
    }
    for ($i = 0; $i < count($items_to_order); ++$i) {
        $db->query("UPDATE kategorien SET priority=$i WHERE kategorie_id='$items_to_order[$i]'");
    }
    parse_msg("msg§" . _("Kategorien wurden neu geordnet"));
}

?>
