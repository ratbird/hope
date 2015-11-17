<form class="studip-form" action="<?= $controller->url_for('course/members/select_course') ?>" method="post">
    <section>
        <label class="caption" for="course_id">
            <?= _('Zielveranstaltung') ?>:
        </label>
        <?= $search ?>
    </section>
    <section>
        <label class="caption">
            <?= _('Sollen die gew�hlten Personen in die Zielveranstaltung verschoben oder kopiert werden?') ?>
        </label>
        <select name="move">
                <option value="1"><?= _('verschieben') ?></option>
                <option value="0"><?= _('kopieren') ?></option>
        </select>
    </section>
    <?php foreach ($users as $u) : ?>
        <input type="hidden" name="users[]" value="<?= htmlReady($u) ?>"/>
    <?php endforeach ?>
    <?= CSRFProtection::tokenTag() ?>
    <br/><br/>
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Abschicken'), 'submit') ?>
        <?= Studip\Button::createCancel(_('Abbrechen'), 'cancel', array('data-dialog' => 'close')) ?>
    </div>
</form>