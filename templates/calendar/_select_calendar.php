<form name="cal_select_calendars" method="post" action="<?= URLHelper::getLink('', array('cmd' => $cmd, 'atime' => $atime)) ?>">
    <table cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="right">
                <span style="font-size: small; color: #555555;">
                    <?= _("Kalender auswählen:") ?>
                </span>
                <select style="font-size: small;" name="cal_select" onChange="document.cal_select_calendars.submit();">
                    <option value="user.<?= get_username() ?>"<?= (get_userid() == $_calendar->getId() ? ' selected="selected"' : '') ?>>
                            <?= _("Eigener Kalender") ?>
                    </option>
                    <? $groups = Calendar::getGroups(); ?>
                    <? if (sizeof($groups)) : ?>
                        <option style="font-weight:bold;" value="user.<?= get_username ?>"><?= _("Gruppenkalender:") ?></option>
                        <? foreach ($groups as $group) : ?>
                        <option value="group.<?= $group['id'] ?>"<?= ($_calendar->getId() == $group['id'] ? ' selected="selected"' : '') ?>>
                             &nbsp; &nbsp;<?= htmlReady(my_substr($group['name'], 0, 30)) ?>
                        </option>
                        <? endforeach ?>
                    <? endif; ?>
                    <? $users = Calendar::getUsers(); ?>
                    <? if (sizeof($users)) : ?>
                        <option style="font-weight:bold;" value="user.<?= get_username() ?>"><?= _("Einzelkalender:") ?></option>
                        <? foreach ($users as $user) : ?>
                        <option value="user.<?= $user['username'] ?>"<?= ($_calendar->getId() == $user['id'] ? ' selected="selected"' : '') ?>>
                            &nbsp; &nbsp;<?= htmlReady(my_substr($user['name'] . " ({$user['username']})", 0, 30)) ?>
                        </option>
                        <? endforeach ?>
                    <? endif ?>
                    <?/*
                        if ($GLOBALS['perm']->have_perm('dozent')) {
                            $lecturers = Calendar::GetLecturers();
                        } else {
                            $lecturers = array();
                        }
                        */
                        $lecturers = array();
                    ?>
                    <? if (sizeof($lecturers)) : ?>
                        <option style="font-weight:bold;" value="user.<?= get_username() ?>"><?= _("Dozentenkalender:") ?></option>
                        <? foreach ($lecturers as $lecturer) : ?>
                        <option value="user.<?= $lecturer['username'] ?>"<?= ($_calendar->getId() == $lecturer['id'] ? ' selected="selected"' : '') ?>>
                            &nbsp; &nbsp;<?= htmlReady(my_substr($lecturer['name'] . " ({$lecturer['username']})", 0, 30)) ?>
                        </option>
                        <? endforeach ?>
                    <? endif ?>
                    <? $sems = Calendar::GetSeminarActivatedCalendar(); ?>
                    <? if (sizeof($sems)) : ?>
                        <option style="font-weight:bold;" value="user.<?= get_username() ?>"><?= _("Veranstaltungskalender:") ?></option>
                        <? foreach ($sems as $sem_id => $sem_name) : ?>
                        <option value="sem.<?= $sem_id ?>"<?= ($_calendar->getId() == $sem_id ? ' selected="selected"' : '') ?>>
                            &nbsp; &nbsp;<?= htmlReady(my_substr($sem_name, 0, 30)) ?>
                        </option>
                        <? endforeach ?>
                    <? endif ?>
                    <? $insts = Calendar::GetInstituteActivatedCalendar(); ?>
                    <? if (sizeof($insts)) : ?>
                        <option style="font-weight:bold;" value="user.<?= get_username() ?>"><?= _("Einrichtungskalender:") ?></option>
                        <? foreach ($insts as $inst_id => $inst_name) : ?>
                        <option value="inst.<?= $inst_id ?>"<?= ($_calendar->getId() == $inst_id ? ' selected="selected"' : '') ?>>
                            &nbsp; &nbsp;<?= htmlReady(my_substr($inst_name, 0, 30)); ?>
                        </option>
                        <? endforeach ?>
                    <? endif ?>
                </select>
                <span style="font-size: small; color: #555555; white-space: nowrap;">
                    <input type="image" src="<?= Assets::image_path('icons/16/blue/accept.png') ?>" border="0" class="text-top">
                </span>
            </td>
        </tr>
    </table>
</form>
