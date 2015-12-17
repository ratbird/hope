<? foreach ($course_collection as $course)  : ?>
    <? $sem_class = $course['sem_class']; ?>
    <tr>
        <td class="gruppe<?= $course['gruppe'] ?>"></td>
        <td>
            <? if ($sem_class['studygroup_mode']) : ?>
                <?=
                StudygroupAvatar::getAvatar($course['seminar_id'])->is_customized()
                    ? StudygroupAvatar::getAvatar($course['seminar_id'])->getImageTag(Avatar::SMALL, tooltip2($course['name']))
                    : Icon::create('studygroup', 'clickable', ['title' => $course['name']])->asImg(20) ?>
            <? else : ?>
                <?=
                CourseAvatar::getAvatar($course['seminar_id'])->is_customized()
                    ? CourseAvatar::getAvatar($course['seminar_id'])->getImageTag(Avatar::SMALL, tooltip2($course['name']))
                    : Icon::create('seminar', 'clickable', ['title' => $course['name']])->asImg(20) ?>
            <? endif ?>
        </td>
        <? if($config_sem_number) :?>
            <td><?= $course['veranstaltungsnummer']?></td>
        <? endif?>
        <td style="text-align: left">
            <a href="<?= URLHelper::getLink('seminar_main.php', array('auswahl' => $course['seminar_id'])) ?>"
                <?= $course['visitdate'] <= $course['chdate'] ? 'style="color: red;"' : '' ?>>
                <?= htmlReady($course['name']) ?>
                <?= ($course['is_deputy'] ? ' ' . _("[Vertretung]") : '');?>
            </a>
            <? if ($course['visible'] == 0) : ?>
                <? $infotext = _("Versteckte Veranstaltungen können über die Suchfunktionen nicht gefunden werden."); ?>
                <? $infotext .= " "; ?>
                <? if (Config::get()->ALLOW_DOZENT_VISIBILITY) : ?>
                    <? $infotext .= _("Um die Veranstaltung sichtbar zu machen, wählen Sie den Punkt \"Sichtbarkeit\" im Administrationsbereich der Veranstaltung."); ?>
                <? else : ?>
                    <? $infotext .= _("Um die Veranstaltung sichtbar zu machen, wenden Sie sich an Admins."); ?>
                <? endif ?>
                <?= _("[versteckt]") ?>
                <?= tooltipicon($infotext) ?>
            <? endif ?>
        </td>
        <td>
            <? if (!$sem_class['studygroup_mode']) : ?>
                <a data-dialog href="<?= $controller->url_for(sprintf('course/details/index/%s', $course['seminar_id'])) ?>">
                    <? $params = tooltip2(_("Veranstaltungsdetails")); ?>
                    <? $params['style'] = 'cursor: pointer'; ?>
                    <?= Icon::create('info-circle', 'inactive')->asImg(20, $params) ?>
                </a>
            <? else : ?>
                <?= Assets::img('blank.gif', array('width'  => 20, 'height' => 20)); ?>
            <? endif ?>
        </td>
        <td style="text-align: left; white-space: nowrap;">
            <? if (!empty($course['navigation'])) : ?>
                <? foreach (MyRealmModel::array_rtrim($course['navigation']) as $key => $nav)  : ?>
                    <? if (isset($nav) && $nav->isVisible(true)) : ?>
                        <a href="<?=
                        UrlHelper::getLink('seminar_main.php',
                            array('auswahl'     => $course['seminar_id'],
                                  'redirect_to' => strtr($nav->getURL(), '?', '&'))) ?>" <?= $nav->hasBadgeNumber() ? 'class="badge" data-badge-number="' . intval($nav->getBadgeNumber()) . '"' : '' ?>>
                            <?= $nav->getImage()->asImg(20, $nav->getLinkAttributes()) ?>
                        </a>
                    <? elseif (is_string($key)) : ?>
                        <?=
                        Assets::img('blank.gif', array('width'  => 20,
                                                       'height' => 20)); ?>
                    <? endif ?>
                    <? echo ' ' ?>
                <? endforeach ?>
            <? endif ?>

        </td>
        <td style="text-align: right">
            <? if (in_array($course["user_status"], array("dozent",
                                                          "tutor"))
            ) : ?>
                <? $adminmodule = $sem_class->getModule("admin"); ?>
                <? if ($adminmodule) : ?>
                    <? $adminnavigation = $adminmodule->getIconNavigation($course['seminar_id'], 0, $GLOBALS['user']->id); ?>
                <? endif ?>

                <? if ($adminnavigation) : ?>
                    <a href="<?= URLHelper::getLink($adminnavigation->getURL(), array('cid' => $course['seminar_id'])) ?>">
                        <?= $adminnavigation->getImage()->asImg(20, $adminnavigation->getLinkAttributes()) ?>
                    </a>
                <? endif ?>

            <? elseif ($values["binding"]) : ?>
                <a href="<?= $controller->url_for('my_courses/decline_binding') ?>">
                    <?= Icon::create('door-leave+decline', 'inactive', ['title' => _("Die Teilnahme ist bindend. Bitte wenden Sie sich an die Lehrenden.")])->asImg(20) ?>
                </a>
            <?
            else : ?>
                <a href="<?= URLHelper::getLink(sprintf('dispatch.php/my_courses/decline/%s', $course['seminar_id']), array('cmd' => 'suppose_to_kill')) ?>">
                    <?= Icon::create('door-leave', 'inactive', ['title' => _("aus der Veranstaltung abmelden")])->asImg(20) ?>
                </a>
            <? endif ?>
        </td>
    </tr>
<? endforeach ?>
