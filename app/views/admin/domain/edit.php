<?
# Lifter010: TODO
?>
<h3><?= _('Liste der Nutzerdomänen') ?></h3>

<form action="<?= $controller->url_for('admin/domain/save') ?>" method="POST">
<?= CSRFProtection::tokenTag() ?>
<table class="default" style="margin-bottom: 1em;">
    <?= $this->render_partial('admin/domain/domains') ?>

    <? if (!isset($edit_id)): ?>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
          <input type="hidden" name="new_domain" value="1">
          <input type="text" style="width: 80%;" name="name" value="">
        </td>
        <td>
          <input type="text" style="width: 80%;" name="id" value="">
        </td>
        <td></td>
        <td></td>
    </tr>
    <? endif ?>
    <tr>
        <td colspan="4" align="center">
            <?= makebutton('uebernehmen2', 'input', _('Änderungen speichern')) ?>
            <a href="<?= $controller->url_for('admin/domain') ?>">
                <?= makeButton("abbrechen", "img", _("abbrechen"), 'abort') ?>
            </a>
        </td>
    </tr>
</table>
</form>
