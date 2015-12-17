<? foreach ($studygroups as $group)  : ?>
    <tr>
        <td class="gruppe<?= $group['gruppe'] ?>"></td>
        <td>
            <?=
            CourseAvatar::getAvatar($group['seminar_id'])->is_customized()
                ? CourseAvatar::getAvatar($group['seminar_id'])->getImageTag(Avatar::SMALL, tooltip2(htmlReady($group['name'])))
                : Icon::create('studygroup', 'clickable', ['title' => htmlReady($group['name'])])->asImg(20) ?>
        </td>
        <td style="text-align: left">
            <a href="<?= URLHelper::getLink('seminar_main.php', array('auswahl' => $group['seminar_id'])) ?>"
                <?= $group['lastvisitdate'] >= $group['chdate'] ? 'style="color: red;"' : '' ?>>
                <?= htmlReady($group['name']) ?>
            </a>
            <? if ($group['visible'] == 0) : ?>
                <? $infotext = _("Versteckte Studiengruppen k�nnen �ber die Suchfunktionen nicht gefunden werden."); ?>
                <? $infotext .= " "; ?>
                <? if (Config::get()->ALLOW_DOZENT_VISIBILITY) : ?>
                    <? $infotext .= _("Um die Studiengruppe sichtbar zu machen, w�hlen Sie den Punkt \"Sichtbarkeit\" im Administrationsbereich der Veranstaltung."); ?>
                <? else : ?>
                    <? $infotext .= _("Um die Studiengruppe sichtbar zu machen, wenden Sie sich an die Admins."); ?>
                <? endif ?>
                <?= _("[versteckt]") ?>
                <?= tooltipicon($infotext) ?>
            <? endif ?>
        </td>
        <td style="text-align: left">
            <? if (!empty($group['navigation'])) : ?>
                <? foreach (MyRealmModel::array_rtrim($group['navigation']) as $key => $nav)  : ?>
                    <? if (isset($nav) && $nav->isVisible(true)) : ?>
                        <a href="<?=
                        UrlHelper::getLink('seminar_main.php',
                            array('auswahl'     => $group['seminar_id'],
                                  'redirect_to' => strtr($nav->getURL(), '?', '&'))) ?>" <?= $nav->hasBadgeNumber() ? 'class="badge" data-badge-number="' . intval($nav->getBadgeNumber()) . '"' : '' ?>>
                            <?= $image->asImg(20, $nav->getLinkAttributes()) ?>
                        </a>
                    <? elseif (is_string($key)) : ?>
                        <?= Assets::img('blank.gif', array('width' => 20, 'height' => 20)); ?>
                    <? endif ?>
                    <? echo ' ' ?>
                <? endforeach ?>
            <? endif ?>
        </td>
        <td style="text-align: right">
            <? if (in_array($group["user_status"], array("dozent", "tutor"))) : ?>
                <? $adminmodule = $group["sem_class"]->getModule("admin"); ?>
                <? if ($adminmodule) : ?>
                    <? $adminnavigation = $adminmodule->getIconNavigation($group['seminar_id'], 0, $GLOBALS['user']->id); ?>
                <? endif ?>
                <? if ($adminnavigation) : ?>
                    <a href="<?= URLHelper::getLink($adminnavigation->getURL(), array('cid' => $group['seminar_id'])) ?>">
                        <?= $adminnavigation->getImage()->asImg(20, $adminnavigation->getLinkAttributes())?>
                    </a>
                <? endif ?>

            <? elseif ($group["binding"]) : ?>
                <a href="<?= URLHelper::getLink('', array('auswahl' => $group['seminar_id'], 'cmd' => 'no_kill')) ?>">
                    <?= Icon::create('door-leave+decline', 'inactive', ['title' => _("Die Teilnahme ist bindend. Bitte wenden Sie sich an die Lehrenden.")])->asImg(20) ?>
                </a>
            <?
            else : ?>
                <a href="<?= URLHelper::getLink('', array('auswahl' => $group['seminar_id'], 'cmd' => 'suppose_to_kill')) ?>">
                    <?= Icon::create('door-leave', 'inactive', ['title' => _("aus der Studiengruppe abmelden")])->asImg(20) ?>
                </a>
            <? endif ?>
        </td>
    </tr>
<? endforeach ?>