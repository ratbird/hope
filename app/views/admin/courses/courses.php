<? $colspan = 2 ?>
<? if ($actions[$selected_action]['multimode']) : ?>
    <? if ($selected_action == 16) : ?>
        <?= MessageBox::error(_('Achtung: Das Archivieren ist ein Schritt, der nicht rückgängig gemacht werden kann!')) ?>
    <? endif ?>
    <form action="<?= URLHelper::getLink($actions[$selected_action]['url']) ?>" method="post">
<? endif ?>
<?= CSRFProtection::tokenTag() ?>
    <table class="default course-admin">
    <colgroup>
        <col width="2%">
        <? if (in_array(_('Nr.'), $view_filter)) : ?>
            <? $colspan++ ?>
            <col width="8%">
        <? endif ?>
        <? if (in_array('Name', $view_filter)) : ?>
            <? $colspan++ ?>
            <col>
        <? endif ?>
        <? if (in_array('Veranstaltungstyp', $view_filter)) : ?>
            <? $colspan++ ?>
            <col width="10%">
        <? endif ?>
        <? if (in_array('Raum/Zeit', $view_filter)) : ?>
            <? $colspan++ ?>
            <col width="30%">
        <? endif ?>
        <? if (in_array('DozentIn', $view_filter)) : ?>
            <? $colspan++ ?>
            <col width="15%">
        <? endif ?>
        <? if (in_array('TeilnehmerInnen', $view_filter)) : ?>
            <? $colspan++ ?>
            <col width="5%">
        <? endif ?>
        <? if (in_array('TeilnehmerInnen auf Warteliste', $view_filter)) : ?>
            <? $colspan++ ?>
            <col width="5%">
        <? endif ?>
        <? if (in_array(_('Vorläufige Anmeldungen'), $view_filter)) : ?>
            <? $colspan++ ?>
            <col width="5%">
        <? endif ?>
        <? if (in_array('Inhalt', $view_filter)) : ?>
            <? $colspan++ ?>
            <col width="8%">
        <? endif ?>
        <col width="15%">
    </colgroup>
    <caption>
        <?=
        sprintf(_('%s im %s'), !is_null($selected_inst) ? htmlReady($selected_inst['Name']) : _('Alle Einrichtungen'), htmlReady($semester->name)) ?>
        <span class="actions">
                <?= sprintf('%u %s', $count_courses, $count_courses > 1 ? _('Veranstaltungen') : _('Veranstaltung')) ?>
            </span>
    </caption>
    <thead>
    <tr class="sortable">
        <th width="2%">
            &nbsp;
        </th>
        <? if (in_array(_('Nr.'), $view_filter)) : ?>
            <th <?= ($sortby == 'VeranstaltungsNummer') ? sprintf('class="sort%s"', strtolower($sortFlag)) : '' ?>>
                <a href="<?=
                URLHelper::getLink('', array('sortby'   => 'VeranstaltungsNummer',
                                             'sortFlag' => strtolower($sortFlag))) ?>"><?= _("Nr.") ?></a>
            </th>
        <? endif ?>
        <? if (in_array('Name', $view_filter)) : ?>
            <th <?= ($sortby == 'Name') ? sprintf('class="sort%s"', strtolower($sortFlag)) : '' ?>>
                <a href="<?=
                URLHelper::getLink('', array('sortby'   => 'Name',
                                             'sortFlag' => strtolower($sortFlag))) ?>"><?= _("Name") ?></a>
            </th>
        <? endif ?>
        <? if (in_array('Veranstaltungstyp', $view_filter)) : ?>
            <th <?= ($sortby == 'status') ? sprintf('class="sort%s"', strtolower($sortFlag)) : '' ?>>
                <a href="<?=
                URLHelper::getLink('', array('sortby'   => 'status',
                                             'sortFlag' => strtolower($sortFlag))) ?>"><?= _("VA-Typ") ?></a>
            </th>
        <? endif ?>
        <? if (in_array('Raum/Zeit', $view_filter)) : ?>
            <th><?= _("Raum/Zeit") ?></th>
        <? endif ?>
        <? if (in_array('DozentIn', $view_filter)) : ?>
            <th><?= _("DozentIn") ?></th>
        <? endif ?>
        <? if (in_array('TeilnehmerInnen', $view_filter)) : ?>
            <th <?= ($sortby == 'teilnehmer') ? sprintf('class="sort%s"', strtolower($sortFlag)) : '' ?>>
                <a href="<?=
                URLHelper::getLink('', array('sortby'   => 'teilnehmer',
                                             'sortFlag' => strtolower($sortFlag))) ?>"><?= _("TN") ?></a>
            </th>
        <? endif ?>
        <? if (in_array('TeilnehmerInnen auf Warteliste', $view_filter)) : ?>
            <th <?= ($sortby == 'waiting') ? sprintf('class="sort%s"', strtolower($sortFlag)) : '' ?>>
                <a href="<?=
                URLHelper::getLink('', array('sortby'   => 'waiting',
                                             'sortFlag' => strtolower($sortFlag))) ?>"><?= _('Warteliste') ?></a>
            </th>
        <? endif ?>
        <? if (in_array(_('Vorläufige Anmeldungen'), $view_filter)) : ?>
            <th <?= ($sortby == 'prelim') ? sprintf('class="sort%s"', strtolower($sortFlag)) : '' ?>>
                <a href="<?=
                URLHelper::getLink('', array('sortby'   => 'prelim',
                                             'sortFlag' => strtolower($sortFlag))) ?>"><?= _('Vorläufig') ?></a>
            </th>
        <? endif ?>
        <? if (in_array('Inhalt', $view_filter)) : ?>
            <th style="width: <?= $nav_elements * 27 ?>px">
                <?= 'Inhalt' ?>
            </th>
        <? endif ?>
        <th style="text-align: center" class="actions">
            <?= _('Aktion') ?>
        </th>
    </tr>
    <? if ($actions[$selected_action]['multimode']) : ?>
        <?= $this->render_partial('admin/courses/additional_inputs.php', compact('colspan')) ?>
        <? if (count($courses) > 10): ?>
            <tr>
                <th colspan="<?= $colspan ?>" style="text-align: right">
                    <?= Studip\Button::createAccept(is_string($actions[$selected_action]['multimode'])
                        ? $actions[$selected_action]['multimode']
                        : $actions[$selected_action]['title'], 'save_action') ?>
                </th>
            </tr>
        <? endif; ?>
    <? endif ?>
    </thead>
    <tbody>
    <? foreach ($courses as $semid => $values) { ?>
        <tr>
            <td>
                <?=
                CourseAvatar::getAvatar($course['seminar_id'])->is_customized()
                    ? CourseAvatar::getAvatar($course['seminar_id'])->getImageTag(Avatar::SMALL, array('title' => tooltip2(trim($values["Name"]))))
                    : Assets::img('icons/20/blue/seminar.png', tooltip2(trim($values["Name"]))) ?>
            </td>
            <? if (in_array('Nr.', $view_filter)) : ?>
                <td>
                    <a href="<?= URLHelper::getLink('seminar_main.php', array('auswahl' => $semid)) ?>">
                        <?= htmlReady($values["VeranstaltungsNummer"]) ?>
                    </a>
                </td>
            <? endif ?>
            <? if (in_array('Name', $view_filter)) : ?>
                <td>
                    <a href="<?= URLHelper::getLink('seminar_main.php', array('auswahl' => $semid)) ?>">
                        <?= htmlReady(trim($values["Name"])) ?>
                    </a>
                    <a data-dialog="buttons=false;size=auto" href="<?= $controller->url_for(sprintf('course/details/index/%s', $semid)) ?>">
                        <? $params = tooltip2(_("Veranstaltungsdetails anzeigen")); ?>
                        <? $params['style'] = 'cursor: pointer'; ?>
                        <?= Assets::img('icons/16/grey/info-circle.png', $params) ?>
                    </a>
                    <? if ($values["visible"] == 0) : ?>
                        <?= _("(versteckt)") ?>
                    <? endif ?>
                </td>
            <? endif ?>
            <? if (in_array('Veranstaltungstyp', $view_filter)) : ?>
                <td>
                    <strong><?= $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$values["status"]]["class"]]['name'] ?></strong>: <?= $GLOBALS['SEM_TYPE'][$values["status"]]["name"] ?>
                </td>
            <? endif ?>
            <? if (in_array('Raum/Zeit', $view_filter)) : ?>
                <td>
                    <? $sem = new Seminar($semid);
                    $_room  = $sem->getDatesHTML(array(
                        'semester_id' => $semester->id,
                        'show_room'   => true
                    ));
                    $_room  = $_room ? $_room : "nicht angegeben";?>
                    <?= $_room ?>
                </td>
            <? endif ?>
            <? if (in_array('DozentIn', $view_filter)) : ?>
                <td><?= $this->render_partial_collection('my_courses/_dozent', $values['dozenten']) ?></td>
            <? endif ?>
            <? if (in_array('TeilnehmerInnen', $view_filter)) : ?>
                <td style="text-align: center;"><?= $values["teilnehmer"] ?></td>
            <? endif ?>
            <? if (in_array('TeilnehmerInnen auf Warteliste', $view_filter)) : ?>
                <td style="text-align: center;"><?= $values["waiting"] ?></td>
            <? endif ?>
            <? if (in_array('Vorläufige Anmeldungen', $view_filter)) : ?>
                <td style="text-align: center;"><?= $values["prelim"] ?></td>
            <? endif ?>
            <? if (in_array('Inhalt', $view_filter)) : ?>
                <td style="text-align: left; white-space: nowrap;">
                    <? if (!empty($values['navigation'])) : ?>
                        <? foreach (MyRealmModel::array_rtrim($values['navigation']) as $key => $nav)  : ?>
                            <? if (isset($nav) && $nav->isVisible(true)) : ?>
                                <? $image = $nav->getImage(); ?>
                                <a href="<?=
                                UrlHelper::getLink('seminar_main.php',
                                    array('auswahl'     => $semid,
                                          'redirect_to' => strtr($nav->getURL(), '?', '&'))) ?>" <?= $nav->hasBadgeNumber() ? 'class="badge" data-badge-number="' . intval($nav->getBadgeNumber()) . '"' : '' ?>>
                                    <?= Assets::img($image['src'], array_map("htmlready", $image)) ?>
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
            <? endif ?>
            <td style="text-align: right;" class="actions">
                <? if ($actions[$selected_action]['multimode'] && is_numeric($selected_action)) : ?>
                    <? switch ($selected_action) {
                        case 8 :
                            echo $this->render_partial('admin/courses/lock.php', compact('values', 'semid'));
                            break;
                        case 9:
                            echo $this->render_partial('admin/courses/visibility.php', compact('values', 'semid'));
                            break;
                        case 10:
                            echo $this->render_partial('admin/courses/aux-select.php', compact('values', 'semid'));
                            break;
                        case 16:
                            echo $this->render_partial('admin/courses/add_to_archive', compact('values', 'semid'));
                            break;
                    }?>
                <? elseif (!is_numeric($selected_action) && $actions[$selected_action]['multimode']) : ?>
                    <? $plugin = PluginManager::getInstance()->getPlugin($selected_action) ?>
                    <? $template = $plugin->getAdminCourseActionTemplate($semid, $values) ?>
                    <?= $template ? $template->render() : "" ?>
                <?
                else : ?>
                    <?=
                    \Studip\LinkButton::createEdit(
                        _($actions[$selected_action]['title']),
                        URLHelper::getURL(sprintf($actions[$selected_action]['url'], $semid),
                            ($actions[$selected_action]['params'] ? $actions[$selected_action]['params'] : array())),
                        ($actions[$selected_action]['attributes'] ? $actions[$selected_action]['attributes'] : array())
                    ) ?>
                <? endif ?>
            </td>
        </tr>
    <? } ?>
    </tbody>
    <? if ($actions[$selected_action]['multimode']) : ?>
        <tfoot>
        <tr>
            <td colspan="<?= $colspan ?>" style="text-align: right">
                <?= Studip\Button::createAccept(
                    is_string($actions[$selected_action]['multimode'])
                        ? $actions[$selected_action]['multimode']
                        : $actions[$selected_action]['title'],
                    $actions[$selected_action]['name']) ?>
            </td>
        </tr>
        </tfoot>
    <? endif ?>
    </table>
<? if ($actions[$selected_action]['multimode']) : ?>
    </form>
<? endif ?>