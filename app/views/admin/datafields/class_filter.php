<?
# Lifter010: TODO
?>

<form action="<?= $controller->url_for('admin/datafields') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <select name="class_filter" onchange="this.form.submit();">
        <option value="-1"><?= _('alle anzeigen') ?></option>
   <? foreach ($allclasses as $key => $class): ?>
         <option value="<?= $key ?>" <?= (!is_null($class_filter) && $class_filter == $key) ? 'selected="selected"' : '' ?>>
             <?= htmlReady($class) ?>
         </option>
    <? endforeach; ?>
    </select>

    <noscript>
        <?= Assets::input("icons/16/blue/accept.png", array('type' => "image", 'class' => "middle", 'name' => "show")) ?>
    </noscript>
</form>
