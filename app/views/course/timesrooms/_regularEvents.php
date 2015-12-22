<section class="contentbox timesrooms">
    <header>
        <h1>
            <?= _('Regelmäßige Termine') ?>
        </h1>
        <nav>
            <a class="link-add"
               href="<?= $controller->link_for('course/timesrooms/createCycle', $editParams) ?>"
               data-dialog="size=600"
               title="<?= _('Regelmäßigen Termin hinzufügen') ?>">
                <?= _('Regelmäßigen Termin hinzufügen') ?>
            </a>
        </nav>
    </header>

<? if (!empty($cycle_dates)) : ?>
    <? foreach ($cycle_dates as $metadate_id => $cycle) : ?>

        <form class="default collapsable" action="<?= $controller->url_for('course/timesrooms/stack/' . $metadate_id, $editParams) ?>"
              method="post" <?= Request::isXhr() ? 'data-dialog="size=big"' : ''?>>
            <?= CSRFProtection::tokenTag() ?>

            <article id="<?= $metadate_id ?>" class="<?= ContentBoxHelper::classes($metadate_id) ?>">
                <header class="<?= $course->getCycleColorClass($metadate_id) ?>">
                    <h1>
                    <? if ($info = $course->getBookedRoomsTooltip($metadate_id)) : ?>
                        <?= tooltipIcon($info); ?>
                    <? elseif ($course->getCycleColorClass($metadate_id) === 'red'): ?>
                        <?= tooltipIcon(_('Keine Raumbuchungen vorhanden')) ?>
                    <? else: ?>
                        <?= tooltipIcon(_('Keine offenen Raumbuchungen')) ?>
                    <? endif; ?>
                        <a href="<?= ContentBoxHelper::href($metadate_id) ?>">
                            <?= htmlReady($cycle['cycle']->toString('long')) ?>
                        </a>
                    </h1>
                    <nav>
                        <span>
                            <?= _('Raum') ?>:
                        <? if (count($cycle['room_request']) > 0): ?>
                            <?= htmlReady(array_pop($cycle['room_request'])->name)?>
                        <? else : ?>
                            <?= _('keiner') ?>
                        <? endif; ?>
                        </span>
                        <span>
                            <?= _('Einzel-Raumanfrage') ?>:
                            <?= htmlReady($course->getRequestsInfo($metadate_id)) ?>
                        </span>
                        <span>
                            <a href="<?= $controller->url_for('course/timesrooms/createCycle/' . $metadate_id) ?>"
                               data-dialog="size=big">
                                <?= Icon::create('edit', 'clickable', ['title' => _('Diesen Zeitraum bearbeiten')])->asImg() ?>
                            </a>
                            <?= Icon::create('trash', 'clickable', ['title' => _('Diesen Zeitraum löschen')])
                                    ->asInput(['formaction' => $controller->url_for('course/timesrooms/deleteCycle/' . $metadate_id),
                                               'data-confirm' => _('Soll dieser Zeitraum wirklich gelöscht werden?')] + $linkAttributes) ?>
                        </span>
                    </nav>
                </header>
                <section>
                    <table class="default nohover">
                        <colgroup>
                            <col width="30px">
                            <col width="30%">
                            <col>
                            <col width="20%">
                            <col width="50px">
                        </colgroup>
                    <? foreach ($cycle['dates'] as $semester_id => $termine) : ?>
                        <thead>
                            <tr>
                                <th colspan="5"><?= htmlReady(Semester::find($semester_id)->name) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <? foreach ($termine as $termin) : ?>
                            <?= $this->render_partial('course/timesrooms/_cycleRow.php',
                                    array('termin' => $termin,'class_ids' => 'ids-regular')) ?>
                        <? endforeach ?>
                        </tbody>
                    <? endforeach ?>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <label>
                                        <input type="checkbox"
                                                data-proxyfor=".ids-regular"
                                                data-activates=".actionForAllRegular">
                                        <?= _('Alle auswählen') ?>
                                    </label>
                                </td>
                                <td colspan="3" class="actions">
                                    <select name="method" class="actionForAllRegular">
                                        <?= $this->render_partial('course/timesrooms/_stack_actions.php') ?>
                                    </select>
                                    <?= Studip\Button::create(_('Ausführen'), 'run', array('class' => 'actionForAllRegular','data-dialog' => 'size=big')) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                </section>
            </article>
        </form>
    <? endforeach; ?>

<? else: ?>
    <section>
        <p class="text-center">
            <strong><?= _('Keine regelmäßige Termine vorhanden') ?></strong>
        </p>
    </section>
<? endif; ?>
</section>
