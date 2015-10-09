<?
# Lifter010: TODO
sort_groups($group_field, $groups);
$group_names = get_group_names($group_field, $groups);
$_my_sem_open['not_grouped'] = $group_field === 'not_grouped';

foreach ($groups as $group_id => $group_members):
    if (!$_my_sem_open['not_grouped']): 
        $last_modified = check_group_new($group_members, $my_obj);
?>
    <tbody class="toggleable <? if (!isset($_my_sem_open[$group_id])) echo 'toggled'; ?>">
        <tr>
            <th nowrap colspan="2">
                <? if (isset($_my_sem_open[$group_id])): ?>
                    <a class="toggle-switch"
                       href="<?= URLHelper::getLink('#' . $group_id, array('view' => $view, 'close_my_sem' => $group_id)) ?>"
                       title="<?= _('Gruppierung schließen') ?>">

                        <?= _('Gruppierung schließen') ?>
                    </a>
                <? else: ?>
                    <a class="toggle-switch"
                       href="<?= URLHelper::getLink('#' . $group_id, array('view' => $view, 'open_my_sem' => $group_id)) ?>"
                       title="<?= _('Gruppierung öffnen') ?>">

                        <?= _('Gruppierung öffnen') ?>
                    </a>
                <? endif; ?>
                <?
                if (is_array($group_names[$group_id])) {
                    $group_name = $group_names[$group_id][1]
                                  ? $group_names[$group_id][1] . ' > ' . $group_names[$group_id][0]
                                  : $group_names[$group_id][0];
                } else {
                    $group_name = $group_names[$group_id];
                }
                ?>

            </th>
            <th colspan="<?= $view == 'ext' ? 3 : 1 ?>">

                <a class="tree"
                   name="<?= $group_id ?>"
                   href="<?= URLHelper::getLink('#' . $group_id, array('view' => $view, ($_my_sem_open[$group_id] ? 'close_my_sem' : 'open_my_sem' ) => $group_id)) ?>"
                   title="<?= _('Gruppierung öffnen') ?>">

                    <?= htmlReady($group_field == "sem_tree_id" ? $group_names[$group_id][0] : $group_names[$group_id]) ?>
                </a>

            <? if ($group_field == "sem_tree_id"): ?>
                <br>
                <small>
                    <sup><?= htmlReady($group_name) ?></sup>
                </small>
            <? endif; ?>
            </th>

            <th colspan="4">
            <? if ($last_modified): ?>
                <?= tooltipIcon(_('Letzte Änderung') . ': '. strftime('%x, %H:%M', $last_modified), true) ?>
            <? endif; ?>
            </th>
        </tr>
    <? endif; ?>
    <? if (isset($_my_sem_open[$group_id])): ?>
        <?= $this->render_partial('meine_seminare/_course', compact('group_members')) ?>
    <? endif; ?>
    </tbody>
<? endforeach; ?>
