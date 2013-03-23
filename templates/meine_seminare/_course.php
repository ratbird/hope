<?
# Lifter010: TODO
global $SEM_CLASS, $SEM_TYPE, $auth;

foreach ($group_members as $member) {
    $semid = $member['seminar_id'];
    $values = $my_obj[$semid];
    $sem_class = $SEM_CLASS[$SEM_TYPE[$my_obj[$semid]['sem_status']]["class"]];
    $studygroup_mode = $sem_class["studygroup_mode"];

    if ($values['obj_type'] == "sem") {

        $lastVisit = $values['visitdate'];
        ?>
        <tr>
            <td class="gruppe<?= $values['gruppe'] ?>">
                <a href="<?= URLHelper::getLink('dispatch.php/meine_seminare/groups') ?>">
                    <?= Assets::img('blank.gif', array('size' => '7@12') + tooltip2(_("Gruppe �ndern"))) ?>
                </a>
            </td>

            <td>
                <? if ($studygroup_mode) { ?>
                    <?= StudygroupAvatar::getAvatar($semid)->getImageTag(Avatar::SMALL, array('title' => htmlReady($values['semname']))) ?>
                <? } else { ?>
                    <?= CourseAvatar::getAvatar($semid)->getImageTag(Avatar::SMALL, array('title' => htmlReady($values['semname']))) ?>
                <? } ?>
            </td>

            <td align="left">
                <a href="seminar_main.php?auswahl=<?= $semid ?>"
                   <?= $lastVisit <= $values["chdate"] ? 'style="color: red;"' : '' ?>>

                    <? if ($studygroup_mode) { ?>

                        <?= htmlReady($values['semname']) ?>

                        <? if ($values['prelim']) { ?>
                            (<?= _("Studiengruppe") ?>, <?= _("geschlossen") ?>)
                        <? } else { ?>
                            (<?= _("Studiengruppe") ?>)
                        <? } ?>

                    <? } else { ?>

                        <?= htmlReady($values['name']) ?>

                    <? } ?>
                </a>


                <? if ($values["visible"] == 0) {

                    $infotext = _("Versteckte Veranstaltungen k�nnen �ber die Suchfunktionen nicht gefunden werden.");
                    $infotext .= " ";
                    if (get_config('ALLOW_DOZENT_VISIBILITY')) {
                        $infotext .= _("Um die Veranstaltung sichtbar zu machen, w�hlen Sie den Punkt \"Sichtbarkeit\" im Administrationsbereich der Veranstaltung.");
                    }
                    else {
                        $infotext .= _("Um die Veranstaltung sichtbar zu machen, wenden Sie sich an eineN der zust�ndigen AdministratorInnen.");
                    }
                ?>
                        <?= _("[versteckt]") ?>
                        <?= tooltipicon($infotext) ?>
                <? } ?>
            </td>
            <td align="left" nowrap="nowrap">
                <? print_seminar_content($semid, $values, "seminar", $sem_class); ?>
            </td>

            <td align="right" nowrap="nowrap">
            <? if (in_array($values["status"], array("dozent", "tutor"))) { ?>
                <?
                $adminmodule = $sem_class->getModule("admin");
                if ($adminmodule) {
                    $adminnavigation = $adminmodule->getIconNavigation($semid, 0, $GLOBALS['user']->id);
                }
                if ($adminnavigation) : ?>
                <a href="<?= URLHelper::getLink($adminnavigation->getURL(), array('cid' => $semid)) ?>">
                    <?
					$image=$adminnavigation->getImage();
            		echo Assets::img($image['src'], array_map("htmlready", $image));
                    ?>
                </a>
                <? endif ?>

            <? } else if ($values["binding"]) { ?>

                    <a href="<?= URLHelper::getLink('', array('auswahl' => $semid, 'cmd' => 'no_kill')) ?>">
                        <?= Assets::img('icons/16/grey/decline/door-leave.png', tooltip2(_("Das Abonnement ist bindend. Bitte wenden Sie sich an die Dozentin oder den Dozenten."))) ?>
                    </a>

            <? } else { ?>

                    <a href="<?= URLHelper::getLink('', array('auswahl' => $semid, 'cmd' => 'suppose_to_kill')) ?>">
                        <?= Assets::img('icons/16/grey/door-leave.png', tooltip2(_("aus der Veranstaltung abmelden"))) ?>
                    </a>
            <? } ?>
            </td>
        </tr>
    <?
    }
}

