<?
use Studip\Button, Studip\LinkButton;
SkipLinks::addIndex(_('Kalender exportieren'), 'main_content', 100);
?>
<form action="<?= $controller->url_for('calendar/single/export_calendar/' . $calendar->getRangeId(), array('atime' => $atime, 'last_view' => $last_view)) ?>" method="post" name="sync_form" id="calendar_sync">
    <table class="default" id="main_content">
        <caption>
            <?= sprintf(_('Kalender exportieren')) ?>
        </caption>
        <colgroup>
            <col width="25%">
            <col>
            <col width="60px">
        </colgroup>
        <tbody>
            <tr class="header-row">
                <th colspan="3" class="toggle-indicator">
                    <a class="toggler"><?= _('Einstellungen') ?></a>
                </th>
            </tr>
            <tr>
                <td>
                    <label for="event-type">
                        <?= _('Welche Termine sollen exportiert werden') ?>:
                    </label>
                </td>
                <td colspan="2">
                    <select name="event_type" id="event-type" size="1">
                        <option value="user" selected><?= _('Nur eigene Termine') ?></option>
                        <option value="course"><?= _('Nur Veranstaltungs-Termine') ?></option>
                        <option value="all"><?= _('Alle Termine') ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="export-all">
                        <?= _('Zeitbereich') ?>:
                    </label>
                </td>
                <td>
                    <div>
                        <label>
                            <input type="radio" name="export_time" value="all" id="export-all" checked>
                            <?= _('Alle Termine') ?>
                        </label>
                    </div>
                    <div>
                        <input type="radio" name="export_time" value="date" id="export-date">
                        <? $start = strtotime('now') ?>
                        <? $end = strtotime('+1 year') ?>
                        <?= sprintf(_('<label>Nur Termine vom: %s</label> <label>bis zum: %s</label>'),
                                '<input id="export-start" type="text" name="export_start" size="10" maxlength="10" class="hasDatepicker" value="'
                                . strftime('%x', $start) . '">',
                                '<input id="export-end" type="text" name="export_end" size="10" maxlength="10" class="hasDatepicker" value="'
                                . strftime('%x', $end) . '">') ?>
                    </div>
                </td>
            </tr>
    </table>
    <div style="text-align: center; clear: both" data-dialog-button>
        <?= Button::createAccept(_('Termine exportieren'), 'export', array('title' => _('Termine exportieren'))) ?>
    </div>
</form>
<script>
    jQuery('#export-start').datepicker();
    jQuery('#export-end').datepicker();
</script>