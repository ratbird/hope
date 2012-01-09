<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<table class="default">
    <tbody>
        <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
            <td><label for="sem_select"><?= _('Semester:') ?></label></td>
            <td>
            <?=SemesterData::GetSemesterSelector(array('name' => 'sem_select', 'id' => 'sem_select', 'class' => 'user_form'), $sem_select, 'key', true)?>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
            <td><label for="sem_search"><?= _('Veranstaltung:') ?></label></td>
            <td>
                <input type="text" name="sem_search" value="<?= htmlReady($sem_search) ?>" id="sem_search" class="user_form" required>
            </td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" align="center">
                <?= Button::create(_('suchen'),'suchen')?>
            </td>
        </tr>
    </tfoot>
</table>
<br>