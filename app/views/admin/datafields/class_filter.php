<form action="<?= $controller->url_for('admin/datafields') ?>" method="post">
    <select name="class_filter" onchange="this.form.submit();">
        <option value="-1"><?= _('alle anzeigen') ?></option>
   <? foreach ($allclasses as $key => $class): ?>
         <option value = "<?= $key ?>" <?= (!is_null($class_filter) and $class_filter == $key) ? 'selected="selected"' : '' ?>>
             <?= $class ?>
         </option>
    <? endforeach; ?>
    </select>

    <noscript>
        <input type="image" class="middle" name="show" src="<?= Assets::image_path('icons/16/blue/accept.png') ?>">
    </noscript>
</form>