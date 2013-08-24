<?
# Lifter010: TODO
?>
<? if (isset($message)): ?>
  <?= $message ?>
<? endif ?>

<? if (count($domains) == 0) : ?>
    <?= MessageBox::info(_('Es sind keine Nutzerdomänen vorhanden.')) ?>
<? else : ?>

<table class="default">
    <caption>
        <?= _('Liste der Nutzerdomänen') ?>
    </caption>
    <?= $this->render_partial('admin/domain/domains') ?>
</table>

<? endif ?>
