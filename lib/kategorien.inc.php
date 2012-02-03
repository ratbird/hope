<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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

use Studip\Button, Studip\LinkButton;

require_once 'visual.inc.php';
require_once 'user_visible.inc.php';

function print_freie($username) {

    global $view,$PHP_SELF,$auth, $user;
    $db=new DB_Seminar;
    SkipLinks::addIndex(_("Eigene Kategorien bearbeiten"), 'edit_categories');
    $cssSw=new cssClassSwitcher;

    $cssSw->switchClass();

    $db->query("SELECT * FROM auth_user_md5 LEFT JOIN kategorien ON(range_id=user_id) WHERE username='$username' AND NOT ISNULL(range_id) ORDER BY priority ");

    echo '<tr><td align="left" valign="top" class="blank" id="edit_categories"><p class="info"><br>'. "\n";
    echo _("Hier können Sie beliebige eigene Kategorien anlegen. Diese Kategorien erscheinen je nach eingestellter Sichtbarkeit auf Ihrer Profilseite. Mit den Pfeilsymbolen k&ouml;nnen Sie die Reihenfolge, in der die Kategorien angezeigt werden, ver&auml;ndern.");
    echo "<br>\n";
    echo sprintf(_("Für wen Ihre angelegten Kategorien genau sichtbar sein sollen, können Sie in Ihren %sPrivatsphäre-Einstellungen%s festlegen."), '<a href="'.URLHelper::getUrl('edit_about.php', array('view'=>'privacy')).'">', '</a>');
    echo "\n<br><br></p></td></tr>\n".'<tr><td class="blank">';
    echo '<form action="'. URLHelper::getLink('?freie=update_freie&username='.$username.'&view='.$view) .'" method="POST" name="edit_freie">';
    echo CSRFProtection::tokenTag();
    echo '<table width="100%" class="blank" border="0" cellpadding="0" cellspacing="0">';
    if (!$db->num_rows())
        echo '<tr><td class="'.$cssSw->getClass().'"><font size="-1"><b><p class="info">' . _("Es existieren zur Zeit keine eigenen Kategorien.") . "</p></b></font></td></tr>\n";
    echo '<tr><td class="'.$cssSw->getClass().'"> <p class="info">' . _("Kategorie") . '&nbsp;' . 
				LinkButton::create(_('Neuanlegen'), URLHelper::getURL('', array('freie' => 'create_freie', 'view' => $view, 'username' => $username)), 
				array('title' => _('Kategorie anlegen'))) . "</p></td></tr>\n";
    $count = 0;
    $hidden_count = 0;
    while ($db->next_record() ){
          $visibility = get_homepage_element_visibility($user->id, 'kat_'.$db->f('kategorie_id'));
        IF ((($auth->auth["perm"] == "root") OR ($auth->auth["perm"] == "admin")) AND $visibility == VISIBILITY_ME AND $username != $auth->auth["uname"]) {
            $hidden_count++;
            }
        ELSE {
            $cssSw->switchClass();
            $id = $db->f("kategorie_id");
            echo '<tr><td class="'.$cssSw->getClass().'">';
            if ($count) {
                echo "<br>\n";
            }
            echo '<input type="hidden" name="freie_id[]" value="'.$db->f("kategorie_id")."\">\n";
            echo '<p class="info"><input type="text" aria-label="' . _("Name der Kategorie") . '" name="freie_name[]" style="width: 50%" value="' . htmlReady($db->f("name")).'" size="40">';
            switch ($visibility) {
                case VISIBILITY_ME:
                    $vis_text = _("sichtbar für mich selbst");
                    break;
                case VISIBILITY_BUDDIES:
                    $vis_text = _("sichtbar für meine Buddies");
                    break;
                case VISIBILITY_DOMAIN:
                    $vis_text = _("sichtbar für meine Nutzerdomäne");
                    break;
                case VISIBILITY_STUDIP:
                    $vis_text = _("sichtbar für alle Stud.IP-Nutzer");
                    break;
                case VISIBILITY_EXTERN:
                    $vis_text = _("sichtbar auf externen Seiten");
                    break;
            }
            echo "&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;".$vis_text."&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;";
            if ($count){
                echo "\n".'<a href="'.URLHelper::getLink('?freie=order_freie&direction=up&username='.$username.'&view='.$view.'&cat_id=' . $db->f('kategorie_id'))
                . '">' . Assets::img('icons/16/yellow/arr_2up.png', array('class' => 'text-top', 'title' =>_('Kategorie nach oben verschieben')))
                . '</a>';
            }
            if (($count+$hidden_count) != ($db->num_rows()-1) ){
                echo "\n".'<a href="'.URLHelper::getLink('?freie=order_freie&direction=down&username='.$username.'&view='.$view.'&cat_id=' . $db->f("kategorie_id"))
                . '">' . Assets::img('icons/16/yellow/arr_2down.png', array('class' => 'text-top', 'title' =>_('Kategorie nach unten verschieben')))
                . '</a>';
            }
            echo "<br>\n&nbsp;</p></td></tr>\n";
            // Breite für textarea
            $cols = ($auth->auth["jscript"])? ceil($auth->auth["xres"]/13):50;
            echo '<tr><td class="'.$cssSw->getClass(). '"><p class="info">';
            echo '<textarea aria-label="' . _("Inhalt der Kategorie:") . '" name="freie_content[]" style="width: 90%" cols="' . $cols . '" rows="7" wrap="virtual">' . htmlReady($db->f('content')) . '</textarea>';
            echo '<br><br>' . Button::create(_('Übernehmen'));
            echo LinkButton::create(_('Löschen'), URLHelper::getURL('', array('freie' => 'verify_delete_freie', 'freie_id' => $id, 'view' => $view, 'username' => $username))) ;

            // show help links
            echo '<a style="margin-left: 15px" href="'. URLHelper::getLink('dispatch.php/smileys') .'" target="_blank">'. _("Smileys") .'</a>', "\n";
            echo '<a style="margin-left: 10px" href="'. format_help_url("Basis.VerschiedenesFormat") .'" target="_blank">'. _("Formatierungshilfen") .'</a>', "\n";

            echo '<br>', "\n", '&nbsp; </p></td></tr>', "\n";
            $count++;
            }
        }
    if ($hidden_count) {
        echo '<tr><td class="'.$cssSw->getClass().'"><font size="-1"><b><p class="info">';
        if ($hidden_count > 1) {
            printf(_("Es existiereren zus&auml;tzlich %s Kategorien, die Sie nicht einsehen und bearbeiten k&ouml;nnen."), $hidden_count);
        } else {
            print(_("Es existiert zus&auml;tzlich eine Kategorie, die Sie nicht einsehen und bearbeiten k&ouml;nnen."));

        }
        echo '</p></b></font></td></tr>'."\n";
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
    global $user, $freie_id,$freie_name,$freie_content;
    $db = new DB_Seminar;
    $max = sizeof($freie_id);
    for ($i=0; $i < $max; $i++) {
        $now = time();
        $name = $freie_name[$i];
        if ($name === '') {
            parse_msg ('error§' . _("Kategorien ohne Namen k&ouml;nnen nicht gespeichert werden!"));
            continue;
        }
        $content = $freie_content[$i];
        $id = $freie_id[$i];
        $db->query("UPDATE kategorien SET name='$name', content='$content', chdate='$now' WHERE kategorie_id='$id'");
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
