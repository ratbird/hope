<? 
use Studip\Button;

$groupables = array(
    'sem_number'  => _('Semester'),
    'sem_tree_id' => _('Studienbereich'),
    'sem_status'  => _('Typ'),
    'gruppe'      => _('Farbgruppen'),
    'dozent_id'   => _('Dozenten'),
)
?>

<p>
    <?= _('Hier können Sie Ihre Veranstaltungen in Farbgruppen einordnen und '
         .'eine Gliederung nach Kategorien festlegen. <br>'
         .'Die Darstellung unter <b>meine Veranstaltungen</b> wird entsprechend '
         .'den Gruppen sortiert bzw. entsprechend der gewählten Kategorie gegliedert.') ?>
</p>

    <form method="post" action="<?= URLHelper::getLink('meine_seminare.php') ?>">
        <?= CSRFProtection::tokenTag() ?>
        <table class="default zebra-hover">
            <colgroup>
                <col>
            <? for ($i = 0; $i < 9; $i++): ?>
                <col width="28px">
            <? endfor; ?>
            </colgroup>
            <tr>
                <td class="blank" align="right">
                    <label>
                        <?= _('Kategorie zur Gliederung:') ?>
                        <select name="select_group_field">
                        <? if ($no_grouping_allowed): ?>
                            <option value="not_grouped" <? if ($group_field == 'not_grouped') echo 'selected'; ?>>
                                <?= _('keine Gliederung') ?>
                            </option>
                        <? endif; ?>
                        <? foreach ($groupables as $key => $label): ?>
                            <option value="<?= $key ?>" <? if ($group_field == $key) echo 'selected'; ?>>
                                <?= $label ?>
                            </option>
                        <? endforeach; ?>
                        </select>
                    </label>
                </td>
                <td class="blank" align="center" colspan="9">
                    <?= Button::createAccept() ?>
                </td>
            </tr>
            <tr>
                <td class="blank" align="right" colspan="10">&nbsp;</td>
            </tr>
            <tr valign="top" align="center">
                <th style="text-align: center;">
                    &nbsp;<br>
                    <?= _('Veranstaltung') ?>
                </th>
            <? for ($i = 0; $i < 9; $i++): ?>
                <th class="gruppe<?= $i ?>">&nbsp;</th>
            <? endfor; ?>
            </tr>
<? foreach ($groups as $group_id => $group_members): ?>
        <? if ($group_field != 'not_grouped'): ?>
            <tr>
                <td class="blank" colspan="10">&nbsp;</td>
            </tr>
            <tr>
                <td class="blue_gradient" valign="middle" height="20" colspan="10">
                <? if (isset($_my_sem_open[$group_id])): ?>
                    <a class="tree" style="font-weight:bold" name="<?= $group_id ?>" href="<?= URLHelper::getLink('?close_my_sem=' . $group_id . '#' .$group_id) ?>" <?= tooltip(_('Gruppierung schließen'), true) ?>>
                        <?= Assets::img('icons/16/blue/arr_1down') ?>
                <? else: ?>
                    <a class="tree" name="<?= $group_id ?>" href="<?= URLHelper::getLink('?open_my_sem=' . $group_id . '#' .$group_id ) ?>" <?= tooltip(_('Gruppierung öffnen'), true) ?>>
                         <?= Assets::img('icons/16/blue/arr_1right') ?>
                <? endif; ?>
                <? if (is_array($group_names[$group_id])): ?>
                    <?= htmlReady(my_substr($group_names[$group_id][1] . ' > ' . $group_names[$group_id][0], 0, 70)) ?>
                <? else: ?>
                    <?= htmlReady(my_substr($group_names[$group_id], 0, 70)) ?>
                <? endif; ?>
                     </a>
                </td>
            </tr>
        <? endif; ?>
    <? if (isset($_my_sem_open[$group_id])): ?>
        <? foreach ($group_members as $member): ?>
            <tr>
                <td>
                    <a href="<?= URLHelper::getLink('seminar_main.php?auswahl=' . $member['seminar_id']) ?>">
                        <?= htmlReady(my_substr($my_sem[$member['seminar_id']]['name'], 0, 70)) ?>
                    </a>
                <? if (!$my_sem[$member['seminar_id']]['visible']): ?>
                    <?= _('(versteckt)') ?>
                <? endif; ?>
                </td>
            <? for ($i = 0; $i < 9; $i++): ?>
                <td class="gruppe<?= $i ?>">
                    <input type="radio" name="gruppe[<?= $member['seminar_id'] ?>]" value="<?= $i ?>"
                           <? if ($my_sem[$member['seminar_id']]['gruppe'] == $i) echo 'checked'; ?>>
                </td>
            <? endfor; ?>
            </tr>
        <? endforeach; ?>
    <? endif; ?>
<? endforeach; ?>
            <tr>
                <td class="blank">&nbsp;</td>
                <td class="blank" align="center" colspan="9">
                    <br>
                    <?= Button::createAccept() ?>
                    <input type="hidden" name="gruppesent" value="1">
                    <br>
                </td>
            </tr>
        </table>
    </form>
