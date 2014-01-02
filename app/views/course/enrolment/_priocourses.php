<div id="enrollment">
    <label for="admission_user_limit"><?= _("Ich m�chte folgende Anzahl an Veranstaltungen belegen:") ?></label>
    <select name="admission_user_limit">
        <? foreach(range(1, $max_limit) as $max) : ?>
        <option <?= $user_max_limit == $max ? 'selected' : '' ?>>
            <?= $max ?>
        </option>
        <? endforeach ?>
    </select>
    <table width="100%">
        <tbody>
            <tr>
                <td valign="top">    
                    <h2> <?= _("Verf�gbare Veranstaltungen") ?></h2>
                    <ul id="avaliable-courses">
                        <?php $prios = array(); ?>
                        <?php foreach ($priocourses as $course): ?>
                            <?php $prios[$course->id] = htmlReady($course->name) ?>
                        <?php $visible = !isset($user_prio[$course->id]);?>
                            <li class="<?= htmlReady($course->id) ?> <?=$visible?'visible':'' ?>" <?= !$visible ? 'style="display:none"' : '' ?>><?= htmlReady($course->name) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </td>
                <td>

                </td>
                <td  valign="top">    
                    <h2><?= _("Ausgew�hlte Veranstaltungen") ?></h2>
                    <ul id="selected-courses">
                        <?php $hasUserPrios = count($user_prio) > 0 ?>

                        <li class="empty" <?= $hasUserPrios ? 'style="display:none"' : '' ?>><?= _('Verf�gbare Veranstaltungen hierhin droppen') ?></li>
                        <?php
                        asort($user_prio);
                        if ($hasUserPrios):
                            foreach ($user_prio as $id => $prio):
                                ?>
                                <li class="<?= $id ?>"><?= $prios[$id] ?><input type="hidden" value="<?= $prio ?>" name="admission_prio[<?= $id ?>]"> <?= Assets::img('icons/16/black/trash',array('class'=>$id.' delete'))?></li>
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
         <?= Assets::img('icons/16/black/trash',array('class'=>'delete'))?>
    </div>
   




</div>