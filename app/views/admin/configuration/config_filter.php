<form action="<?= $controller->url_for('admin/configuration/configuration') ?>" method="post">
    <select name="config_filter" onchange="this.form.submit();">
        <option value="-1">
            <?= _('alle anzeigen') ?>
        </option>
        <? foreach (array_keys($allconfigs) as $section): ?>
          <option value = "<?= $section?>"
            <?= (!is_null($config_filter) and $config_filter == $section) ? 'selected="selected"' : '' ?>>
            <? if (empty($section)) : $section = '-'._('Ohne Kategorie').'-'?><?= $section?>
            <? else : ?><?= $section?>
            <? endif;?>
          </option>
        <? endforeach; ?>
    </select>

    <noscript>
        <input type="image" class="middle" name="show" src="<?= Assets::image_path('GruenerHakenButton.png') ?>">
    </noscript>
</form>