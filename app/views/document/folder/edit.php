<form action="<?= $controller->url_for('document/folder/edit/' . $folder->id) ?>" method="post" class="studip_form">
    <input type="hidden" name="studip-ticket" value="<?= get_ticket() ?>">
    <?= CSRFProtection::tokenTag() ?>

    <fieldset>
        <fieldset class="required">
            <label>
                <?= _('Name:') ?>
                <input type="text" name="name" placeholder="<?= _('Ordnername') ?>" value="<?= htmlReady($folder->name) ?>" required>
            </label>
        </fieldset>

        <fieldset>
             <label>
                 <?= _('Beschreibung:') ?>
                 <textarea name="description" placeholder="<?= _('Optionale Beschreibung f�r den Ordner') ?>"><?= htmlReady($folder->description) ?></textarea>
             </label>
        </fieldset>
    </fieldset>

    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern')) ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for_parent_directory($folder)) ?>
    </div>
</form>
