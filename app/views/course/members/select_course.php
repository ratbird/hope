<form class="studip_form" action="<?= $controller->url_for('course/members/select_course') ?>" method="post">
    <label class="caption" for="course_id">
        <?= _('Zielveranstaltung') ?>:
    </label>
    <?= $search ?>
    <br/><br/>
    <label class="caption">
        <?= _('Sollen die gewählten Personen in die Zielveranstaltung verschoben oder kopiert werden?') ?>
        <select name="move">
            <option value="1"><?= _('verschieben') ?></option>
            <option value="0"><?= _('kopieren') ?></option>
        </select>
    </label>
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