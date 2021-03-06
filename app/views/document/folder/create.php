<form action="<?= $controller->url_for('document/folder/create/' . $parent_id) ?>" method="post" class="studip_form">
    <input type="hidden" name="studip-ticket" value="<?= get_ticket() ?>">
    <?= CSRFProtection::tokenTag() ?>

    <fieldset>
        <fieldset class="required">
            <label for="name"><?= _('Name:') ?></label>
            <input type="text" name="name" placeholder="<?= _('Ordnername') ?>" required>
        </fieldset>

        <fieldset>
            <label for="description"><?= _('Beschreibung:') ?></label>
            <textarea name="description" placeholder="<?= _('Optionale Beschreibung f�r den Ordner') ?>"></textarea>
        </fieldset>
    </fieldset>
   
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Erstellen')) ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'),
                                            $controller->url_for('document/files/index/' . $parent_id . '/' . $page)) ?>
    </div>
</form>
