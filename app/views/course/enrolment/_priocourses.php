<div id="enrollment">
    <label for="admission_user_limit"><?= _("Ich möchte folgende Anzahl an Veranstaltungen belegen:") ?></label>
    <select name="admission_user_limit">
        <? foreach (range(1, $max_limit) as $max) : ?>
            <option <?= $user_max_limit == $max ? 'selected' : '' ?>>
                <?= $max ?>
            </option>
        <? endforeach ?>
    </select>
    <table width="100%">
        <tbody>
            <tr>
                <td colspan="3">
                    <span class="hidden-no-js">
                        <?= _('Ziehen Sie die in Frage kommenden Veranstaltungen auf die rechte Seite
und ordnen Sie sie dort in der Reihenfolge der von Ihnen gewünschten
Priorität an. Sie können mehr Veranstaltungen nach rechts ziehen als Sie
tatsächlich belegen wollen') ?>
                    </span>
                    <span class="hidden-js">
                        <?= _('Sortieren Sie die in Frage kommenden Veranstaltungen auf die rechte Seite
und ordnen Sie sie dort in der Reihenfolge der von Ihnen gewünschten
Priorität an. Sie können mehr Veranstaltungen nach rechts zuweisen als Sie
tatsächlich belegen wollen') ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <h3> <?= _("Verfügbare Veranstaltungen") ?></h3>
                    <input type="text" class="hidden-no-js" name="filter" placeholder="<?= _('Filter') ?>">

                    <ul id="avaliable-courses">
                        <?php $prios = array(); ?>
                        <?php foreach ($priocourses as $prio => $course): ?>
                            <?php
                            $name = $course->name;
                            if (get_config('IMPORTANT_SEMNUMBER')) {
                                $name = $course->veranstaltungsnummer.' '.$name;
                            }
                            $prios[$course->id]['name'] = htmlReady(my_substr($name, 0, 50));
                            $tooltxt                    = array();
                            $tooltxt[]                  = $course->veranstaltungsnummer;
                            $tooltxt[]                  = $course->name;
                            $tooltxt[]                  = join(', ',
                                    $course->members->findBy('status', 'dozent')->orderBy('position')->limit(3)->pluck('Nachname'));
                            $tooltxt[]                  = join('; ',
                                    $course->cycles->toString());
                            $prios[$course->id]['info'] = tooltipicon(join("\n",
                                            $tooltxt));
                            ?>
                            <?php $visible                    = !isset($user_prio[$course->id]); ?>
                            <li class="<?= htmlReady($course->id) ?> <?= $visible ? 'visible' : '' ?>" <?= !$visible ? 'style="display:none"' : '' ?>>
                                <input type="checkbox" class="hidden-js" value="0" name="admission_prio[<?= $course->id ?>]">
                                <?= $prios[$course->id]['name'] . '&nbsp;' . $prios[$course->id]['info'] ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </td>
                <td align="center">

                    <?=
                    Assets::input('icons/16/yellow/arr_2right',
                            array('type' => 'submit', 'class' => 'hidden-js'))
                    ?>


                </td>
                <td  valign="top">
                    <h3><?= _("Ausgewählte Veranstaltungen") ?></h3>
                    <input type="text" class="hidden-no-js" name="filter" placeholder="<?= _('Filter') ?>">
                    <ul id="selected-courses">
<?php $hasUserPrios = count($user_prio) > 0 ?>

                        <li class="empty" <?= $hasUserPrios ? 'style="display:none"' : '' ?>><?= _('Gewünschte Veranstaltungen hierhin ziehen') ?></li>
                        <?php
                        asort($user_prio);

                        if ($hasUserPrios):
                            foreach ($user_prio as $id => $prio):
                                ?>
                                <li class="<?= $id ?>">
                                    <?= $prios[$id]['name'] . '&nbsp;' . $prios[$id]['info'] ?>
                                    <input type="hidden" value="<?= $prio ?>" name="admission_prio[<?= $id ?>]"> <?=
                                    Assets::img('icons/16/black/trash',
                                            array('class' => $id . ' delete hidden-no-js'))
                                    ?>
                                    <?=
                                    Assets::input('icons/16/black/trash',
                                            array('name' => 'admission_prio_delete[' . $id . ']', 'type' => 'submit', 'class' => 'hidden-js delete'))
                                    ?>

                                    <?php if ($prio != 1): ?>
                                        <?=
                                        Assets::input('icons/16/yellow/arr_1up',
                                                array('name' => 'admission_prio_order_up[' . $id . ']', 'type' => 'submit', 'class' => 'hidden-js delete'))
                                        ?>
                                    <?php endif; ?>
                                    <?php if ($prio != count($user_prio)): ?>
                                    <?=
                                    Assets::input('icons/16/yellow/arr_1down',
                                            array('name' => 'admission_prio_order_down[' . $id . ']', 'type' => 'submit', 'class' => 'hidden-js delete'))
                                    ?>
                                <?php endif; ?>
                                </li>
        <?php
    endforeach;
endif;
?>
                    </ul>
                </td>
            </tr>
        </tbody>
    </table>
    <div class="icons" style="display: none">
<?= Assets::img('icons/16/black/trash', array('class' => 'delete')) ?>
    </div>





</div>
