<?
# Lifter010: TODO
?>
<? if (isset($message)): ?>
  <?= $message ?>
<? endif ?>

<? if (count($domains) == 0) : ?>
    <?= MessageBox::info(_('Es sind keine Nutzerdom�nen vorhanden.')) ?>
<? else : ?>

<table class="default">
    <caption>
        <?= _('Liste der Nutzerdom�nen') ?>
    </caption>
    <?= $this->render_partial('admin/domain/domains') ?>
</table>

<? endif ?>
