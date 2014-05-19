<form action="<?= URLHelper::getLink('?') ?>" method="get">
    <input type="text" name="search" value="<?= htmlReady(Request::get('search'))?>" placeholder="<?= _('Nachrichten durchsuchen') ?>">
    <input type="image" src="<?= Assets::image_path('icons/16/black/search.png') ?>" title="Nachrichten durchsuchen"><br>
    <label><input type="checkbox" name="search_autor" value="1" <?= Request::get("search_autor") || !Request::get("search") ? 'checked="checked"' : '' ?>> <?= _("Verfasser") ?></label>
    <label><input type="checkbox" name="search_subject" value="1" <?= Request::get("search_subject") || !Request::get("search") ? 'checked="checked"' : '' ?>> <?= _("Betreff") ?></label>
    <label><input type="checkbox" name="search_content" value="1"  <?= Request::get("search_content") || !Request::get("search") ? 'checked="checked"' : '' ?>> <?= _("Inhalt") ?></label>
</form>
