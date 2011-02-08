<?
# Lifter010: TODO
?>
<table class="default">
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td><label for="sem_select"><?= _('Semester:') ?></label></td>
        <td>
            <select name="sem_select" id="sem_select" class="user_form">
                <option value="all"><?= _('alle') ?></option>
                <? foreach ($semester_data as $sem_value => $semester): ?>
                 <option value = "<?= $sem_value + 1 ?>" <?= ($sem_select == $sem_value + 1) ? 'selected="selected"' : '' ?>>
                     <?= $semester['name'] ?>
                 </option>
                <? endforeach; ?>
            </select>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td><label for="sem_search"><?= _('Veranstaltung:') ?></label></td>
        <td><input type="text" name="sem_search" value="<?= $sem_search ?>" id="sem_search" class="user_form"></td>
    </tr>
    <tr>
        <td colspan="2" align="center">
            <?= makeButton('suchen', 'input', _('suchen'), 'suchen') ?>
        </td>
    </tr>
</table>
<br>