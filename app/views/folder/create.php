<form action="<?= URLHelper::getLink('folder.php?cid=' . $id . '&data[cmd]=tree#anker') ?>" method="post" class="studip_form">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset style="padding-top: 0;">
        <fieldset>
            <label for="open"><?= _('Name:') ?></label>
            <select name="open" id="open" style="vertical-align:middle" aria-label="<?= _('Name für neuen Ordner auswählen') ?>" onchange="$(this).closest('fieldset').next().toggle(this.selectedIndex === 0);">
            <? foreach ($options as $id => $label): ?>
                <option value="<?= htmlReady($id) ?>_a_"><?= htmlReady($label) ?>
            <? endforeach; ?>
            </select>
        </fieldset>
        <fieldset>
            <label for="top_folder_name"><?= _('Neuer Name:') ?></label>
            <input type="text" name="top_folder_name" id="top_folder_name"
                   size="40" autofocus
                   placeholder="<?= _('Name für neuen Ordner eingeben') ?>"
                   aria-label="<?= _('Name für neuen Ordner eingeben') ?>">
        </fieldset>
    </fieldset>
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Anlegen'), 'anlegen') ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'),
                                            URLHelper::getLink('folder.php?cid=' . $id . '&cmd=tree'))?>
    </div>
</form>
