<section class="required">
    <label for="wizard-coursetype">
        <?= _('Typ') ?>
    </label>
    <select name="coursetype" id="wizard-coursetype">
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
</section>
<section class="required">
    <label for="wizard-name">
        <?= _('Name') ?>
    </label>
    <input type="text" name="name" id="wizard-name" size="75" maxlength="254" value="<?= $values['name'] ?>" required/>
</section>
<section>
    <label for="wizard-description">
        <?= _('Beschreibung') ?>
    </label>
    <textarea name="description" id="wizard-description" cols="75" rows="4"></textarea>
</section>
<section class="required">
    <label for="wizard-access">
        <?= _('Zugang') ?>
    </label>
    <select name="access" id="wizard-access">
        <option value="all"><?= _('offen für alle') ?></option>
        <option value="invite"><?= _('auf Anfrage') ?></option>
        <?php if (Config::get()->STUDYGROUPS_INVISIBLE_ALLOWED) : ?>
            <option value="invisible"><?= _('unsichtbar') ?></option>
        <?php endif ?>
    </select>
</section>
<section class="required">
    <b><?= _('Nutzungsbedingungen')?></b>
    <br/>
    <i><?= htmlReady(Config::Get()->STUDYGROUP_TERMS) ?></i>
    <br/>
    <input type="checkbox" name="accept" id="wizard-accept" required>
    <label for="wizard-accept" class="horizontal">
        <?= _('Einverstanden') ?>
    </label>
</section>
<input type="hidden" name="institute" value="<?= $values['institute'] ?>"/>
<input type="hidden" name="start_time" value="<?= $values['start_time'] ?>"/>
<input type="hidden" name="studygroup" value="1"/>
<?php foreach ($values['lecturers'] as $id => $assigned) : ?>
    <input type="hidden" name="lecturers[<?= $id ?>]" value="1"/>
<?php endforeach ?>