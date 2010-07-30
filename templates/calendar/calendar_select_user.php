<!-- CALENDAR SELECT CALENDAR USERS -->
<select name="select_user[]" multiple="multiple" size="5">
<option value="<?= $GLOBALS['auth']->auth['uname'] ?>"<? if (in_array($GLOBALS['auth']->auth['uname'], $selected_users)) : echo ' selected'; endif ?>><?= _("Eigener Kalender") ?></option>
<? foreach ($users as $user) : ?>
    <option value="<?= $user['username']; ?>"<? if (in_array($user['username'], $selected_users)) : echo ' selected'; endif ?>><?= htmlReady(my_substr($user['name'] . " ({$user['username']})", 0, 30)); ?></option>
<? endforeach ?>
</select>
<!-- END CALENDAR SELECT CALENDAR USERS -->