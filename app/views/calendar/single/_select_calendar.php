<form name="select_calendars" method="post" action="<?= $action_url ?>">
    <table width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td>
                <span style="font-size: small; color: #555555;">
                    <?= _("Kalender:") ?>
                </span>
                <select style="font-size: small;" name="range_id" onChange="document.select_calendars.submit();">
                    <option value="user.<?= get_username() ?>"<?= (get_userid() == $calendar_id ? ' selected="selected"' : '') ?>>
                            <?= _("Eigener Kalender") ?>
                    </option>
                    <? $groups = Calendar::getGroups($GLOBALS['user']->id); ?>
                    <? if (sizeof($groups)) : ?>
                        <option style="font-weight:bold;" value="<?= $GLOBALS['user']->id ?>"><?= _("Gruppenkalender:") ?></option>
                        <? foreach ($groups as $group) : ?>
                        <option value="<?= $group['id'] ?>"<?= ($range_id == $group['id'] ? ' selected="selected"' : '') ?>>
                             &nbsp; &nbsp;<?= htmlReady(my_substr($group['name'], 0, 30)) ?>
                        </option>
                        <? endforeach ?>
                    <? endif; ?>
                    <? $users = Calendar::getUsers($GLOBALS['user']->id); ?>
                    <? if (sizeof($users)) : ?>
                        <option style="font-weight:bold;" value="<?= $GLOBALS['user']->id ?>"><?= _("Einzelkalender:") ?></option>
                        <? foreach ($users as $user) : ?>
                        <option value="<?= $user['id'] ?>"<?= ($range_id == $user['id'] ? ' selected="selected"' : '') ?>>
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
                        <option style="font-weight:bold;" value="<?= $GLOBALS['user']->id ?>"><?= _("Dozentenkalender:") ?></option>
                        <? foreach ($lecturers as $lecturer) : ?>
                        <option value="<?= $lecturer['id'] ?>"<?= ($range_id == $lecturer['id'] ? ' selected="selected"' : '') ?>>
                            &nbsp; &nbsp;<?= htmlReady(my_substr($lecturer['name'] . " ({$lecturer['username']})", 0, 30)) ?>
                        </option>
                        <? endforeach ?>
                    <? endif ?>
                    <? if (get_config('COURSE_CALENDAR_ENABLE')) : ?>
                        <? $sems = Calendar::GetSeminarActivatedCalendar($GLOBALS['user']->id); ?>
                        <? if (sizeof($sems)) : ?>
                            <option style="font-weight:bold;" value="<?= $GLOBALS['user']->id ?>"><?= _("Veranstaltungskalender:") ?></option>
                            <? foreach ($sems as $sem_id => $sem_name) : ?>
                            <option value="<?= $sem_id ?>"<?= ($range_id == $sem_id ? ' selected="selected"' : '') ?>>
                                &nbsp; &nbsp;<?= htmlReady(my_substr($sem_name, 0, 30)) ?>
                            </option>
                            <? endforeach ?>
                        <? endif ?>
                        <? $insts = Calendar::GetInstituteActivatedCalendar($GLOBALS['user']->id); ?>
                        <? if (sizeof($insts)) : ?>
                            <option style="font-weight:bold;" value="<?= $GLOBALS['user']->id ?>"><?= _("Einrichtungskalender:") ?></option>
                            <? foreach ($insts as $inst_id => $inst_name) : ?>
                            <option value="<?= $inst_id ?>"<?= ($range_id == $inst_id ? ' selected="selected"' : '') ?>>
                                &nbsp; &nbsp;<?= htmlReady(my_substr($inst_name, 0, 30)); ?>
                            </option>
                            <? endforeach ?>
                        <? endif ?>
                    <? endif ?>
                </select>
                <input type="hidden" name="view" value="<?= $view ?>">
                <span style="font-size: small; color: #555555; white-space: nowrap;">
                    <input type="image" src="<?= Assets::image_path('icons/16/blue/accept.png') ?>" border="0" class="text-top">
                </span>
            </td>
        </tr>
    </table>
</form>
