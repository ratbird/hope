<h3><?= _('Liste der Nutzerdomänen') ?></h3>

<form action="<?= $controller->url_for('domain_admin/save') ?>" method="POST">

  <table class="default" style="margin-bottom: 1em;">
    <?= $this->render_partial('domain_admin/domains') ?>

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
  </table>

  <?= makebutton('uebernehmen', 'input') ?>
</form>
