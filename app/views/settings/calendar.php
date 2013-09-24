<?
use Studip\Button, Studip\LinkButton;

$cal_views = array(
    'showday'   => _('Tagesansicht'),
    'showweek'  => _('Wochenansicht'),
    'showmonth' => _('Monatsansicht'),
    'showyear'  => _('Jahresansicht'),
);
$cal_deletes = array(
    12 => _('12 Monate nach Ablauf'),
     6 => _('6 Monate nach Ablauf'),
     3 => _('3 Monate nach Ablauf'),
     0 => _('nie'),
);
$cal_step_days = array(
     600 => _('10 Minuten'),
     900 => _('15 Minuten'),
    1800 => _('30 Minuten'),
    3600 => _('1 Stunde'),
    7200 => _('2 Stunden'),
);
$cal_step_weeks = array(
    1800 => _('30 Minuten'),
    3600 => _('1 Stunde'),
    7200 => _('2 Stunden'),
);
?>

<form method="post" action="<?= $controller->url_for('settings/calendar/store') ?>">
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
    <?= CSRFProtection::tokenTag() ?>

<table class="default" id="main_content">
    <colgroup>
        <col width="50%">
        <col width="50%">
    </colgroup>
    <caption><?= _('Einstellungen des Terminkalenders anpassen') ?></caption>
    <tbody>
        <tr>
            <th colspan="2"><?= _('Allgemeine Optionen') ?></th>
        </tr>
        <tr>
            <td>
                <label for="cal_view"><?= _('Startansicht anpassen') ?></label>
            </td>
            <td>
                <select name="cal_view" id="cal_view" size="1">
                <? foreach ($cal_views as $index => $label): ?>
                    <option value="<?= $index ?>" <? if ($view == $index) echo 'selected'; ?>>
                        <?= $label ?>
                    </option>
                <? endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label><?= _('Wochenansicht anpassen') ?></label>
            </td>
            <td>
                <label>
                    <input type="radio" name="cal_type_week" value="LONG"
                           <? if ($type_week == 'LONG') echo 'checked'; ?>>
                    <?= _('7 Tage-Woche') ?>
                </label>
                <br>

                <label>
                    <input type="radio" name="cal_type_week" value="SHORT"
                           <? if ($type_week == 'SHORT') echo 'checked'; ?>>
                    <?= _('5 Tage-Woche') ?>
                </label>
            </td>
        </tr>
        <tr>
            <td>
                <label for="cal_delete"><?= _('L&ouml;schen von Terminen'); ?></label>
            </td>
            <td>
                <select name="cal_delete" id="cal_delete" size="1">
                <? foreach ($cal_deletes as $index => $label): ?>
                    <option value="<?= $index ?>" <? if ($delete == $index) echo 'selected'; ?>>
                        <?= $label ?>
                    </option>
                <? endforeach; ?>
                </select>
            </td>
        </tr>
<? if (get_config('CALENDAR_GROUP_ENABLE')): ?>
    </tbody>
    <tbody>
        <tr>
            <th colspan="2"><?= _('Einzelterminkalender') ?></th>
        </tr>
<? endif ?>
        <tr>
            <td>
                <label><?= _('Zeitraum der Tages- und Wochenansicht') ?></label>
            </td>
            <td>
                <select name="cal_start" aria-label="<?= _('Startzeit der Tages- und Wochenansicht') ?>">
                <? for ($i = 0; $i < 24; $i += 1): ?>
                    <option value="<?= $i ?>" <? if ($start == $i) echo 'selected'; ?>>
                        <?= sprintf('%02u:00', $i) ?>
                    </option>
                <? endfor; ?>
                </select>
                <?= _('Uhr bis') ?>
                <select name="cal_end" aria-label="<?= _('Endzeit der Tages- und Wochenansicht') ?>">
                <? for ($i = 0; $i < 24; $i += 1): ?>
                    <option value="<?= $i ?>" <? if ($end == $i) echo 'selected'; ?>>
                        <?= sprintf('%02u:00', $i) ?>
                    </option>
                <? endfor; ?>
                </select>
                <?= _('Uhr.') ?>
            </td>
        </tr>
        <tr>
            <td>
                <label for="cal_step_day"><?= _('Zeitintervall der Tagesansicht') ?></label>
            </td>
            <td>
                <select name="cal_step_day" for="cal_step_day">
                <? foreach ($cal_step_days as $index => $label): ?>
                    <option value="<?= $index ?>" <? if ($step_day == $index) echo 'selected'; ?>>
                        <?= $label ?>
                    </option>
                <? endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label for="cal_step_week"><?= _('Zeitintervall der Wochenansicht') ?></label>
            </td>
            <td>
                <select name="cal_step_week" id="cal_step_week">
                <? foreach ($cal_step_weeks as $index => $label): ?>
                    <option value="<?= $index ?>" <? if ($step_week == $index) echo 'selected'; ?>>
                        <?= $label ?>
                    </option>
                <? endforeach; ?>
                </select>
            </td>
        </tr>
<?/*
        <tr>
            <td><?= _('Feiertage/Semesterdaten:') ?></td>
            <td>
                <label>
                    <input type="checkbox" name="cal_holidays" value="TRUE"
                           <? if ($holidays) echo 'checked'; ?>>
                    <?= _('Feiertage anzeigen') ?>
                </label>
                <br>

                <label>
                    <input type="checkbox" name="cal_sem_data" value="5"
                           <? if ($sem_data) echo 'checked'; ?>>
                    <?= _('Semesterdaten anzeigen') ?>
                </label>
            </td>
        </tr>
*/?>
<? if (get_config('CALENDAR_GROUP_ENABLE')): ?>
    </tbody>
    <tbody>
        <tr>
            <th colspan="2"><?= _('Gruppenterminkalender') ?></th>
        </tr>
        <tr>
            <td>
                <label for="cal_step_day_group"><?= _("Zeitintervall der Tagesansicht"); ?></label>
            </td>
            <td>
                <select name="cal_step_day_group" id="cal_step_day_group">
                <? foreach ($cal_step_days as $index => $label): ?>
                    <option value="<?= $index ?>" <? if ($step_day_group == $index) echo 'selected'; ?>>
                        <?= $label ?>
                    </option>
                <? endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label for="cal_step_week_group"><?= _('Zeitintervall der Wochenansicht') ?></label>
            </td>
            <td>
                <select name="cal_step_week_group" id="cal_step_week_group">
                <? foreach ($cal_step_weeks as $index => $label): ?>
                    <option value="<?= $index ?>" <? if ($step_week_group) echo 'selected'; ?>>
                        <?= $label ?>
                    </option>
                <? endforeach; ?>
                </select>
            </td>
        </tr>
<? endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2">
            <? if (Request::option('atime')): ?>
                <input type="hidden" name="atime" value="<?= Request::option('atime') ?>">
            <? endif ?>
                <input type="hidden" name="view" value="calendar">
                <?= Button::create(_('�bernehmen'), array('title' => _('�nderungen �bernehmen'))) ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>
