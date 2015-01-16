<?
use Studip\Button, Studip\LinkButton;
SkipLinks::addIndex(_("Termine anlegen/bearbeiten"), 'main_content', 100);
?>
<? $event = $calendar->getEvent(); ?>
<form method="post" action="<?= $controller->url_for('calendar/single/event/' . $calendar->getRangeId() . '/' . $event->getId()) ?>">
<?= CSRFProtection::tokenTag() ?>
    <table class="default collapsable" id="main_content">
        <caption>
            <? if ($event->isNew()) : ?>
            <?= sprintf(_('Neuen Termin anlegen am %s'), strftime('%x', $event->getStart())) ?>
            <? else : ?>
            <?= _('Termin bearbeiten') ?>
            <? endif; ?>
        </caption>
        <colgroup>
            <col width="25%">
            <col>
            <col width="60px">
        </colgroup>
        <tbody>
            <tr class="header-row">
                <th colspan="3" class="toggle-indicator">
                    <a class="toggler"><?= _('Allgemeine Daten') ?></a>
                </th>
            </tr>
            <tr>
                <td>
                    <label for="start-date" class="required">
                        <?= _('Beginn') ?>:
                    </label>
                </td>
                <td colspan="2">
                    <label><?= _('Datum') ?>
                        <input type="text" name="start_date" id="start-date" value="<?= strftime('%x', $event->getStart()) ?>" size="12" required>
                    </label>
                    <span style="white-space: nowrap;">
                        <label><?= _('Uhrzeit') ?>
                            <input type="text" name="start_hour" value="<?= date('H', $event->getStart()) ?>" size="2">
                        </label> :
                        <input type="text" name="start_minute" value="<?= date('i', $event->getStart()) ?>" size="2">
                    </span>
                    <label style="white-space: nowrap;"><?= _('ganztägig') ?>
                        <input type="checkbox" name="isdayevent" <?= $event->isDayEvent() ? 'checked' : '' ?>>
                    </label>
                </td>
            </tr>
                <td>
                    <label for="end-date" class="required">
                        <?= _('Ende') ?>:
                    </label>
                </td>
                <td colspan="2">
                    <label><?= _('Datum') ?>
                        <input type="text" name="end_date" id="end-date" value="<?= strftime('%x', $event->getStart()) ?>" size="12" required>
                    </label>
                    <span style="white-space: nowrap;">
                        <label><?= _('Uhrzeit') ?>
                            <input type="text" name="end_hour" value="<?= date('H', $event->getStart()) ?>" size="2">
                        </label> :
                        <input type="text" name="end_minute" value="<?= date('i', $event->getStart()) ?>" size="2">
                    </span>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="summary" class="required">
                        <?= _('Zusammenfassung') ?>:
                    </label>
                </td>
                <td colspan="2">
                    <input type="text" size="50" name="summary" id="summary" value="<?= htmlReady($event->getTitle()) ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="description">
                        <?= _('Beschreibung') ?>:
                    </label>
                </td>
                <td colspan="2">
                    <textarea rows="4" cols="40" id="description" name="description"><?= htmlReady($event->getDescription()) ?></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="studip-category">
                        <?= _('Kategorie') ?>:
                    </label>
                </td>
                <td colspan="2">
                    <select name="studip_category" id="studip-category" size="1">
                        <? foreach ($GLOBALS['PERS_TERMIN_KAT'] as $key => $category) : ?>
                        <option value="<?= $key ?>" style="color: <?= $category['color'] ?>"><?= $category['name'] ?></option>
                        <? endforeach; ?>
                    </select>
                    <input type="text" size="40" name="summary" value="<?= htmlReady($event->getUserDefinedCategories()) ?>">
                    <?= tooltipicon(_('Sie können beliebige Kategorien in das Freitextfeld eingeben. Trennen Sie einzelne Kategorien bitte durch ein Komma.')) ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="location">
                        <?= _('Raum/Ort') ?>:
                    </label>
                </td>
                <td colspan="2">
                    <input type="text" size="50" name="location" id="location" value="<?= htmlReady($event->getLocation()) ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="accessibility">
                        <?= _('Zugriff') ?>:
                    </label>
                </td>
                <td colspan="2">
                    <select name="accessibility" id="accessibility" size="1">
                        <? foreach ($event->getAccessibilityOptions($calendar->getPermissionByUser($GLOBALS['user']->id)) as $key => $option) : ?>
                        <option value="<?= $key ?>"><?= $option ?></option>
                        <? endforeach; ?>
                    </select>
                    <? if ($calendar->getPermissionByUser($GLOBALS['user']->id) == Calendar::PERMISSION_OWN) : ?>
                    <? $info = _('Private und vertrauliche Termine sind nur für Sie sichtbar. Öffentliche Termine werden auf ihrer internen Homepage auch anderen Nutzern bekanntgegeben.') ?>
                    <? elseif ($calendar->getRange() == Calendar::RANGE_SEM) : ?>
                    <? $info = _('In Veranstaltungskalendern können nur private Termine angelegt werden.') ?>
                    <? elseif ($calendar->getRange() == Calendar::RANGE_INST) : ?>
                    <? $info = _('In Einrichtungskalendern können nur private Termine angelegt werden.') ?>
                    <? else : ?>
                    <? $info = _('Im Kalender eines anderen Nutzers können Sie nur private oder vertrauliche Termine einstellen. Vertrauliche Termine sind nur für Sie und den Kalenderbesitzer sichtbar. Alle anderen sehen den Termin nur als Besetztzeit.') ?>
                    <? endif; ?>
                    <?= tooltipicon($info) ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="priority">
                        <?= _('Priorität') ?>:
                    </label>
                </td>
                <td colspan="2">
                    <? $priority_names = array(_('keine Angabe'), _('hoch'), _('mittel'), _('niedrig')) ?>
                    <select name="priority" id="priority" size="1">
                        <? foreach ($priority_names as $key => $priority) : ?>
                        <option value="<?= $key ?>"><?= $priority ?></option>
                        <? endforeach; ?>
                    </select>
                </td>
            </tr>
        </tbody>
        <tbody class="collapsed">
            <tr class="header-row">
                <th colspan="3" class="toggle-indicator">
                    <a class="toggler"><?= _('Wiederholung') ?></a>
                </th>
            </tr>
            <tr>
                <td>
                    <?= _('Wiederholungsart') ?>:
                </td>
                <td colspan="2">
                    <? $linterval = $event->getRecurrence('linterval') ?: '1' ?>
                    <ul class="recurrences">
                        <li>
                            <input type="radio" class="rec-select" id="rec-none" name="recurrence" value="single"<?= $event->getRecurrence('rtype') == 'SINGLE' ? ' checked' : '' ?>>
                            <label class="rec-label" for="rec-none">
                                <?= _('keine') ?>
                            </label>
                            <div class="rec-content" id="rec-content-none">
                                <?= _('Der Termin wird nicht wiederholt.') ?>
                            </div>
                        </li>
                        <li>
                            <input type="radio" class="rec-select" id="rec-daily" name="recurrence" value="daily"<?= $event->getRecurrence('rtype') == 'DAILY' ? ' checked' : '' ?>>
                            <label class="rec-label" for="rec-daily">
                                <?= _('täglich') ?>
                            </label>
                            <div class="rec-content" id="rec-content-daily">
                                <div>
                                    <input type="radio" name="type_daily" value="day"<?= $event->getRecurrence('wdays') ? '' : ' checked' ?>>
                                    <label>
                                        <?= sprintf(_('Jeden %s. Tag'), '<input type="text" size="3" name="linterval_d" value="' . $linterval . '">') ?>
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="radio" name="type_daily" value="workday"<?= $event->getRecurrence('wdays') == '12345' ? ' checked' : '' ?>>
                                        <?= _('Jeden Werktag') ?>
                                    </label>
                                </div>
                            </div>
                        </li>
                        <li>
                            <? $wdays = array(
                                '1' => _('Montag'),
                                '2' => _('Dienstag'),
                                '3' => _('Mittwoch'),
                                '4' => _('Donnerstag'),
                                '5' => _('Freitag'),
                                '6' => _('Samstag'),
                                '7>' => _('Sonntag')) ?>
                            <input type="radio" class="rec-select" id="rec-weekly" name="recurrence" value="weekly"<?= $event->getRecurrence('rtype') == 'WEEKLY' ? ' checked' : '' ?>>
                            <label class="rec-label" for="rec-weekly">
                                <?= _('wöchentlich') ?>
                            </label>
                            <div class="rec-content" id="rec-content-weekly">
                                <div>
                                    <label>
                                        <?= sprintf(_('Jede %s. Woche am:'), '<input type="text" size="3" name="linterval_w" value="' . $linterval . '">') ?>
                                    </label>
                                </div>
                                <div>
                                    <? $aday = date('N', $event->getStart()) ?>
                                    <? foreach ($wdays as $key => $wday) : ?>
                                    <label style="white-space: nowrap;">
                                        <input type="checkbox" name="wdays[]" value="<?= $key ?>"<?= $key == $aday ? ' checked' : '' ?>>
                                        <?= $wday ?>
                                    </label>
                                    <? endforeach; ?>
                                </div>
                            </div>
                        </li>
                        <li>
                            <? $mdays = array(
                                '1' => _('ersten'),
                                '2' => _('zweiten'),
                                '3' => _('dritten'),
                                '4' => _('vierten'),
                                '5' => _('letzten')) ?>
                            <? $mdays_options = '' ?>
                            <? foreach ($mdays as $key => $mday) : ?>
                                <? $mdays_options .= '<option value="' . $key . '">' . $mday . '</option>' ?>
                            <? endforeach; ?>
                            <? $wdays_options = '' ?>
                            <? foreach ($wdays as $key => $wday) : ?>
                                <? $wdays_options .= '<option value="' . $key . '">' . $wday . '</option>' ?>
                            <? endforeach; ?>
                            <input type="radio" class="rec-select" id="rec-monthly" name="recurrence" value="monthly"<?= $event->getRecurrence('rtype') == 'MONTHLY' ? ' checked' : '' ?>>
                            <label class="rec-label" for="rec-monthly">
                                <?= _('monatlich') ?>
                            </label>
                            <div class="rec-content" id="rec-content-monthly">
                                <div>
                                    <input type="radio" value="day" name="type_m">
                                    <? $mday = $event->getRecurrence('day') ?: date('j', $event->getStart()) ?>
                                    <?= sprintf(_('Wiederholt am %s. jeden %s. Monat'),
                                            '<input type="text" name="day_m" size="2" value="'
                                            . $mday . '">',
                                            '<input type="text" name="linterval_m1" size="3" value="'
                                            . $linterval . '">') ?>
                                </div>
                                <div>
                                    <input type="radio" value="wday" name="type_m">
                                    <?= sprintf(_('Jeden %s alle %s Monate'),
                                            '<select size="1" name="sinterval_m">' . $mdays_options . '</select>'
                                            . '<select size="1" name="wday_m">' . $wdays_options . '</select>',
                                            '<input type="text" size="3" maxlength="3" name="linterval_m2" value="'
                                            . $linterval . '">')?>
                                </div>
                            </div>
                        </li>
                        <li>
                            <? $months = array(
                                '1' => _('Januar'),
                                '2' => _('Februar'),
                                '3' => _('März'),
                                '4' => _('April'),
                                '5' => _('Mai'),
                                '6' => _('Juni'),
                                '7' => _('Juli'),
                                '8' => _('August'),
                                '9' => _('September'),
                                '10' => _('Oktober'),
                                '11' => _('November'),
                                '12' => _('Dezember')) ?>
                            <? $months_options = '' ?>
                            <? foreach ($months as $key => $month) : ?>
                            <? $months_options .= '<option value="' . $key . '">' . $month . '</option>' ?>
                            <? endforeach; ?>
                            <input type="radio" class="rec-select" id="rec-yearly" name="recurrence" value="yearly"<?= $event->getRecurrence('rtype') == 'YEARLY' ? ' checked' : '' ?>>
                            <label class="rec-label" for="rec-yearly">
                                <?= _('jährlich') ?>
                            </label>
                            <div class="rec-content" id="rec-content-yearly">
                                <div>
                                    <input type="radio" checked="" value="day" name="type_y">
                                    <?= sprintf(_('Jeden %s. %s'),
                                            '<input type="text" size="2" maxlength="2" name="day_y" value="'
                                            . $event->getRecurrence('day') . '">',
                                            '<select size="1" name="month_y1">' . $months_options . '</select>') ?>
                                </div>
                                <div>
                                    <input type="radio" value="wday" name="type_y">
                                    <?= sprintf(_('Jeden %s im %s'),
                                            '<select size="1" name="sinterval_y">' . $mdays_options . '</select>'
                                            . '<select size="1" name="wday_y">' . $wdays_options . '</select>',
                                            '<select size="1" name="month_y1">' . $months_options . '</select>') ?>
                                </div>
                            </div>
                        </li>
                    </ul>
                </td>
            </tr>
            <tr>
                <td>
                    <?= _('Wiederholung endet') ?>:
                </td>
                <td colspan="2">
                    <div>
                        <label>
                            <? $checked = !$event->getRecurrence('expire') && !$event->getRecurrence('count') ?>
                            <input type="radio" name="exp_c" value="never"<?= $checked ? ' checked' : '' ?>>
                            <?= _('nie') ?>
                        </label>
                    </div>
                    <div>
                        <? $checked = $event->getRecurrence('expire') && !$event->getRecurrence('count') ?>
                        <input type="radio" name="exp_c" value="date"<?= $checked ? ' checked' : '' ?>>
                        <label>
                            <? $exp_date = $event->getRecurrence('expire') ?: time() ?>
                            <?= sprintf(_('am: %s'),
                                    '<input type="text" size="12" name="exp_date" id="exp-date" value="'
                                    . strftime('%x', $exp_date) . '">') ?>
                        </label>
                    </div>
                    <div>
                        <? $checked = !$event->getRecurrence('expire') && $event->getRecurrence('count') ?>
                        <input type="radio" name="exp_c" value="count"<?= $checked ? ' checked' : '' ?>>
                        <label>
                            <? $exp_count = $event->getRecurrence('count') ?: '10' ?>
                            <?= sprintf(_('nach %s Wiederholungen'),
                                    '<input type="text" size="5" name="exp_count" value="'
                                    . $exp_count . '">') ?>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="exc-dates">
                        <?= _('Ausnahmen') ?>:
                    </label>
                </td>
                <? $exceptions = array_map(function ($exc) { return strftime('%x', $exc); }, $event->getExceptions()) ?>
                <td colspan="2">
                    <textarea rows="5" cols="12" name="exc_dates"><?= implode("\n", $exceptions) ?></textarea>
                </td>
        </tbody>
    </table>
    <div style="text-align: center; clear: both" data-dialog-button>
        <?= Button::createSuccess(_('Speichern'), 'store', array('title' => _('Termin speichern'))) ?>
        <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('calendar/single/' . $last_view, array($event->getStart()))) ?>
    </div>
</form>
<script>
    jQuery('#start-date').datepicker();
    jQuery('#end-date').datepicker();
    jQuery('#exp-date').datepicker();
</script>