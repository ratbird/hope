<form action="<?= $controller->url_for('document/folder/create/' . $parent_id) ?>" method="post" class="studip_form">
    <?= CSRFProtection::tokenTag() ?>

    <fieldset>
        <fieldset class="required">
            <label for="name"><?= _('Name:') ?></label>
            <input type="text" name="name" placeholder="<?= _('Ordnername') ?>" required>
        </fieldset>

        <fieldset>
            <label for="description"><?= _('Beschreibung:') ?></label>
            <textarea name="description" placeholder="<?= _('Optionale Beschreibung für den Ordner') ?>"></textarea>
        </fieldset>
    </fieldset>
   
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Erstellen')) ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('document/files/index/' . $parent_id)) ?>
    </div>
</form>
