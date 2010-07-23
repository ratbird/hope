<?
$cssSw = new CssClassSwitcher();

foreach ($show_entry['seminars'] as $sem_id) {
    $seminars[] = Seminar::getInstance($sem_id);
}
?>
<div id="edit_inst_entry" class="schedule_edit_entry">
	<div id="edit_inst_entry_drag" class="window_heading">Liste der Veranstaltungen</div>
	<form action="<?= $controller->url_for('calendar/schedule/editseminar/'. $show_entry['id'] .'/'. $show_entry['cycle_id'] ) ?>" method="post" name="edit_entry" style="padding-left: 10px; padding-top: 10px; margin-right: 10px;">
        <table class="default">
            <thead>
                <tr>
                    <th>Nummer</th>
                    <th>Name</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <? foreach ($seminars as $seminar) : ?>
                    <tr <?= $cssSw->getFullClass() ?>>
                        <td width="15%"><?= $seminar->getNumber() ?></td>
                        <td width="60%">
                            <a target="_blank" href="<?= UrlHelper::getLink('details.php?sem_id='. $seminar->getId()) ?>">
                                <?= Assets::img('link_intern') ?>
                                <?= $seminar->getName() ?>
                            </a>
                        </td>
                        <td width="25%">
                            <? $cycle_id = CalendarScheduleModel::getSeminarCycleId($seminar, $show_entry['start'], $show_entry['end']) ?>
                            <? $visible = CalendarScheduleModel::isSeminarVisible($seminar->getId(), $cycle_id) ?>

                            <a id="<?= $seminar->getId() ?>_<?= $cycle_id ?>_hide" href="<?= $controller->url_for('calendar/schedule/adminbind/'. $seminar->getId() .'/'. $cycle_id .'/0') ?>" onClick="STUDIP.Schedule.instSemUnbind('<?= $seminar->getId() ?>', '<?= $cycle_id ?>'); return false;" <?= $visible ? '' : 'style="display: none"' ?>>
                                <?= makebutton('ausblenden') ?>
                            </a>

                            <a id="<?= $seminar->getId() ?>_<?= $cycle_id ?>_show" href="<?= $controller->url_for('calendar/schedule/adminbind/'. $seminar->getId() .'/'. $cycle_id .'/1') ?>" onClick="STUDIP.Schedule.instSemBind('<?= $seminar->getId() ?>', '<?= $cycle_id ?>'); return false;" <?= $visible ? 'style="display: none"' : '' ?>>
                                <?= makebutton('einblenden') ?>
                            </a>
                        </td>
                    </tr>
                    <? $cssSw->switchClass() ?>
                <? endforeach ?>
            </tbody>
        </table>
        <br>

		<div style="text-align: center">
			<a href="<?= $controller->url_for('calendar/schedule') ?>" onClick="return STUDIP.Schedule.hideInstOverlay('#edit_inst_entry')">
                <?= makebutton('schliessen') ?>
            </a>
		</div>
	</form>
</div>
<script>
	$('#edit_inst_entry').draggable({ handle: 'edit_inst_entry_drag' });
</script>
