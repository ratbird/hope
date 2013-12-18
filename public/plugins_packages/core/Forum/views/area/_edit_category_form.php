<form method="post" action="<?= PluginEngine::getLink('coreforum/index/edit_category/' . $category_id) ?>">
    <input type="text" required name="name" size="40" value="<?= htmlReady($categories[$category_id]) ?>">

    <?= Studip\Button::createAccept(_('Kategorie speichern'), '', 
        array('onClick' => "javascript:STUDIP.Forum.saveCategoryName('". $category_id ."'); return false;")) ?>
    <?= Studip\LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('coreforum/index/index#cat_'. $category_id),
        array('onClick' => "STUDIP.Forum.cancelEditCategoryName('". $category_id ."'); return false;")) ?>
</form>