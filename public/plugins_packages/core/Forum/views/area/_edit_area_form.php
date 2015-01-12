<form method="post" action="<?= PluginEngine::getLink('coreforum/area/edit/' . $entry['topic_id']) ?>">
    <input type="text" name="name" size="20" maxlength="255" style="width: 100%;" value="<?= $entry['name_raw'] ?>" onClick="jQuery(this).focus()"><br>
    <textarea name="content" style="height: 3em;" onClick="jQuery(this).focus()"><?= $entry['content_raw'] ?></textarea>

    <?= Studip\Button::createAccept(_('Speichern')) ?>
    <?= Studip\LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('coreforum/index')) ?>
</form>