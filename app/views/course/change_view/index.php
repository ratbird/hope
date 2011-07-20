<?
/*
 * index.php - contains view for changing view in a course
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.1
 */

/* * * * * * * * * * * * *
 * * * I N F O B O X * * *
 * * * * * * * * * * * * */
$infobox['picture'] = CourseAvatar::getAvatar($SessSemName[1])->getUrl(Avatar::NORMAL);

$infobox['content'][] = array(
    'kategorie' => _("Information"),
    'eintrag'   => array(
        array(
            'text' => _("Hier können Sie festlegen, dass Sie die ".
                "Veranstaltung aus der Sicht einer Person sehen wollen, ".
                "die nicht Dozent/-in ist und somit überprüfen, wie ".
                "für Ihre Teilnehmer/-innen die Veranstaltung aussieht."),
            "icon" => "icons/16/black/info.png"
         )
     )
);
// ende Infobox

header('Location: '.$controller->url_for('course/change_view/set?cid='.Request::option('cid')));
?>
<!--
<div style="padding-left:0.5em; background-color: white;">
    <h2 class="smashbox_kategorie"><?=_("Ansicht simulieren");?></h2>

    <div class="smashbox_stripe">
        <br/>
        <div style="margin-left: 1.5em;">
        <br/>
        <form action="<?= $controller->url_for('course/change_view/set?cid='.Request::option('cid')) ?>" method="post">
        <?php if (!$_SESSION['seminar_change_view'] || $_SESSION['seminar_change_view']['cid'] != Request::option('cid')) { ?>
            <?= _('Veranstaltung anzeigen, wie sie für Teilnehmer mit folgender Berechtigung aussieht:') ?>
            <select name="change_view_perm" size="1">
                <option value="">-- <?= _('bitte auswählen'); ?> --</option>
                <option value="tutor">tutor</option>
                <option value="autor">autor</option>
                <option value="user">user</option>
            </select>
            <input type="hidden" name="cid" value="<?= Request::get('cid'); ?>"/>
            <?= makeButton('uebernehmen', 'input', 
                'Veranstaltung mit der angegebenen Berechtigung anzeigen', 
                'set_seminar_view'); ?>
            <br/>
            <?= _('Wenn Sie hier eine Berechtigungsstufe auswählen, werden Sie '.
                'die Veranstaltung genauso sehen, wie es eine/e Teilnehmer/-in '.
                'mit dieser Berechtigung tut.'); ?>
            <br/><br/>
            <?= _('Ihre gewohnte Ansicht als Dozent/-in können Sie auf dieser '.
                'Seite wieder einstellen.'); ?>
        <?php } else { ?>
            <?= _('Veranstaltung wieder auf die normale Ansicht für Dozent/-innen zurücksetzen:') ?>
            <input type="hidden" name="cid" value="<?= Request::get('cid'); ?>"/>
            <?= makeButton('uebernehmen', 'input', 
                'Ansicht der Veranstaltung zurücksetzen', 
                'set_seminar_view'); ?>
            <br/><br/>
            <?= _('Durch Klick auf "übernehmen" sehen Sie die Veranstaltung wieder als Dozent/-in.'); ?>
        <?php } ?>
        </form>
        </div>
        <br style="clear: both;">
    </div>
</div>
-->