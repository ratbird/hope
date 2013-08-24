<?
# Lifter010: TODO
?>
<thead>
<tr>
  <th style="width: 40%;">
    <?= _('Name') ?>
  </th>
  <th style="width: 35%;">
    <?= _('ID') ?>
  </th>
  <th style="width: 15%;">
    <?= _('NutzerInnen') ?>
  </th>
  <th class="aktions" style="width: 10%;">
    <?= _('Aktionen') ?>
  </th>
</tr>
</thead>
<tbody>
<? foreach ($domains as $domain): ?>
  <tr>
    <td>
      <? if (isset($edit_id) && $edit_id === $domain->getID()): ?>
        <input type="hidden" name="id" value="<?= $edit_id ?>">
        <input type="text" style="width: 80%;" name="name" value="<?= htmlReady($domain->getName()) ?>">
      <? else: ?>
        <?= htmlReady($domain->getName()) ?>
      <? endif ?>
    </td>
    <td>
      <?= $domain->getID() ?>
    </td>
    <td>
      <?= count($domain->getUsers()) ?>
    </td>
    <td class="actions">
      <a href="<?= $controller->url_for('admin/domain/edit?id='.$domain->getID()) ?>">
        <?= Assets::img('icons/16/blue/edit.png', array('title' => _('bearbeiten'))) ?>
      </a>
      <? if (count($domain->getUsers()) == 0): ?>
        <a href="<?= $controller->url_for('admin/domain/delete?id='.$domain->getID()) ?>">
          <?= Assets::img('icons/16/blue/trash.png', array('title' => _('l�schen'))) ?>
        </a>
      <? endif ?>
    </td>
  </tr>
<? endforeach ?>

