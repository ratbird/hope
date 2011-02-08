<?
# Lifter010: TODO
?>
<form action="<?= $controller->url_for('admin/configuration/results_configuration') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <input name="search_config" type="text" onchange="this.form.submit();" size="20" value="<?= htmlReady($search) ?>">
    <noscript>
        <input type="image" class="middle" name="search_config" src="<?= Assets::image_path('icons/16/blue/accept.png') ?>">
    </noscript>
</form>
