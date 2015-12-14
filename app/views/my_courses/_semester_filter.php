<form action="<?=$controller->url_for('my_courses/set_semester')?>">
<select name="sem_select" onchange="jQuery(this).closest('form').submit();">
    <option <?= ($sem == 'current' ? 'selected' : '')?> value="current"><?= _('Aktuelles Semester') ?></option>
    <option <?= ($sem == 'future' ? 'selected' : '')?> value="future"><?= _('Aktuelles und n�chstes Semester') ?></option>
    <option <?= ($sem == 'nexttwo' ? 'selected' : '')?> value="nexttwo"><?= _('Aktuelles und zwei n�chste Semester') ?></option>
    <option <?= ($sem == 'last' ? 'selected' : '')?> value="last"><?= _('Aktuelles und letztes Semester') ?></option>
    <option <?= ($sem == 'lastandnext' ? 'selected' : '')?> value="lastandnext"><?= _('Letztes, aktuelles, n�chstes Semester') ?></option>
    <? if (Config::get()->MY_COURSES_ENABLE_ALL_SEMESTERS) : ?>
        <option <?= ($sem == 'all' ? 'selected' : '')?> value="all"><?= _('Alle Semester') ?></option>
    <? endif ?>

    <? if (!empty($semesters)) : ?>
        <optgroup label="<?=_('Semester ausw�hlen')?>">
        <? foreach ($semesters as $semester) :?>
            <option value="<?=$semester->id?>" <?= ($sem == $semester->id ? 'selected' : '')?>>
                <?= htmlReady($semester->name)?>
            </option>
        <? endforeach ?>
        </optgroup>
    <? endif ?>
</select>
    <noscript>
        <?= \Studip\Button::createAccept(_('Ausw�hlen'))?>
    </noscript>
</form>
