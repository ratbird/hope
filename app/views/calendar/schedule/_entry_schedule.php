<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;

?>
<div id="edit_entry" class="schedule_edit_entry" <?= $show_entry ? '' : 'style="display: none"' ?>>
    <div id="edit_entry_drag" class="window_heading">Termindetails bearbeiten</div>
    <form action="<?= $controller->url_for('calendar/schedule/addentry'. ($show_entry['id'] ? '/'. $show_entry['id'] : '') ) ?>" method="post" name="edit_entry" style="padding-left: 10px; padding-top: 10px; margin-right: 10px;" onSubmit="return STUDIP.Schedule.checkFormFields()">
        <?= CSRFProtection::tokenTag() ?>
        <b><?= _("Tag") ?>:</b> <select name="entry_day">
            <? foreach (array(1,2,3,4,5,6,7) as $index) : ?>
            <option value="<?= $index ?>" <?= (isset($show_entry['day']) && $show_entry['day'] == $index) ? 'selected="selected"' : '' ?>><?= getWeekDay($index%7, false) ?></option>
            <? endforeach ?>
        </select>

        <div id="schedule_entry_hours">
            <?= _("von") ?>
            <input type="text" size="2" name="entry_start_hour" value="<?= $show_entry['start_hour'] ?>"
                onChange="STUDIP.Calendar.validateHour(this)"> :
            <input type="text" size="2" name="entry_start_minute" value="<?= $show_entry['start_minute'] ?>"
                onChange="STUDIP.Calendar.validateMinute(this)">

            <?= _("bis") ?>
            <input type="text" size="2" name="entry_end_hour" value="<?= $show_entry['end_hour'] ?>"
                onChange="STUDIP.Calendar.validateHour(this)"> :
            <input type="text" size="2" name="entry_end_minute" value="<?= $show_entry['end_minute'] ?>" style="margin-right: 10px"
                onChange="STUDIP.Calendar.validateMinute(this)">

            <span class="invalid_message"><?= _("Die Endzeit liegt vor der Startzeit!") ?></span>
        </div>

        <div id="color_picker">
            <b><?= _("Farbe des Termins") ?>:</b>
            <? foreach ($GLOBALS['PERS_TERMIN_KAT'] as $data) : ?>
            <span style="background-color: <?= $data['color'] ?>; vertical-align: middle; padding-top: 3px;">
                <input type="radio" name="entry_color" value="<?= $data['color'] ?>" <?= ($data['color'] == $show_entry['color']) ? 'checked="checked"' : '' ?>>
            </span>
            <? endforeach ?>
        </div>

        <br>

        <b><?= _("Titel") ?>:</b>
        <input type="text" name="entry_title" style="width: 98%" value="<?= htmlReady($show_entry['title']) ?>">
        <b><?= _("Beschreibung") ?>:</b>
        <textarea name="entry_content" style="width: 98%" rows="7"><?= htmlReady($show_entry['content']) ?></textarea>
        <br>
        <div style="text-align: center">
            <?= Button::createAccept(_('speichern'), array('style' => 'margin-right: 20px')) ?>

            <? if ($show_entry['id']) : ?>
                <?= LinkButton::create(
                        _('löschen'),
                        $controller->url_for('calendar/schedule/delete/'. $show_entry['id']),
                        array('style' => 'margin-right: 20px')) ?>
            <? endif ?>

            <? if ($show_entry) : ?>
                <?= LinkButton::createCancel(
                        _('abbrechen'),
                        $controller->url_for('calendar/schedule'),
                        array('onclick' => 'STUDIP.Schedule.cancelNewEntry(); STUDIP.Calendar.click_in_progress = false;return false;')) ?>
            <? else: ?>
                <?= LinkButton::createCancel(_('abbrechen'), 'javascript:STUDIP.Schedule.cancelNewEntry()') ?>
            <? endif ?>
        </div>
    </form>
</div>
<script>
    jQuery('#edit_entry').draggable({ handle: 'edit_entry_drag' });
</script>
