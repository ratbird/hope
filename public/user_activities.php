<?php
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
/*
user_activities.php
Copyright (C) 2006 André Noack <noack@data-quest.de>
Suchi & Berg GmbH <info@data-quest.de>
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA   02111-1307, USA.
*/

use Studip\Button, Studip\LinkButton;


require '../lib/bootstrap.php';

require_once('lib/functions.php');
require_once('lib/msg.inc.php');
require_once('lib/datei.inc.php');

function show_posts_guestbook($user_id,$range_id)
{
    global $PHP_SELF;
    $query = "SELECT user_id, mkdate, content, post_id
              FROM guestbook
              WHERE user_id = ? AND range_id = ?
              ORDER BY mkdate DESC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id, $range_id));
    
    $output = "<table class=\"blank\" width=\"98%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">";
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $output .= "<tr><td class=\"steel2\"><b><font size=\"-1\"><a href=\"$PHP_SELF?username=".get_username($row['user_id'])."\">";
        $output .= sprintf(_("%s hat am %s geschrieben:"), htmlReady(get_fullname($row['user_id']))."</a>", date("d.m.Y - H:i", $row['mkdate']));
        $output .= "</font></b></td></tr>"
        . "<tr><td class=\"steelgraulight\"><font size=\"-1\">".formatready($row['content'])."</font><p align=\"right\">";
        $output .= LinkButton::create(_("Löschen"), "?deletepost=".$row['post_id']."&ticket=".get_ticket());
        $output .= "</p></td></tr>"
        . "<tr><td class=\"steel1\">&nbsp;</td></tr>";
    }
    $output .= "</table>";
    return $output;
}


function show_documents($documents, $open = null)
{
    $pic_path = $GLOBALS['ASSETS_URL'] . 'images';
    if (is_array($documents)){
        if (!is_null($open) && !is_array($open)) {
            $open = null;
        }
        if (is_array($open)) {
            reset($open);
            $ank = key($open);
        }

        $query = "SELECT {$GLOBALS['_fullname_sql']['full']} AS fullname, username, user_id,
                         dokument_id, filename, filesize, downloads, protected, url, description,
                         IF(IFNULL(name, '') = '', filename, name) AS t_name,
                         a.chdate, a.mkdate
                  FROM dokumente AS a
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE dokument_id IN (?)
                  ORDER BY a.chdate DESC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($documents));

        ob_start();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $type = ($row['url'] != '') ? 6 : 0;
            //Icon auswaehlen
            $icon = '<a href="' . GetDownloadLink($row['dokument_id'], $row['filename'], $type) . '">'
                                . GetFileIcon(getFileExtension($row['filename']), true) . '</a>';
            //Workaround for older data from previous versions (chdate is 0)
            $chdate = $row['chdate'] ?: $row['mkdate'];
            $box = '';
            $is_open = (is_null($open) || $open[$row['dokument_id']]) ? 'open' : 'close';
            $tmp_titel=htmlReady(mila($row["t_name"]));
            if ($is_open == 'open') $link = $GLOBALS['PHP_SELF']."?close=".$row['dokument_id']."#dok_anker";
            else $link = $GLOBALS['PHP_SELF']."?open=".$row['dokument_id']."#dok_anker";
            if ($link) $tmp_titel = "<a " . ($ank==$row['dokument_id'] ? "name=\"dok_anker\"" : "")." href=\"$link\" class=\"tree\" >$tmp_titel</a>";
            if (($row['filesize'] /1024 / 1024) >= 1) $titel= $tmp_titel."&nbsp;&nbsp;(".round ($row['filesize'] / 1024 / 1024)." MB";
            else $titel= $tmp_titel."&nbsp;&nbsp;(".round ($row['filesize'] / 1024)." kB";
            //add number of downloads
            $titel .= " / ".(($row['downloads'] == 1) ? $row['downloads']." "._("Download") : $row['downloads']." "._("Downloads")).")";
            //$box = sprintf ("<input type=\"CHECKBOX\" %s name=\"download_ids[]\" value=\"%s\">",($check_all) ? "checked" : "" , $row['dokument_id']);
            //Zusatzangaben erstellen
            $zusatz="<a href=\"about.php?username=".$row['username']."\"><font color=\"#333399\">".htmlReady($row['fullname'])."</font></a>&nbsp;".date("d.m.Y - H:i", $chdate);
            if ($row['protected'] == 1) $zusatz .= Assets::img('icons/16/grey/info-circle.png', tooltip(_("Diese Datei ist urheberrechtlich geschützt!")));
            if ($row['url'] != '') $zusatz .= Assets::img('icons/16/blue/link-extern.png', array('class' => 'text-top', 'title' =>_('Diese Datei wird von einem externen Server geladen!')));
            $zusatz .= $box;
            echo "\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
            printhead (0, 0, $link, $is_open, false , $icon, $titel, $zusatz, $chdate);
            echo "\n</tr></table>";
            if ($is_open == 'open'){
                $content='';
                if ($row['description']) $content= htmlReady($row['description'], TRUE, TRUE);
                else $content= _("Keine Beschreibung vorhanden");
                $content.=  "<br><br>" . sprintf(_("<b>Dateigr&ouml;&szlig;e:</b> %s kB"), round ($row['filesize'] / 1024));
                $content.=  "&nbsp; " . sprintf(_("<b>Dateiname:</b> %s "), $row['filename']);
                $content.= "\n";
                //Editbereich ertstellen

                $edit = LinkButton::create(_("Herunterladen"), GetDownloadLink($row['dokument_id'], $row['filename'], $type, 'force'));

                $fext = getFileExtension(strtolower($row['filename']));
                if (($type != 6) && ($fext != 'zip') && ($fext != 'tgz') && ($fext != 'gz') && ($fext != 'bz2')) {
                    $edit .= LinkButton::create(_("Als ZIP herunterladen"), GetDownloadLink($row['dokument_id'], $row['filename'], $type, 'zip'));
                }
                if ($row['protected']) {
                    $content = "<br>" . MessageBox::info(_("Diese Datei ist urheberrechtlich geschützt."), array(_("Sie darf nur im Rahmen dieser Veranstaltung verwendet werden, jede weitere Verbreitung ist strafbar!")));
                }
                echo "\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
                printcontent ("100%",false, $content, $edit);
                echo "\n</tr></table>";
            }
        }

        return ob_get_clean();
    }
}

function get_user_documents($user_id, $seminar_id = null)
{
    $query = "SELECT dokument_id FROM dokumente WHERE user_id = ?";
    $parameters = array($user_id);

    if ($seminar_id !== null) {
        $query .= " AND seminar_id = ?";
        $parameters[] = $seminar_id;
    }

    $statement = DBManager::get()->prepare($query);
    $statement->execute($parameters);
    return $statement->fetchAll(PDO::FETCH_COLUMN);
}

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("root");
if (!$sess->is_registered('_user_activities')){
    $sess->register('_user_activities');
    $_user_activities['open'] = array();
    $_user_activities['details'] = 'files';
}

$queries = array();
$msg = array();

if ($_REQUEST['username']){
    $_user_activities['username'] = $_REQUEST['username'];
    $_user_activities['open'] = array();
    $_user_activities['details'] = 'files';
}
if ($_REQUEST['details']) $_user_activities['details'] = $_REQUEST['details'];
if ($_REQUEST['open']) $_user_activities['open'][$_REQUEST['open']] = time();
if ($_REQUEST['close']) unset($_user_activities['open'][$_REQUEST['close']]);
$user_id = get_userid($_user_activities['username']);
arsort($_user_activities['open'], SORT_NUMERIC);
if ($_REQUEST['download_as_zip']) {
    $download_ids = $_REQUEST['download_as_zip'] == 'all' ? get_user_documents($user_id) : get_user_documents($user_id, $_REQUEST['download_as_zip']);
    if (is_array($download_ids) && count($download_ids)) {
        $zip_file_id = createSelectedZip($download_ids, false);
        $zip_name = prepareFilename($_user_activities['username'] . '-' . _("Dokumente") . '.zip');
        header('Location: ' . getDownloadLink( $zip_file_id, $zip_name, 4));
        page_close();
        die;
    }
}
if ($_REQUEST['deletepost'] && check_ticket($_REQUEST['ticket'])){
    $post_id = Request::option('deletepost');

    $query = "DELETE FROM guestbook WHERE post_id = ? LIMIT 1";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($post_id));
    if ($statement->rowCount()){
        $msg[] = array('msg', _("Ein Gästebucheintrag wurde gelöscht."));
    }
}

reset($_user_activities['open']);
$ank = key($_user_activities['open']);

$queries[] = array(
    'desc'    => _("Eingetragen in Veranstaltungen (dozent / tutor / autor / user)"),
    'query'   => "SELECT CONCAT_WS(' / ', SUM(status = 'dozent'), SUM(status = 'tutor'),
                                          SUM(status = 'autor'), SUM(status = 'user'))
                  FROM seminar_user
                  WHERE user_id = ?
                  GROUP BY user_id",
    'details' => "details=seminar",
);
$queries[] = array(
    'desc'    => _("Eingetragen in geschlossenen Veranstaltungen (dozent / tutor / autor / user)"),
    'query'   => "SELECT CONCAT_WS(' / ', SUM(su.status = 'dozent'), SUM(su.status = 'tutor'),
                                          SUM(su.status = 'autor'), SUM(su.status = 'user'))
                  FROM seminar_user AS su
                  INNER JOIN seminare USING (Seminar_id)
                  WHERE user_id = ? AND (Schreibzugriff > 2 OR Lesezugriff > 2)
                  GROUP BY user_id",
    'details' => "details=seminar_closed",
);
$queries[] = array(
    'desc'    => _("Eingetragen in Wartelisten (chronologisch / los / vorläufig akzeptiert)"),
    'query'   => "SELECT CONCAT_WS(' / ', SUM(status = 'awaiting'), SUM(status = 'claiming'), SUM(status = 'accepted'))
                  FROM admission_seminar_user
                  WHERE user_id = ?
                  GROUP BY user_id",
    'details' => "details=seminar_wait",
);
$queries[] = array(
    'desc'    => _("Eingetragen in Einrichtungen (admin / dozent / tutor / autor)"),
    'query'   => "SELECT CONCAT_WS(' / ', SUM(inst_perms = 'admin'), SUM(inst_perms = 'dozent'),
                                          SUM(inst_perms = 'tutor'), SUM(inst_perms = 'autor'))
                  FROM user_inst
                  WHERE user_id = ?
                  GROUP BY user_id",
);
$queries[] = array(
    'desc'    => _("Anzahl der Gästebucheinträge"),
    'query'   => "SELECT COUNT(*) FROM guestbook WHERE user_id = ? GROUP BY user_id",
    'details' => "details=guestbook",
);
$queries[] = array(
    'desc'  => _("Anzahl der Forenpostings"),
    'query' => "SELECT COUNT(*) FROM px_topics WHERE user_id = ? GROUP BY user_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Ankündigungen"),
    'query' => "SELECT COUNT(*) FROM news WHERE user_id = ? GROUP BY user_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Wikiseiten"),
    'query' => "SELECT COUNT(*) FROM wiki WHERE user_id = ? GROUP BY user_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Umfragen"),
    'query' => "SELECT COUNT(*) FROM vote WHERE author_id = ? GROUP BY author_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Evaluationen"),
    'query' => "SELECT COUNT(*) FROM eval WHERE author_id = ? GROUP BY author_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Literatureinträge"),
    'query' => "SELECT COUNT(*) FROM lit_catalog WHERE user_id = ? GROUP BY user_id",
);
$queries[] = array(
    'desc'  => _("Anzahl der Ressourcenobjekte"),
    'query' => "SELECT COUNT(*) FROM resources_objects WHERE owner_id = ? GROUP BY owner_id",
);
$queries[] = array(
    'desc'    => _("Anzahl der Dateien (hochgeladen / verlinkt)"),
    'query'   => "SELECT CONCAT_WS(' / ', COUNT(*) - COUNT(NULLIF(url,'')), COUNT(NULLIF(url,'')))
                  FROM dokumente
                  WHERE user_id = ?
                  GROUP BY user_id",
    'details' => "details=files",
);
$queries[] = array(
    'desc'    => _("Gesamtgröße der hochgeladenen Dateien (MB)"),
    'query'   => "SELECT FORMAT(SUM(filesize)/1024/1024,2)
                  FROM dokumente
                  WHERE user_id = ? AND (url IS NULL OR url = '')
                  GROUP BY user_id",
    'details' => "details=files",
);

include ('lib/seminar_open.php');       // initialise Stud.IP-Session

PageLayout::setTitle(_('Informationen zu einem Nutzer'));
Navigation::activateItem('/admin/config/user');

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');  //hier wird der "Kopf" nachgeladen
$pic_path = $GLOBALS['ASSETS_URL'] . 'images';
?>
<table class="default">
    <tr>
        <td class="blank" align="center">
        <?
        if (count($msg)){
        echo "\n<tr><td class=\"blank\"><table width=\"99%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
        parse_msg_array($msg, "blank", 1 ,false);
        echo "\n</table></td></tr>";
        }
        ?>
        <table width="99%" cellpadding="2" cellspacing="0">
        <tr>
            <td class="topic" colspan="3">
                <b><?=_("Informationen zu einem Nutzer:")?> <?=htmlReady(get_fullname($user_id))?> (<?=$perm->get_perm($user_id)?>)</b>
            </td>
        </tr>
    <? $cssSw = new cssClassSwitcher(); ?>
        <?php
        foreach($queries as $query):
            $statement = DBManager::get()->prepare($query['query']);
            $statement->execute(array($user_id));
            $value = $statement->fetchColumn();
            ?>
            <tr><td <?=$cssSw->getFullClass()?>>
            <b>
            <?= $query['desc'] ?>
            </b></td>
            <td <?=$cssSw->getFullClass()?> >
            <?=htmlReady($value)?>
            </td>
            <td <?=$cssSw->getFullClass()?> width="1%">
            <?=($query['details'] ? '<a href="'.$PHP_SELF.'?'.$query['details'].'">'.Assets::img('icons/16/blue/edit.png').'</a>' : "")?>
            </td>
            </tr>
            <?
            $cssSw->switchClass();
        endforeach; ?>
        </table>
    </td>
    </tr>
    <tr>
    <td class="blank">
    <?if ($_user_activities['details'] == 'files'){?>
    <div style="margin-left:20px;" align="left">
    <?=_("Alle Dateien dieses Nutzers als Zip")?>
    &nbsp;
    <a href="<?=$PHP_SELF?>">
    <?= LinkButton::create(_("Herunterladen"), "?download_as_zip=all") ?>
    </a>
    </div>
    <br>
    <div style="margin-left:20px;">
    <b><?=_("Dateiübersicht Veranstaltungen")?></b>
    <?php
    $query = "SELECT s.Seminar_id, seminar_user.status, IF(s.visible = 0,CONCAT(s.Name, ' ', :hidden), s.Name) AS Name,
                     COUNT(dokument_id) AS numdok, sd1.name AS startsem, IF(s.duration_time = -1, :unlimited, sd2.name) AS endsem
            FROM dokumente d
            INNER JOIN seminare s USING(seminar_id)
            LEFT JOIN semester_data sd1 ON (start_time BETWEEN sd1.beginn AND sd1.ende)
            LEFT JOIN semester_data sd2 ON ((start_time + duration_time) BETWEEN sd2.beginn AND sd2.ende)
            LEFT JOIN seminar_user ON (d.seminar_id = seminar_user.seminar_id AND seminar_user.user_id = :user_id)
            WHERE d.user_id = :user_id
            GROUP BY s.Seminar_id
            ORDER BY sd1.beginn, numdok DESC";
    $statement = DBManager::get()->prepare($query);
    $statement->bindParam(':hidden', _('(versteckt)'));
    $statement->bindParam(':unlimited', _('(unbegrenzt)'));
    $statement->bindParam(':user_id', $user_id);
    $statement->execute();

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $title = $row['Name'] . ' (' . $row['startsem']
                . ($row['startsem'] != $row['endsem'] ? ' - ' . $row['endsem'] : '')
                . ')';
        $addon = $row['numdok'] . '&nbsp;' . _('Dokumente');
        $is_open = $_user_activities['open'][$row['Seminar_id']] ? 'open' : 'close';
        $title = "<a ".($ank == $row['Seminar_id'] ? 'name="dok_anker"' : '')." href=\"$PHP_SELF?".($is_open == 'open' ? 'close' : 'open')."=".$row['Seminar_id']."#dok_anker\" class=\"tree\">".htmlReady($title)."</a>";
        echo "\n<table width=\"80%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
        printhead(0,0,false,$is_open,false, '&nbsp;', $title ,$addon, 0);
        echo "\n</tr></table>";
        $content = "";
        if ($is_open == 'open'){
        echo "\n<table width=\"80%\" cellpadding=\"0\" cellspacing=\"0\"><tr>";
            $content = '<div style="margin-bottom:10px;"><b>'
                        .'<a href="seminar_main.php?auswahl='.$row['Seminar_id']
                        . '&redirect_to=folder.php&cmd=all"><img src="'.Assets::image_path('icons/16/blue/files.png').'" align="absmiddle">'
                        .getHeaderLine($row['Seminar_id']).'</a></b>
                        <br>'._("Status in der Veranstaltung:").'&nbsp;<b>'.$row['status'].'</b></div>';
            $content .= '<div style="margin-bottom:10px;" align="center"><a href="'.$PHP_SELF.'?download_as_zip='.$row['Seminar_id'].'">';
            $content .= Button::create(_('Herunterladen'));
            $content .= '</a>&nbsp;</div>';
            $content .= show_documents(get_user_documents($user_id,$row['Seminar_id']) ,  $_user_activities['open']);

            printcontent(0,0,$content, $edit);
            echo "\n</tr></table>";
        }
    }
    ?>
    <br>
    <b><?=_("Dateiübersicht Einrichtungen")?></b>
    <?php
    $query = "SELECT i.Institut_id,user_inst.inst_perms AS status, i.Name, COUNT(dokument_id) AS numdok
              FROM dokumente d
              INNER JOIN Institute i ON (i.Institut_id = d.seminar_id)
              LEFT JOIN user_inst  ON (d.seminar_id = user_inst.institut_id AND user_inst.user_id = :user_id)
              WHERE d.user_id = :user_id
              GROUP BY i.Institut_id
              ORDER BY numdok DESC";
    $statement = DBManager::get()->prepare($query);
    $statement->bindParam(':user_id', $user_id);
    $statement->execute();
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $title = $row["Name"];
        $addon = $row['numdok'] . '&nbsp;' . _("Dokumente");
        $is_open = $_user_activities['open'][$row['Institut_id']] ? 'open' : 'close';
        $title = "<a ".($ank == $row['Institut_id'] ? 'name="dok_anker"' : '')." href=\"$PHP_SELF?".($is_open == 'open' ? 'close' : 'open')."=".$row['Institut_id']."#dok_anker\" class=\"tree\">".htmlReady($title)."</a>";
        echo "\n<table width=\"80%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
        printhead(0,0,false,$is_open,false, '&nbsp;', $title ,$addon, 0);
        echo "\n</tr></table>";
        $content = "";
        if ($is_open == 'open'){
        echo "\n<table width=\"80%\" cellpadding=\"0\" cellspacing=\"0\"><tr>";
            $content = '<div style="margin-bottom:10px;"><b>'
                        .'<a href="institut_main.php?auswahl='.$row['Institut_id']
                        . '&redirect_to=folder.php&cmd=all"><img src="'.Assets::image_path('icons/16/blue/files.png').'" align="absmiddle" hspace="4" >'
                        .getHeaderLine($row['Institut_id']).'</a></b>
                        <br>'._("Status in der Einrichtung:").'&nbsp;<b>'.$row['status'].'</b></div>';
            $content .= '<div style="margin-bottom:10px;" align="center"><a href="'.$PHP_SELF.'?download_as_zip='.$row['Institut_id'].'">';
            $content .= Button::create(_('Herunterladen'));
            $content .= '</a>&nbsp;</div>';
            $content .= show_documents(get_user_documents($user_id, $row['Institut_id']), $_user_activities['open']);

            printcontent(0,0,$content, $edit);
            echo "\n</tr></table>";
        }
    }
    ?>
    </div>
    <?} elseif (in_array($_user_activities['details'], words('seminar seminar_closed seminar_wait'))) {
        $table = $status = $desc = $where = '';
    ?>
    <div style="margin-left:20px;">
    <?
        switch ($_user_activities['details']){
            case "seminar":
                $table = "seminar_user";
                $status = "seminar_user.status";
                $desc = _("Übersicht Veranstaltungen");
                break;
            case "seminar_closed":
                $table = "seminar_user";
                $status = "seminar_user.status";
                $where = " AND (Schreibzugriff > 2 OR Lesezugriff > 2) ";
                $desc = _("Übersicht geschlossene Veranstaltungen");
                break;
            case "seminar_wait":
                $table = "admission_seminar_user";
                $status = "IF(admission_seminar_user.status='awaiting',CONCAT('awaiting at ', admission_seminar_user.position),admission_seminar_user.status)";
                $desc = _("Übersicht Wartelisten von Veranstaltungen");
            break;
        }

        echo '<b>'.$desc.'</b><br>';

        $query = "SELECT s.Seminar_id, {$status} AS status, IF(s.visible = 0,CONCAT(s.Name, ' ', ?), s.Name) AS Name,
                         sd1.name AS startsem, IF(s.duration_time = -1, ?, sd2.name) AS endsem
                FROM {$table}
                LEFT JOIN seminare s USING(Seminar_id)
                LEFT JOIN semester_data sd1 ON ( start_time BETWEEN sd1.beginn AND sd1.ende)
                LEFT JOIN semester_data sd2 ON ((start_time + duration_time) BETWEEN sd2.beginn AND sd2.ende)
                WHERE user_id = ? {$where}
                GROUP BY s.Seminar_id ORDER BY name DESC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            _('(versteckt)'), _('unbegrenzt'),
            $user_id,
        ));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $title = $row['Name'] . ' (' . $row['startsem']
                    . ($row['startsem'] != $row['endsem'] ? ' - ' . $row['endsem'] : '')
                    . ')';
            $addon = '<b>' . _('Status') . ':&nbsp;' . $row['status'] . '</b>';
            $title = "<a href=\"seminar_main.php?auswahl=".$row['Seminar_id']."&redirect_to=teilnehmer.php#".$_user_activities['username']."\" class=\"tree\">".htmlReady($title)."</a>";
            echo "\n<table width=\"80%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
            printhead(0,0,false,true,false, '&nbsp;', $title ,$addon, 0);
            echo "\n</tr></table>";
        }
        ?>
        </div>
    <?} elseif ($_user_activities['details'] == 'guestbook'){?>
        <div style="margin-left:20px;">
        <b><?=_("Übersicht Gästebucheinträge")?></b>
        <br>
        <?
        $query = "SELECT range_id, COUNT(post_id) AS count, MAX(mkdate) AS newest
                  FROM guestbook
                  WHERE user_id = ?
                  GROUP BY range_id
                  ORDER BY mkdate DESC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $addon = '(' . _('Anzahl:') . '&nbsp;' . $row['count'] . '&nbsp;'. _('Letzter:') . '&nbsp;' . date('d.m.Y h:i:s', $row['newest']) .')';
            $title = get_fullname($row['range_id']);
            $is_open = $_user_activities['open'][$row['range_id']] ? 'open' : 'close';
            $title = "<a ".($ank == $row['range_id'] ? 'name="guest_anker"' : '')." href=\"$PHP_SELF?".($is_open == 'open' ? 'close' : 'open')."=".$row['range_id']."#guest_anker\" class=\"tree\">".htmlReady($title)."</a>";

            echo "\n<table width=\"80%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
            printhead(0,0,false,$is_open,false, '&nbsp;', $title ,$addon, 0);
            echo "\n</tr></table>";
            if ($is_open == 'open'){
                echo "\n<table width=\"80%\" cellpadding=\"0\" cellspacing=\"0\"><tr>";
                $content = '<div style="margin-bottom:10px;"><b>'
                        .'<a href="about.php?username='.get_username($row['range_id'])
                        . '&guestbook=open#guest"><img src="'.Assets::image_path('icons/16/blue/guestbook.png').'" align="absmiddle" hspace="4" >'
                        . _("Gästebuch"). ': ' . htmlReady(get_fullname($row['range_id'])).'</a></b></div>';

                $content .= show_posts_guestbook($user_id, $row['range_id']);
                printcontent(0,0,$content, $edit);
                echo "\n</tr></table>";
            }
        }
        ?>
        </div>
    <?}?>
        </td>
    </tr>
    <tr>
        <td class="blank">&nbsp;</td>
    </tr>
</table>
<?
include ('lib/include/html_end.inc.php');
page_close();
