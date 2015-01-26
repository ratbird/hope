<form name="select_calendars" method="post" action="<?= $action_url ?>">
    <table width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td>
                <span style="font-size: small; color: #555555;">
                    <?= _('Kalender:') ?>
                </span>
                <select style="font-size: small; width: 12em;" name="range_id" onChange="document.select_calendars.submit();">
                    <option value="user.<?= get_username() ?>"<?= (get_userid() == $calendar_id ? ' selected' : '') ?>>
                            <?= _("Eigener Kalender") ?>
                    </option>
                    <? $groups = Calendar::getGroups($GLOBALS['user']->id); ?>
                    <? if (sizeof($groups)) : ?>
                        <optgroup style="font-weight:bold;" label="<?= _('Gruppenkalender:') ?>">
                        <? foreach ($groups as $group) : ?>
                            <option value="<?= $group->getId() ?>"<?= ($range_id == $group->getId() ? ' selected' : '') ?>>
                                 <?= htmlReady($group->name) ?>
                            </option>
                        <? endforeach ?>
                        </optgroup>
                    <? endif; ?>
                    <? $calendar_users = CalendarUser::getOwners($GLOBALS['user']->id); ?>
                    <? if (sizeof($calendar_users)) : ?>
                        <optgroup style="font-weight:bold;" label="<?= _('Einzelkalender:') ?>">
                        <? foreach ($calendar_users as $calendar_user) : ?>
                            <option value="<?= $calendar_user->owner_id ?>"<?= ($range_id == $calendar_user->owner_id ? ' selected' : '') ?>>
                                <?= htmlReady($calendar_user->owner->getFullname()) ?>
                            </option>
                        <? endforeach ?>
                        </optgroup>
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
                        <optgroup style="font-weight:bold;" label="<?= _('Dozentenkalender:') ?>">
                        <? foreach ($lecturers as $lecturer) : ?>
                            <option value="<?= $lecturer['id'] ?>"<?= ($range_id == $lecturer['id'] ? ' selected' : '') ?>>
                                <?= htmlReady(my_substr($lecturer['name'] . " ({$lecturer['username']})", 0, 30)) ?>
                            </option>
                        <? endforeach ?>
                        </optgroup>
                    <? endif ?>
                    <? if (get_config('COURSE_CALENDAR_ENABLE')) : ?>
                        <? $courses = Calendar::GetCoursesActivatedCalendar($GLOBALS['user']->id); ?>
                        <? if (sizeof($courses)) : ?>
                            <optgroup style="font-weight:bold;" label="<?= _('Veranstaltungskalender:') ?>">
                            <? foreach ($courses as $course) : ?>
                                <option value="<?= $course->id ?>"<?= ($range_id == $course->id ? ' selected' : '') ?>>
                                    <?= htmlReady($course->getFullname()) ?>
                                </option>
                            <? endforeach ?>
                            </optgroup>
                        <? endif ?>
                        <? $insts = Calendar::GetInstituteActivatedCalendar($GLOBALS['user']->id); ?>
                        <? if (sizeof($insts)) : ?>
                            <optgroup style="font-weight:bold;" label="<?= _('Einrichtungskalender:') ?>">
                            <? foreach ($insts as $inst_id => $inst_name) : ?>
                                <option value="<?= $inst_id ?>"<?= ($range_id == $inst_id ? ' selected' : '') ?>>
                                    <?= htmlReady(my_substr($inst_name, 0, 30)); ?>
                                </option>
                            <? endforeach ?>
                            </optgroup>
                        <? endif ?>
                    <? endif ?>
                </select>
                <input type="hidden" name="view" value="<?= $view ?>">
                <span style="font-size: small; color: #555555; white-space: nowrap;">
                    <input type="image" src="<?= Assets::image_path('icons/16/blue/accept.png') ?>" class="text-top">
                </span>
            </td>
        </tr>
    </table>
</form>
