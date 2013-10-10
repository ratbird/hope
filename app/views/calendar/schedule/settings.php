<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;

?>
<div id="schedule-settings-dialog-shadow"></div>
<div id="schedule_settings" class="edit_entry">
    <div class="window_heading_nodrag">
        <?= _("Darstellung des Stundenplans anpassen") ?>
    </div>
    <form method="post" action="<?= $controller->url_for('calendar/schedule/storesettings') ?>" style="margin: 10px;">
        <?= CSRFProtection::tokenTag() ?>
        <div class="settings" style="width: 45%">
            <div><?= _("Angezeigter Zeitraum") ?>:</div>

            <?= _("von") ?>
            <select name="start_hour">
            <? for ($i = 0; $i <= 23; $i++) : ?>
                <option value="<?= $i ?>" <?= $settings['glb_start_time'] == $i ? 'selected="selected"' : '' ?>>
                    <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00
                </option>
            <? endfor ?>
            </select>

            <?= _("bis") ?>

            <select name="end_hour">
            <? for ($i = 0; $i <= 23; $i++) : ?>
                <option value="<?= $i ?>" <?= $settings['glb_end_time'] == $i ? 'selected="selected"' : '' ?>>
                    <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00
                    </option>
            <? endfor ?>
            </select>

            <?= _("Uhr") ?><br>
        </div>

        <div class="settings" style="width: 45%">
            <div><?= _("Angezeigte Wochentage") ?>:</div>
            <? foreach (array(1,2,3,4,5,6,0) as $day) : ?>
                <label>
                    <input type="checkbox" name="days[]" value="<?= $day ?>"
                        <?= in_array($day, $settings['glb_days']) !== false ? 'checked="checked"' : '' ?>>
                    <?= getWeekDay($day, false) ?>
                </label><br>
            <? endforeach ?>
            <br><span class="invalid_message">Bitte mindestens einen Wochentag ausw&auml;hlen.</span><br>
        </div>

        <div style="text-align: center; clear: both">
            <?= Button::createSuccess(_('Speichern'), array('onclick' => "return STUDIP.Calendar.validateNumberOfDays();")) ?>
            <?= LinkButton::createCancel(
                    _('Abbrechen'),
                    $controller->url_for('calendar/schedule'),  
                    array('onclick' => "jQuery('#schedule_settings,#schedule-settings-dialog-shadow').remove(); return false")) ?>
        </div>
    </form>
</div>
