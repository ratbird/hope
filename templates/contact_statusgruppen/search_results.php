<? if (sizeof($hits)) : ?>
<select name="search_contacts[]" size="4" multiple>
<? foreach ($hits as $hit) : ?>
	<option value="<?= $hit['username'] ?>"><?= htmlReady(my_substr($hit['fullname'], 0, 35)) ?> (<?= $hit['username'] ?>) - <?= $hit['perms'] ?></option>
<? endforeach ?>
<? else : ?>
<?= _("keine Treffer") ?>
<? endif ?>
<input type="image" name="search" src="<?= Assets::image_path('rewind.gif') ?>" border="0" value="1" <?= tooltip(_("neue Suche")) ?>>