<tr class="new_area">
    <td class="areaentry"></td>
    <td class="areaentry">
        <form class="add_area_form" method="post" action="<?= PluginEngine::getLink('coreforum/area/add/' . $category_id) ?>">
            <?= CSRFProtection::tokenTag() ?>
            <input type="text" name="name" size="50" maxlength="255" style="width: 99%;" placeholder="<?= _('Name des neuen Bereiches') ?>" required><br>
            <textarea name="content" style="height: 3em; width: 99%;" placeholder="<?= _('Optionale Beschreibung des neuen Bereiches') ?>"></textarea>

            <?= Studip\Button::create(_('Bereich hinzufügen')) ?>
            <?= Studip\LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('coreforum/index/index#cat_'. $category_id)) ?>
        </form>
    </td>
    <td class="postings">0</td>
    <td class="answer" colspan="2"><br><?= _('keine Antworten') ?></td>
</tr>