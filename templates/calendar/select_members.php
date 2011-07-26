<?
$users = Calendar::getUsers('WRITABLE');
$selected_users = Request::getArray('selected_users');
if (!sizeof($selected_users)) {
    $selected_users = array();
    while ($user_calendar = $_calendar->nextCalendar()) {
        $selected_users[] = $user_calendar->getUserName();
    }
    $selected_users[] = get_username();
}
?>
<span style="white-space:nowrap; font-size: small;">
    <select name="select_user[]" multiple="multiple" size="5">
        <option value="<?= htmlReady(get_username()) ?>"<?= (in_array(get_username(), $selected_users) ? ' selected="selected"' : '') ?>><?= _("Eigener Kalender") ?></option>
    <? foreach ($users as $user) : ?>
        <option value="<?= htmlReady($user['username']) ?>"<?= (in_array($user['username'], $selected_users) ? ' selected="selected"' : '') ?>><?= htmlReady(my_substr($user['name'] . " ({$user['username']})", 0, 30)) ?></option>
    <? endforeach ?>
    </select>
</span>
