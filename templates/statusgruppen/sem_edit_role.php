<?
# Lifter010: TODO
    use Studip\Button;
?>
<form action="<?= URLHelper::getLink() ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <? if (!$edit_role) : ?>
    <?= _("Vorlagen") ?>: <select name="presetName">
        <?
        if (isset($GLOBALS['SEM_STATUS_GROUPS'][$seminar_class])) :   // wir sind in einer Veranstaltung die Presets hat
            $key = $seminar_class;
        else:
            $key = "default";
        endif;

        for ($i = 0; $i < sizeof($GLOBALS['SEM_STATUS_GROUPS'][$key]); $i++) : ?>
        <option><?= $GLOBALS['SEM_STATUS_GROUPS'][$key][$i] ?></option>
        <? endfor; ?>
    </select>
    <span style="padding: 0px 5px 0px 5px">
        <input type="image" name="choosePreset" value="choosePreset" src="<?= Assets::image_path('icons/16/yellow/arr_2right.png') ?>" title="<?= _("in Namensfeld übernehmen") ?>">
    </span>
    <? endif; ?>

    <label for="role_name">
        <?= ($edit_role) ? _("neuer") :'' ?> <?= _("Gruppenname") ?>:
    </label>
    <input id="role_name" type="text" name="new_name" value="<?= ($role_data['name']) ? htmlReady($role_data['name']) : '' ?>">
    <br>

    <label for="role_size">
        <?= ($edit_role) ? _("neue") : '' ?> <?= _("Gruppengröße") ?>:
    </label>
    <input id="role_size" type="text" name="new_size" size="3" value="<?= ($role_data['size']) ? $role_data['size'] : '' ?>">

    <label for="self_assign">
        <?= _("Selbsteintrag") ?>
    </label>
    <input id="self_assign" type="checkbox" name="new_selfassign" <?= ($role_data['selfassign']) ? 'checked="checked"' : '' ?> style="vertical-align: middle;">

    <? if ($role_data['folder']) : ?>
        <?= _("Dateiordner vorhanden") ?>
    <? else: ?>
    <label for="group_folder">
        <?= _("Dateiordner") ?>
    </label>
    <input id="group_folder" type="checkbox" name="groupfolder" style="vertical-align: middle;">
    <? endif;?>

    <input type="hidden" name="cmd" value="<?= ($edit_role) ? 'doEditRole' : 'addRole' ?>">
    <? if ($edit_role) :?>
    <?= Button::createAccept(_('Speichern'), 'speichern'); ?>
    <? else: ?>
    <?= Button::create(_('Eintragen'), 'eintragen'); ?>
    <? endif; ?>

    <input type="hidden" name="role_id" value="<?= $role_data['id'] ?>">
</form>
