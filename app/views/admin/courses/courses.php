<? $colspan = 2 ?>
<? if ($actions[$selected_action]['multimode']) : ?>
    <? if ($selected_action == 16) : ?>
        <?= MessageBox::error(_('Achtung: Das Archivieren ist ein Schritt, der nicht rückgängig gemacht werden kann!'))?>
    <? endif ?>
    <form action="<?= URLHelper::getLink($actions[$selected_action]['url']) ?>" method="post">
<? endif ?>
<?= CSRFProtection::tokenTag() ?>
    <table class="default course-admin">
        <colgroup>
            <col width="2%">
            <? if (in_array('Nr.', $view_filter)) : ?>
                <? $colspan++ ?>
                <col width="10%">
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
                <col width="3%">
            <? endif ?>
            <col width="18%">
        </colgroup>
        <caption>
            <?=
            sprintf(_('%s im %s'), htmlReady($selected_inst['Name']), htmlReady($semester->name)) ?>
            <span class="actions">
                <?= sprintf('%u %s', $count_courses,  $count_courses > 1 ? _('Veranstaltungen') : _('Veranstaltung'))?>
            </span>
        </caption>
        <thead>
        <tr class="sortable">
            <th width="2%">
                &nbsp;
            </th>
            <? if (in_array('Nr.', $view_filter)) : ?>
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
                                                 'sortFlag' => strtolower($sortFlag))) ?>"><?= _("Anzahl") ?></a>
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
                    <?= Studip\Button::createAccept(sprintf('%s', $actions[$selected_action]['button_name']), 'save_action') ?>
                </th>
            </tr>
        <? endif; ?>
    <? endif ?>
        </thead>
        <tbody>
        <? foreach ($courses as $semid => $values) { ?>
            <?
            $sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$values["status"]]["class"]];
            $lastVisit = $values['visitdate'];
            ?>
            <tr>
                <td>
                    <?=
                    CourseAvatar::getAvatar($course['seminar_id'])->is_customized()
                        ? CourseAvatar::getAvatar($course['seminar_id'])->getImageTag(Avatar::SMALL, array('title' => htmlReady($course['name'])))
                        : Assets::img('icons/20/blue/seminar.png', tooltip2(_('Studienbereiche anzeigen'))) ?>
                </td>
                <? if (in_array('Nr.', $view_filter)) : ?>
                    <td>
                        <a href="<?= URLHelper::getLink('seminar_main.php', array('auswahl' => $semid)) ?>">
                            <?= $values["VeranstaltungsNummer"] ?>
                        </a>
                    </td>
                <? endif ?>
                <? if (in_array('Name', $view_filter)) : ?>
                    <td>
                        <a href="<?= URLHelper::getLink('seminar_main.php', array('auswahl' => $semid)) ?>"
                           style="<?= lastVisit <= $values['chdate'] ? 'color: red;' : '' ?>">
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
                        <strong><?= $values['sem_class_name'] ?></strong>: <?= $GLOBALS['SEM_TYPE'][$values["status"]]["name"] ?>
                    </td>
                <? endif ?>
                <? if (in_array('Raum/Zeit', $view_filter)) : ?>
                    <td>
                        <? $sem = new Seminar($semid);
                        $_room = $sem->getDatesHTML(array(
                            'semester_id' => $semester->id,
                            'show_room'   => true
                        ));
                        $_room = $_room ? $_room : "nicht angegeben";?>
                        <?= $_room ?>
                    </td>
                <? endif ?>
                <? if (in_array('DozentIn', $view_filter)) : ?>
                    <td><?= $this->render_partial_collection('my_courses/_dozent', $values['dozenten']) ?></td>
                <? endif ?>
                <? if (in_array('TeilnehmerInnen', $view_filter)) : ?>
                    <td style="text-align: center;"><?= $values["teilnehmer"] ?></td>
                <? endif ?>
                <td style="text-align: right;">
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
                    <? elseif(!is_numeric($selected_action) && $actions[$selected_action]['multimode']) : ?>
                        <? $plugin = PluginManager::getInstance()->getPlugin($selected_action) ?>
                        <? $template = $plugin->getAdminCourseActionTemplate($sem_id, $values) ?>
                        <?= $template ? $template->render() : "" ?>
                    <? else : ?>
                        <?=
                        \Studip\LinkButton::createEdit(
                            _($actions[$selected_action]['button_name']),
                            URLHelper::getURL(sprintf($actions[$selected_action]['url'], $semid),
                                ($actions[$selected_action]['params'] ? $actions[$selected_action]['params'] : array())),
                            ($actions[$selected_action]['attributes'] ? $actions[$selected_action]['attributes'] : array())
                        )?>
                    <? endif ?>
                </td>
            </tr>
        <? } ?>
        </tbody>
        <? if ($actions[$selected_action]['multimode']) : ?>
            <tfoot>
            <tr>
                <td colspan="<?= $colspan ?>" style="text-align: right">
                    <?= Studip\Button::createAccept(sprintf(_('%s'), $actions[$selected_action]['button_name']), $actions[$selected_action]['name']) ?>
                </td>
            </tr>
            </tfoot>
        <? endif ?>
    </table>
<? if ($actions[$selected_action]['multimode']) : ?>
    </form>
<? endif ?>