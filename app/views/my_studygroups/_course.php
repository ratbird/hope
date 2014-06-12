<? foreach ($studygroups as $group)  : ?>
    <tr>
        <td class="gruppe<?= $group['gruppe'] ?>"></td>
        <td>
            <?=
            CourseAvatar::getAvatar($group['seminar_id'])->is_customized()
                ? CourseAvatar::getAvatar($group['seminar_id'])->getImageTag(Avatar::SMALL, array('title' => htmlReady($group['name'])))
                : Assets::img('icons/20/blue/studygroup.png') ?>
        </td>
        <td style="text-align: left">
            <a href="<?= URLHelper::getLink('seminar_main.php', array('auswahl' => $group['seminar_id'])) ?>"
                <?= $group['lastvisitdate'] >= $group['chdate'] ? 'style="color: red;"' : '' ?>>
                <?= htmlReady($group['name']) ?>
            </a>
            <? if ($group['visible'] == 0) : ?>
                <? $infotext = _("Versteckte Studiengruppen können über die Suchfunktionen nicht gefunden werden."); ?>
                <? $infotext .= " "; ?>
                <? if (Config::get()->ALLOW_DOZENT_VISIBILITY) : ?>
                    <? $infotext .= _("Um die Studiengruppe sichtbar zu machen, wählen Sie den Punkt \"Sichtbarkeit\" im Administrationsbereich der Veranstaltung."); ?>
                <? else : ?>
                    <? $infotext .= _("Um die Studiengruppe sichtbar zu machen, wenden Sie sich an eineN der zuständigen AdministratorInnen."); ?>
                <? endif ?>
                <?= _("[versteckt]") ?>
                <?= tooltipicon($infotext) ?>
            <? endif ?>
        </td>
        <td style="text-align: left">
            <? if (!empty($group['navigation'])) : ?>
                <? foreach ($group['navigation'] as $key => $nav)  : ?>
                    <? if (isset($nav) && $nav->isVisible(true)) : ?>
                        <? $image = $nav->getImage(); ?>
                        <a href="<?=
                        UrlHelper::getLink('seminar_main.php',
                            array('auswahl'     => $group['seminar_id'],
                                  'redirect_to' => strtr($nav->getURL(), '?', '&'))) ?>" <?= $nav->hasBadgeNumber() ? 'class="badge" data-badge-number="' . intval($nav->getBadgeNumber()) . '"' : '' ?>>
                            <?= Assets::img($image['src'], array_map("htmlready", $image)) ?>
                        </a>
                    <? elseif (is_string($key)) : ?>
                        <?= Assets::img('blank.gif', array('width' => 20, 'height' => 20)); ?>
                    <? endif ?>
                    <? echo ' ' ?>
                <? endforeach ?>
            <? endif ?>
        </td>
        <td style="text-align: right">
            <? if (in_array($values["status"], array("dozent", "tutor"))) : ?>
                <? $adminmodule = $sem_class->getModule("admin"); ?>
                <? if ($adminmodule) : ?>
                    <? $adminnavigation = $adminmodule->getIconNavigation($group['seminar_id'], 0, $GLOBALS['user']->id); ?>
                <? endif ?>
                <? if ($adminnavigation) : ?>
                    <a href="<?= URLHelper::getLink($adminnavigation->getURL(), array('cid' => $group['seminar_id'])) ?>">
                        <?
                        $image = $adminnavigation->getImage();
                        echo Assets::img($image['src'], array_map("htmlready", $image));
                        ?>
                    </a>
                <? endif ?>

            <? elseif ($values["binding"]) : ?>
                <a href="<?= URLHelper::getLink('', array('auswahl' => $group['seminar_id'], 'cmd' => 'no_kill')) ?>">
                    <?= Assets::img('icons/20/grey/decline/door-leave.png', tooltip2(_("Das Abonnement ist bindend. Bitte wenden Sie sich an die Dozentin oder den Dozenten."))) ?>
                </a>
            <?
            else : ?>
                <a href="<?= URLHelper::getLink('', array('auswahl' => $group['seminar_id'], 'cmd' => 'suppose_to_kill')) ?>">
                    <?= Assets::img('icons/20/grey/door-leave.png', tooltip2(_("aus der Studiengruppe abmelden"))) ?>
                </a>
            <? endif ?>
        </td>
    </tr>
<? endforeach ?>