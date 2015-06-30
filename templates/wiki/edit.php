<form method="post" action="<?= URLHelper::getLink('?cmd=edit', compact('keyword')) ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="wiki" value="<?= htmlReady($keyword) ?>">
    <input type="hidden" name="version" value="<?= htmlReady($version) ?>">
    <input type="hidden" name="submit" value="true">
    <input type="hidden" name="cmd" value="show">

    <textarea name="body" class="wiki-editor add_toolbar wysiwyg resizable" data-secure="true"><?= wysiwygReady($body) ?></textarea>
    <br><br>
    <div class="button-group">
        <?= Studip\Button::createAccept(_('Speichern')) ?>
        <?= Studip\Button::create(_('Speichern und weiter bearbeiten'), 'submit-and-edit') ?>
    </div>
    <?= Studip\LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL('?cmd=abortedit' . $lastpage, compact('keyword'))) ?>
</form>

