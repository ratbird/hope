<? if (isset($error_msg)): ?>
  <?= MessageBox::error($error_msg) ?>
<? endif ?>

<h3><?= _('Liste der Nutzerdomänen') ?></h3>

<form action="<?= $controller->url_for('domain_admin/new') ?>" method="POST">

  <table class="default" style="margin-bottom: 1em;">
    <?= $this->render_partial('domain_admin/domains') ?>
  </table>

  <?= makebutton('anlegen', 'input') ?>
</form>
