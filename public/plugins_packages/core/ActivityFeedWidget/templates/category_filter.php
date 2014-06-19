<form action="<?= PluginEngine::getLink('activityfeed/activities') ?>" method="post">
    <select name="category" onchange="this.form.submit();">
        <option value="">
            <?= _('alles anzeigen') ?>
        </option>
        <? foreach ($categories as $key => $name): ?>
            <option value="<?= $key ?>" <?= $key == $category ? 'selected' : '' ?>>
                <?= $name ?>
            </option>
        <? endforeach ?>
    </select>

    <noscript>
        <input type="image" class="middle" name="show" src="<?= Assets::image_path('GruenerHakenButton.png') ?>">
    </noscript>
</form>
