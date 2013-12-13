<? foreach ($groups as $group): ?>
    <? if ($selected->id == $group->id) continue; ?>
    <option value='<?= $group->id ?>' <?= $group->id == $selected->range_id ? "selected" : "" ?>><?= $preset ?><?= htmlReady($group->name) ?></option>
    <? if($group->children): ?>
        <?= $this->render_partial("admin/statusgroups/_edit_subgroupselect.php", array('groups' => $group->children, 'selected' => $selected, 'preset' => $preset."&nbsp;")) ?>
    <? endif; ?>
<? endforeach; ?>