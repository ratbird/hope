<?
# Lifter010: TODO
?>
<form action="<?= $controller->url_for('admin/configuration/configuration') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <select name="config_filter" onchange="this.form.submit();">
        <option value="-1"><?= _('alle anzeigen') ?></option>
    <? foreach ($allsections as $section): ?>
         <option value = "<?= $section?>"
           <?= (!is_null($config_filter) and $config_filter == $section) ? 'selected="selected"' : '' ?>>
             <?= empty($section) ? '- '._('Ohne Kategorie').' -' : $section ?>
         </option>
    <? endforeach; ?>
    </select>

    <noscript>
        <input type="image" class="middle" name="show" src="<?= Assets::image_path('icons/16/blue/accept.png') ?>">
    </noscript>
</form>