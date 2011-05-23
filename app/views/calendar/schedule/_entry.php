<?
# Lifter010: TODO
?>
<div id="schedule_new_entry" style="display: none;">
    <div id="new_entry_drag" class="window_heading">
        <span id="entry_day"></span>,
        <?= sprintf(_("%s bis %s Uhr"), '<span id="entry_hour_start"></span>:00', '<span id="entry_hour_end"></span>:00') ?>
    </div>

    <form id="new_entry_form" action="<?= $controller->url_for('calendar/schedule/addEntry') ?>" method="post" style="margin: 10px;">

        <?= CSRFProtection::tokenTag() ?>
        <b><?= _("Titel") ?>:</b><br>
        <input id="entry_title" name="entry_title" type="text" style="width: 98%">
        <br>

        <b><?= _("Beschreibung") ?>:</b><br>
        <textarea id="entry_content" name="entry_content" style="width: 98%"></textarea>
        <div style="text-align: right; margin-top: 5px;">
            <a href="javascript:STUDIP.Schedule.showDetails()"><?= _("Termindetails bearbeiten") ?> &gt;&gt;</a>
        </div>

        <br>
        <div style="text-align: center">
            <input type="image" <?= makebutton('speichern', 'src') ?>>
            <input type="hidden" id="new_entry_start_hour" name="start_hour" value="">
            <input type="hidden" id="new_entry_end_hour" name="end_hour" value="">
            <input type="hidden" id="new_entry_day" name="day" value="">
            <span onClick="STUDIP.Schedule.cancelNewEntry();"><?= makebutton('abbrechen') ?></span>
        </div>

    </form>
</div>
<script>
    jQuery('#schedule_new_entry').draggable({ handle: 'new_entry_drag' });
</script>
