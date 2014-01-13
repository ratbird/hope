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
			<?php foreach ($priocourses as $prio => $course): ?>
			    <?php $prios[$course->id] = htmlReady($course->name) ?>
			    <?php $visible = !isset($user_prio[$course->id]); ?>
    			<li class="<?= htmlReady($course->id) ?> <?= $visible ? 'visible' : '' ?>" <?= !$visible ? 'style="display:none"' : '' ?>><input type="checkbox" class="hidden-js" value="<?= $prio ?>" name="admission_prio[<?= $course->id ?>]"><?= htmlReady($course->name) ?></li>
			<?php endforeach; ?>
                    </ul>
                </td>
                <td align="center">

		    <?= Assets::input('icons/16/yellow/arr_2right', array('type' => 'submit', 'class' => 'hidden-js')) ?>


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
				<li class="<?= $id ?>"><?= $prios[$id] ?><input type="hidden" value="<?= $prio ?>" name="admission_prio[<?= $id ?>]"> <?= Assets::img('icons/16/black/trash', array('class' => $id . ' delete hidden-no-js')) ?>
				    <a class="hidden-js" href="<?= $controller->link_for('/delete/' . $id) ?>">
					<?= Assets::img('icons/16/black/trash', array('class' => $id . ' delete')) ?>
				    </a>

				    <?php if ($prio != 0): ?>

	    			    <a class="hidden-js" href="<?= $controller->link_for('/order_up/' . $id) ?>">
					    <?= Assets::img('icons/16/yellow/arr_1up', array('class' => ' delete')) ?>
	    			    </a>
				    <?php endif; ?>
				    <?php if ($prio != count($user_prio) - 1): ?>

	    			    <a class="hidden-js" href="<?= $controller->link_for('/order_down/' . $id) ?>">
					    <?= Assets::img('icons/16/yellow/arr_1down', array('class' => ' delete')) ?>
	    			    </a>
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