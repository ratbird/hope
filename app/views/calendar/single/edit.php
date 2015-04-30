<? use Studip\Button, Studip\LinkButton; ?>
<? if (Request::isXhr()) : ?>
    <? foreach (PageLayout::getMessages() as $messagebox) : ?>
        <?= $messagebox ?>
    <? endforeach ?>
<? else : ?>
    <? SkipLinks::addIndex(_("Termine anlegen/bearbeiten"), 'main_content', 100); ?>
<? endif; ?>
<form data-dialog="" method="post" action="<?= $controller->url_for($base . 'edit/' . $range_id . '/' . $event->event_id) ?>">
<?= CSRFProtection::tokenTag() ?>
    <table class="default collapsable nohover" id="main_content">
        <caption class="hide-in-dialog">
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
                            <input style="text-align: right;" type="text" name="start_hour" value="<?= date('G', $event->getStart()) ?>" size="2" maxlength="2">
                        </label> :
                        <input style="text-align: right;" type="text" name="start_minute" value="<?= date('i', $event->getStart()) ?>" size="2" maxlength="2">
                    </span>
                    <label style="white-space: nowrap;"><?= _('ganztägig') ?>
                        <input type="checkbox" name="isdayevent" value="1" <?= $event->isDayEvent() ? 'checked' : '' ?>>
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
                        <input type="text" name="end_date" id="end-date" value="<?= strftime('%x', $event->getEnd()) ?>" size="12" required>
                    </label>
                    <span style="white-space: nowrap;">
                        <label><?= _('Uhrzeit') ?>
                            <input style="text-align: right;" type="text" name="end_hour" value="<?= date('G', $event->getEnd()) ?>" size="2" maxlength="2">
                        </label> :
                        <input style="text-align: right;" type="text" name="end_minute" value="<?= date('i', $event->getEnd()) ?>" size="2" maxlength="2">
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
                    <textarea rows="2" cols="40" id="description" name="description"><?= htmlReady($event->getDescription()) ?></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="category-intern">
                        <?= _('Kategorie') ?>:
                    </label>
                </td>
                <td colspan="2">
                    <select name="category_intern" id="category-intern" size="1">
                        <? foreach ($GLOBALS['PERS_TERMIN_KAT'] as $key => $category) : ?>
                        <option value="<?= $key ?>" style="color: <?= $category['color'] ?>"<?= $key == $event->getCategory() ? ' selected' : '' ?>><?= $category['name'] ?></option>
                        <? endforeach; ?>
                    </select>
                    <input type="text" size="40" name="categories" value="<?= htmlReady($event->getUserDefinedCategories()) ?>">
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
                        <option value="<?= $key ?>"<?= $event->getAccessibility() == $key ? ' selected' : '' ?>><?= $option ?></option>
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
                        <option value="<?= $key ?>"<?= $key == $event->getPriority() ? ' selected' : '' ?>><?= $priority ?></option>
                        <? endforeach; ?>
                    </select>
                </td>
            </tr>
            <? if (!$event->isNew() && get_config('CALENDAR_GROUP_ENABLE')) : ?>
            <tr>
                <td colspan="3">
                    <div>
                        <? $author = $event->getAuthor() ?>
                        <? if ($author) : ?>
                            <?= sprintf(_('Eingetragen am: %s von %s'),
                            strftime('%x, %X', $event->mkdate),
                                htmlReady($author->getFullName('no_title'))) ?>
                        <? endif; ?>
                    </div>
                    <? if ($event->event->mkdate < $event->event->chdate) : ?>
                        <? $editor = $event->getEditor() ?>
                        <? if ($editor) : ?>
                        <div>
                            <?= sprintf(_('Zuletzt bearbeitet am: %s von %s'),
                                strftime('%x, %X', $event->chdate),
                                    htmlReady($editor->getFullName('no_title'))) ?>
                        </div>
                        <? endif; ?>
                    <? endif; ?>
                </td>
            </tr>
            <? endif; ?>
        </tbody>
        <tbody class="collapsed">
            <tr class="header-row">
                <th colspan="3" class="toggle-indicator">
                    <a class="toggler"><?= _('Wiederholung') ?>
                    <? if ($event->getRecurrence('rtype') != 'SINGLE') : ?>
                        (<?= $event->toStringRecurrence() ?>)
                    <? endif ?>
                    </a>
                </th>
            </tr>
            <tr>
                <td>
                    <?= _('Wiederholungsart') ?>:
                </td>
                <td colspan="2">
                    <? $linterval = $event->getRecurrence('linterval') ?: '1' ?>
                    <? $rec_type = $event->toStringRecurrence(true) ?>
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
                                    <input type="radio" name="type_daily" value="day"<?= in_array($rec_type, array('daily', 'xdaily')) ? ' checked' : '' ?>>
                                    <label>
                                        <?= sprintf(_('Jeden %s. Tag'), '<input type="text" size="3" name="linterval_d" value="' . $linterval . '">') ?>
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="radio" name="type_daily" value="workday"<?= $rec_type == 'workdaily' ? ' checked' : '' ?>>
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
                                '7' => _('Sonntag')) ?>
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
                                    <? $aday = $event->getRecurrence('wdays') ?: date('N', $event->getStart()) ?>
                                    <? foreach ($wdays as $key => $wday) : ?>
                                    <label style="white-space: nowrap;">
                                        <input type="checkbox" name="wdays[]" value="<?= $key ?>"<?= strpos((string) $aday, (string) $key) !== false ? ' checked' : '' ?>>
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
                            <? $mday_selected = $event->getRecurrence('sinterval') ?>
                            <? foreach ($mdays as $key => $mday) :
                                    $mdays_options .= '<option value="' . $key . '"';
                                    if ($key == $mday_selected) {
                                        $mdays_options .= ' selected';
                                    }
                                    $mdays_options .= '>' . $mday . '</option>';
                            endforeach; ?>
                            <? $wdays_options = '' ?>
                            <? $wday_selected = $event->getRecurrence('wdays') ?: date('N', $event->getStart()) ?>
                            <? foreach ($wdays as $key => $wday) :
                                    $wdays_options .= '<option value="' . $key . '"';
                                    if ($key == $wday_selected) {
                                        $wdays_options .= ' selected';
                                    }
                                    $wdays_options .= '>' . $wday . '</option>';
                            endforeach; ?>
                            <input type="radio" class="rec-select" id="rec-monthly" name="recurrence" value="monthly"<?= $event->getRecurrence('rtype') == 'MONTHLY' ? ' checked' : '' ?>>
                            <label class="rec-label" for="rec-monthly">
                                <?= _('monatlich') ?>
                            </label>
                            <div class="rec-content" id="rec-content-monthly">
                                <div>
                                    <input type="radio" value="day" name="type_m"<?= in_array($rec_type, array('mday_monthly', 'mday_xmonthly')) ? ' checked' : '' ?>>
                                    <? $mday = $event->getRecurrence('day') ?: date('j', $event->getStart()) ?>
                                    <?= sprintf(_('Wiederholt am %s. jeden %s. Monat'),
                                            '<input type="text" name="day_m" size="2" value="'
                                            . $mday . '">',
                                            '<input type="text" name="linterval_m1" size="3" value="'
                                            . $linterval . '">') ?>
                                </div>
                                <div>
                                    <input type="radio" value="wday" name="type_m"<?= in_array($rec_type, array('xwday_xmonthly', 'lastwday_xmonthly', 'xwday_monthly', 'lastwday_monthly')) ? ' checked' : '' ?>>
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
                            <? $month_selected = $event->getRecurrence('month') ?: date('n', $event->getStart()) ?>
                            <? foreach ($months as $key => $month) :
                                    $months_options .= '<option value="' . $key . '"';
                                    if ($key == $month_selected) {
                                        $months_options .= ' selected';
                                    }
                                    $months_options .= '>' . $month . '</option>';
                            endforeach; ?>
                            <input type="radio" class="rec-select" id="rec-yearly" name="recurrence" value="yearly"<?= $event->getRecurrence('rtype') == 'YEARLY' ? ' checked' : '' ?>>
                            <label class="rec-label" for="rec-yearly">
                                <?= _('jährlich') ?>
                            </label>
                            <div class="rec-content" id="rec-content-yearly">
                                <div>
                                    <input type="radio" value="day" name="type_y"<?= $rec_type == 'mday_month_yearly' ? ' checked' : '' ?>>
                                    <?= sprintf(_('Jeden %s. %s'),
                                            '<input type="text" size="2" maxlength="2" name="day_y" value="'
                                            . ($event->getRecurrence('day') ?: date('j', $event->getStart())) . '">',
                                            '<select size="1" name="month_y1">' . $months_options . '</select>') ?>
                                </div>
                                <div>
                                    <input type="radio" value="wday" name="type_y"<?= in_array($rec_type, array('xwday_month_yearly', 'lastwday_month_yearly')) ? ' checked' : '' ?>>
                                    <?= sprintf(_('Jeden %s im %s'),
                                            '<select size="1" name="sinterval_y">' . $mdays_options . '</select>'
                                            . '<select size="1" name="wday_y">' . $wdays_options . '</select>',
                                            '<select size="1" name="month_y2">' . $months_options . '</select>') ?>
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
                            <? $checked = (!$event->getRecurrence('expire') || $event->getRecurrence('expire') >= Calendar::CALENDAR_END) && !$event->getRecurrence('count') ?>
                            <input type="radio" name="exp_c" value="never"<?= $checked ? ' checked' : '' ?>>
                            <?= _('nie') ?>
                        </label>
                    </div>
                    <div>
                        <? $checked = $event->getRecurrence('expire') && $event->getRecurrence('expire') < Calendar::CALENDAR_END && !$event->getRecurrence('count') ?>
                        <input type="radio" name="exp_c" value="date"<?= $checked ? ' checked' : '' ?>>
                        <label>
                            <? $exp_date = $event->getRecurrence('expire') != Calendar::CALENDAR_END ? $event->getRecurrence('expire') : $event->getEnd() ?>
                            <?= sprintf(_('am: %s'),
                                    '<input type="text" size="12" name="exp_date" id="exp-date" value="'
                                    . strftime('%x', $exp_date) . '">') ?>
                        </label>
                    </div>
                    <div>
                        <? $checked = $event->getRecurrence('count') ?>
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
                <td colspan="2">
                    <ul id="exc-dates">
                        <? $exceptions = $event->getExceptions(); ?>
                        <? sort($exceptions, SORT_NUMERIC); ?>
                        <? foreach ($exceptions as $exception) : ?>
                        <li>
                            <label>
                                <input type="checkbox" name="del_exc_dates[]" value="<?= strftime('%d.%m.%Y', $exception) ?>" style="display: none;">
                                <span><?= strftime('%x', $exception) ?><?= Assets::img('icons/16/blue/trash.png', array('title' => _('Ausnahme löschen'), 'style' => 'vertical-align: text-top;')) ?></span>
                            </label>
                            <input type="hidden" name="exc_dates[]" value="<?= strftime('%d.%m.%Y', $exception) ?>">
                        </li>
                        <? endforeach; ?>
                    <? /*
                    <select name="exc_dates" id="exc-dates" size="5" style="width: 10em;" multiple>
                        <? foreach ($event->getExceptions() as $exception) : ?>
                        <option value="<?= strftime('%d.%m.%Y', $exception) ?>"><?= strftime('%x', $exception) ?></option>
                        <? endforeach; ?>
                    </select>
                     * 
                     */?>
                    </ul>
                    <input style="vertical-align: top; opacity: 0.8;" type="text" size="12" name="exc_date" id="exc-date" value="<?= strftime('%x', $atime) ?>">
                    <span style="vertical-align: top;" onclick="STUDIP.CalendarDialog.addException(); return false;">
                        <?= Assets::input('icons/16/blue/add.png', array('class' => 'text-bottom', 'title' => _('Ausnahme hinzufügen'))) ?>
                    </span>
                </td>
            </tr>
        </tbody>
        <? if (get_config('CALENDAR_GROUP_ENABLE') && $calendar->getRange() == Calendar::RANGE_USER) : ?>
            <?= $this->render_partial('calendar/group/_attendees') ?>
        <? endif; ?>
    </table>
    <div style="text-align: center;" data-dialog-button>
        <?= Button::create(_('Speichern'), 'store', array('title' => _('Termin speichern'))) ?>

        <? if (!$event->isNew()) : ?>
        <? if ($event->getRecurrence('rtype') != 'SINGLE') : ?>
        <?= LinkButton::create(_('Aus Serie löschen'), $controller->url_for('calendar/single/delete_recurrence/' . implode('/', $event->getId()) . '/' . $atime)) ?>
        <? endif; ?>
        <?= LinkButton::create(_('Löschen'), $controller->url_for('calendar/single/delete/' . implode('/', $event->getId()))) ?>
        <? endif; ?>
        <? if (!Request::isXhr()) : ?>
        <?= LinkButton::create(_('Abbrechen'), $controller->url_for('calendar/single/' . $last_view, array($event->getStart()))) ?>
        <? endif; ?>
    </div>
</form>
<script>
    jQuery('#start-date').datepicker();
    jQuery('#end-date').datepicker();
    jQuery('#exp-date').datepicker();
    jQuery('#exc-date').datepicker();
</script>