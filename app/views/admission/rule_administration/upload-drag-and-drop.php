<form action="<?= $controller->url_for('admission/ruleadministration/install') ?>"
      method="post" enctype="multipart/form-data" class="drag-and-drop">
    <?= CSRFProtection::tokenTag() ?>
    <?= _('Anmelderegel via Drag and Drop installieren') ?>
    <input type="file" name="upload_file">
</form>