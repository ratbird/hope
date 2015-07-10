<label class="caption">
    <?= _('Typ') ?>
    <select name="coursetype">
        <?php foreach ($types as $class => $subtypes) { ?>
            <optgroup label="<?= htmlReady($class) ?>">
                <?php foreach ($subtypes as $type) { ?>
                    <option value="<?= $type['id'] ?>"<?= $type['id'] == $values['coursetype'] ? ' selected="selected"' : '' ?>>
                        <?= htmlReady($type['name']) ?>
                    </option>
                <?php } ?>
            </optgroup>
        <?php } ?>
    </select>
</label>
<label class="caption">
    <?= _('Name') ?>
    <input type="text" name="name" size="75" maxlength="254" value="<?= $values['name'] ?>" required/>
</label>
<label class="caption">
    <?= _('Beschreibung') ?>
    <textarea name="description" cols="75" rows="4"></textarea>
</label>
<label class="caption">
    <?= _('Zugang') ?>
    <select name="access">
        <option value="all"><?= _('offen für alle') ?></option>
        <option value="invite"><?= _('auf Anfrage') ?></option>
        <?php if (Config::get()->STUDYGROUPS_INVISIBLE_ALLOWED) : ?>
        <option value="invisible"><?= _('unsichtbar') ?></option>
        <?php endif ?>
    </select>
</label>
<b><?= _('Nutzungsbedingungen')?></b>
<br/>
<i><?= htmlReady(Config::Get()->STUDYGROUP_TERMS) ?></i>
<label>
    <input type="checkbox" name="accept" required>
    <?= _('Einverstanden') ?>
</label>
<input type="hidden" name="institute" value="<?= $values['institute'] ?>"/>
<input type="hidden" name="start_time" value="<?= $values['start_time'] ?>"/>
<input type="hidden" name="studygroup" value="1"/>
<?php foreach ($values['lecturers'] as $id => $assigned) : ?>
<input type="hidden" name="lecturers[<?= $id ?>]" value="1"/>
<?php endforeach ?>