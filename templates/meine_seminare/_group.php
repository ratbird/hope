<?
sort_groups($group_field, $groups);
$group_names = get_group_names($group_field, $groups);
foreach ($groups as $group_id => $group_members) {
    if ($group_field == 'not_grouped') {

        $_my_sem_open['not_grouped'] = true;

    } else {
        $last_modified = check_group_new($group_members, $my_obj);
        ?>
        <tr>
            <td class="blank" colspan="<?= $view == 'ext' ? 7 : 5 ?>">
                <?= Assets::img("blank.gif", array("size" => "1@5")) ?>
            </td>
        </tr>
        <tr>
            <td class="blue_gradient" valign="top" nowrap height="20" colspan="2">
                <?= Assets::img("blank.gif", array("size" => "1@20", "style" => "vertical-align: middle;")) ?>
                <? if (isset($_my_sem_open[$group_id])) { ?>

                    <a class="tree" style="font-weight:bold;" name="<?= $group_id ?>"
                       href="<?= URLHelper::getLink($_SERVER['PHP_SELF'] . '#' . $group_id, array('view' => $view, 'close_my_sem' => $group_id)) ?>"
                       <?= tooltip(_("Gruppierung schließen"), true) ?>>

                        <?= Assets::img($last_modified ? 'forumrotrunt.gif' : 'forumgraurunt.gif') ?>
                    </a>
                <? } else { ?>
                    <a class="tree" name="<?= $group_id ?>"
                       href="<?= URLHelper::getLink($_SERVER['PHP_SELF'] . '#' . $group_id, array('view' => $view, 'open_my_sem' => $group_id)) ?>"
                       <?= tooltip(_("Gruppierung öffnen"), true) ?>>

                        <?= Assets::img($last_modified ? 'forumrot.gif' : 'forumgrau.gif') ?>
                    </a>
                <?

                }

                if (is_array($group_names[$group_id])) {
                    $group_name = $group_names[$group_id][1]
                                  ? $group_names[$group_id][1] . " > " . $group_names[$group_id][0]
                                  : $group_names[$group_id][0];
                } else {
                    $group_name = $group_names[$group_id];
                }
                ?>

            </td>

            <td class="blue_gradient" align="left" valign="middle"
                colspan="<?= $view == 'ext' ? 3 : 1 ?>">

                <a class="tree" <?= $_my_sem_open[$group_id] ? 'style="font-weight:bold"' : '' ?>
                   name="<?= $group_id ?>"
                   href="<?= URLHelper::getLink($_SERVER['PHP_SELF'] . '#' . $group_id, array('view' => $view, ($_my_sem_open[$group_id] ? 'close_my_sem' : 'open_my_sem' ) => $group_id)) ?>"
                   <?= tooltip(_("Gruppierung öffnen"), true) ?>>

                    <?= htmlReady($group_field == "sem_tree_id" ? $group_names[$group_id][0] : $group_names[$group_id]) ?>
                </a>

                <? if ($group_field == "sem_tree_id") { ?>
                    <br>
                    <span style="font-size:0.8em">
                        <sup><?= htmlReady($group_name) ?></sup>
                    </span>
                <? } ?>
            </td>

            <td class="blue_gradient" align= "right" valign="top" colspan="4" nowrap>
            <? if ($last_modified) { ?>
                <span style="font-size:0.8em">
                    <sup><?= _("letzte Änderung:") ?></sup>
                </span>

                <span style="color:red;font-size:0.8em">
                    <sup><?= strftime("%x, %H:%M", $last_modified) ?></sup>
                </span>
            <? } ?>
            </td>

        </tr>
    <?
    }

    if (isset($_my_sem_open[$group_id])) {
        echo $this->render_partial("meine_seminare/_course", compact("group_members"));
    }
}
