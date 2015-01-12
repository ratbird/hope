<script type="text/template" class="edit_category">
<span class="edit_category">
    <input type="text" required name="name" maxlength="255" size="40" value="<%- name %>">

    <?= ForumHelpers::replace(Studip\LinkButton::createAccept(_('Kategorie speichern'),
            "javascript:STUDIP.Forum.saveCategoryName('%%%- category_id ###');")) ?>
    <?= ForumHelpers::replace(Studip\LinkButton::createCancel(_('Abbrechen'), 
            "javascript:STUDIP.Forum.cancelEditCategoryName('%%%- category_id ###')")) ?>
</span>
</script>

<script type="text/template" class="edit_area">
<span class="edit_area">
    <input type="text" name="name" size="20" maxlength="255" style="width: 100%;" value="<%- name %>" onClick="jQuery(this).focus()"><br>
    <textarea name="content" style="height: 3em;" onClick="jQuery(this).focus()"><%- content %></textarea>

    <?= ForumHelpers::replace(Studip\LinkButton::createAccept(_('Speichern'),
            "javascript:STUDIP.Forum.saveArea('%%%- area_id ###');")) ?>
    <?= ForumHelpers::replace(Studip\LinkButton::createCancel(_('Abbrechen'), 
        "javascript:STUDIP.Forum.cancelEditArea('%%%- area_id ###');")) ?>
</span>
</script>

<script type="text/template" class="add_area">
<tr class="new_area">
    <td class="areaentry"></td>
    <td class="areaentry">
        <form class="add_area_form">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="category_id" value="<%- category_id %>">
            <input type="text" name="name" size="50" maxlength="255" style="width: 99%;" placeholder="<?= _('Name des neuen Bereiches') ?>" required><br>
            <textarea name="content" style="height: 3em; width: 99%;" placeholder="<?= _('Optionale Beschreibung des neuen Bereiches') ?>"></textarea>

            <?= Studip\LinkButton::create(_('Bereich hinzufügen'), "javascript:STUDIP.Forum.doAddArea();") ?>
            <?= Studip\LinkButton::createCancel(_('Abbrechen'), "javascript:STUDIP.Forum.cancelAddArea();") ?>
        </form>
    </td>
    <td class="postings">0</td>
    <td class="answer" colspan="2"><br><?= _('keine Antworten') ?></td>
</tr>
</script>