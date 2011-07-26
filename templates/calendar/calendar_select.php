<!-- CALENDAR SELECT CALENDAR -->
<form name="cal_select_calendars" method="post" action="<?= "?cmd=$cmd&atime=$atime" ?>">
<table cellspacing="0" cellpadding="0" border="0"><tr><td align="right">
<span style="font-size: small; color: #555555;"><?= _("Kalender ausw&auml;hlen:") ?>&nbsp;</span>
<select style="font-size: small;" name="cal_select" onChange="document.cal_select_calendars.submit();">
<option value="user.{$auth->auth['uname']}"<? if ($GLOBALS['auth']->auth['uid'] == $selected_id) : echo ' selected'; endif ?>><?= _("Eigener Kalender") ?></option>
<? if (sizeof($groups)) : ?>
    <option style="font-weight:bold;" value="user.<?= $GLOBALS['auth']->auth['uname'] ?>"><?= _("Gruppenkalender:") ?></option>
    <? foreach ($groups as $group) : ?>
        <option value="group.<?= $group['id'] ?>"<? if ($selected_id == $group['id']) : echo ' selected'; endif ?>> &nbsp; &nbsp;<?= htmlReady(my_substr($group['name'], 0, 30)); ?></option>
    <? endforeach ?>
<? endif ?>
<? if (sizeof($users)) : ?>
    <option style="font-weight:bold;" value="user.<?= $GLOBALS['auth']->auth['uname'] ?>"><?= _("Einzelkalender:") ?></option>
    <? foreach ($users as $user) : ?>
        <option value="user.<?= $user['username'] ?>"<? if ($selected_id == $user['id']) : echo ' selected'; endif ?>> &nbsp; &nbsp;<?= htmlReady(my_substr($user['name'] . " ({$user['username']})", 0, 30)); ?></option>
    <? endforeach ?>
<? endif ?>
<? /*
<? if (sizeof($lecturers)) : ?>
    <option style="font-weight:bold;" value="user.<?= $GLOBALS['auth']->auth['uname'] ?>"><?= _("Dozentenkalender:") ?></option>
    <? foreach ($lecturers as $lecturer) : ?>
        <option value="user.<?= $lecturer['username'] ?>"<? if ($selected_id == $lecturer['id']) : echo ' selected'; endif ?>> &nbsp; &nbsp;<?= htmlReady(my_substr($lecturer['name'] . " ({$lecturer['username']})", 0, 30)); ?></option>
    <? endforeach ?>
<? endif ?>
<? if (sizeof($sems)) : ?>
    <option style="font-weight:bold;" value="user.<?= $GLOBALS['auth']->auth['uname'] ?>"><?= _("Veranstaltungskalender:") ?></option>
    <? foreach ($sems as $sem_id => $sem_name) : ?>
        <option value="sem.<?= $sem_id ?>"<? if ($selected_id == $sem_id) : echo ' selected'; endif ?>> &nbsp; &nbsp;<?= htmlReady(my_substr($sem_name, 0, 30)); ?></option>
    <? endforeach ?>
<? endif ?>
<? if (sizeof($insts)) : ?>
    <option style="font-weight:bold;" value="user.<? $GLOBALS['auth']->auth['uname'] ?>"><?= _("Einrichtungskalender:") ?></option>
    <? foreach ($insts as $inst_id => $inst_name) : ?>
        <option value="inst.<?= $inst_id ?>"<? if ($selected_id == $inst_id) : echo ' selected'; endif ?>> &nbsp; &nbsp;<?= htmlReady(my_substr($inst_name, 0, 30)); ?></option>
    <? endforeach ?>
<? endif ?>
*/ ?>
</select>
<span style="font-size: small; color: #555555; white-space: nowrap;">
<? if (get_object_type($selected_id) == 'user' || get_object_type($selected_id) == 'group') : ?>
    <?= _("Projekttermine anzeigen") ?>
    <input type="checkbox" name="show_project_events" value="1" onChange="document.cal_select_calendars.submit();"<? if ($calendar_sess_control_data['show_project_events']) : echo 'checked'; endif ?>>
<? else : ?>
    <? if ($calendar_sess_control_data['show_project_events']) : ?>
        <input type="hidden" name="show_project_events" value="1">
    <? endif ?>
<? endif ?>
&nbsp;<input type="image" src="<?= $GLOBALS['ASSETS_URL'] ?>images/GruenerHakenButton.png" border="0" style="vertical-align: bottom;">
</span>
</td></tr>
</table>
</form>
<!-- END CALENDAR SELECT CALENDAR -->