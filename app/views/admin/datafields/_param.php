<?
# Lifter010: TODO
?>
<form class="datafield_param" action="<?= $controller->url_for('admin/datafields/edit/'.$datafield_id) ?>" method="post" <?= $hidden ? 'style="display:none;"' : '' ?>>
    <?= CSRFProtection::tokenTag() ?>
    <textarea name="typeparam" data-dev="<?= htmlReady($typeparam) ?>" cols="15" rows="5" wrap="off"><?= htmlReady($typeparam) ?></textarea>
    <input type="hidden" name="datafield_id" value="<?= $datafield_id ?>" /><br>
    <?= Assets::input("icons/16/blue/accept.png", array('type' => "image", 'class' => "middle", 'name' => "save", 'title' => _('�nderungen speichern'))) ?>
    <?= Assets::input("icons/16/blue/question-circle.png", array('type' => "image", 'class' => "middle", 'name' => "preview", 'title' => _('preview'), 'style' => ($hidden ? "display:none;" : ""))); ?>
    <a class="cancel" href="<?= $controller->url_for('admin/datafields') ?>">
        <?= Assets::img('icons/16/blue/decline.png', array('title' => _('Bearbeitung abbrechen'))) ?>
    </a>
</form>
