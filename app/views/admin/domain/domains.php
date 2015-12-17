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
    <?= _('Nutzer/-innen') ?>
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
        <?= Icon::create('edit', 'clickable', ['title' => _('bearbeiten')])->asImg() ?>
      </a>
      <? if (count($domain->getUsers()) == 0): ?>
        <a href="<?= $controller->url_for('admin/domain/delete?id='.$domain->getID()) ?>">
          <?= Icon::create('trash', 'clickable', ['title' => _('löschen')])->asImg() ?>
        </a>
      <? endif ?>
    </td>
  </tr>
<? endforeach ?>

