<? foreach ($groups as $group): ?>
    <? if ($selected->id == $group->id) continue; ?>
    <option value="<?= $group->id ?>" <? if ($group->id == $selected->range_id) echo 'selected'; ?> style="text-indent: <?= $level ?>ex;">
        <?= $preset ?><?= htmlReady($group->name) ?>
    </option>
    <? if($group->children): ?>
        <?= $this->render_partial("admin/statusgroups/_edit_subgroupselect.php", array('groups' => $group->children, 'selected' => $selected, 'level' => $level + 1)) ?>
    <? endif; ?>
<? endforeach; ?>