<? if (isset($message)): ?>
  <?= $message ?>
<? endif ?>

<? if (!$no_domains) : ?>
<h3><?= _('Liste der Nutzerdomänen') ?></h3>

<table class="default" style="margin-bottom: 1em;">
    <?= $this->render_partial('admin/domain/domains') ?>
</table>

<? endif ?>