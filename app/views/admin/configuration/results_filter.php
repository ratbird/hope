<form action="<?= $controller->url_for('admin/configuration/results_configuration') ?>" method="post">
    <input name="search_config" type="text" onchange="this.form.submit();" size="20" value="<?= htmlReady($search) ?>">
    <noscript>
        <input type="image" class="middle" name="search_config" src="<?= Assets::image_path('GruenerHakenButton.png') ?>">
    </noscript>
</form>
