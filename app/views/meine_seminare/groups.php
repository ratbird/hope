<? 
use Studip\Button;
?>

<form method="post" action="<?= URLHelper::getLink('meine_seminare.php') ?>">
    <?= CSRFProtection::tokenTag() ?>
    
        <table class="default">
            <caption><?= _('Gruppenzuordnung') ?></caption>
            <thead>
                <tr>
                    <th><?= _('Veranstaltung') ?></th>
                    <th colspan="100%"><?= _('Gruppen/Farbe') ?></th>
                </tr>
            </thead>
            <tbody>
<? foreach ($groups as $group_id => $group_members): ?>
    <? if ($group_field !== 'not_grouped'): ?>
        
            <tr>
                <th  colspan='100%'>
                <? if (isset($_my_sem_open[$group_id])): ?>
                    <a class="tree" style="font-weight:bold" name="<?= $group_id ?>" href="<?= $controller->url_for('meine_seminare/groups?close_my_sem=' . $group_id . '#' .$group_id) ?>">
                        <?= Assets::img('icons/16/blue/arr_1down', tooltip2(_('Gruppierung schließen'))) ?>
                <? else: ?>
                    <a class="tree" name="<?= $group_id ?>" href="<?= $controller->url_for('meine_seminare/groups?open_my_sem=' . $group_id . '#' .$group_id ) ?>">
                         <?= Assets::img('icons/16/blue/arr_1right', tooltip2(_('Gruppierung öffnen'))) ?>
                <? endif; ?>
                <? if (is_array($group_names[$group_id])): ?>
                    <?= htmlReady(my_substr($group_names[$group_id][1] . ' > ' . $group_names[$group_id][0], 0, 70)) ?>
                <? else: ?>
                    <?= htmlReady(my_substr($group_names[$group_id], 0, 70)) ?>
                <? endif; ?>
                     </a>
                </th>
            </tr>
    <? endif; ?>
<? if ($group_id === 'not_grouped' || isset($_my_sem_open[$group_id])): ?>
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
            <td class="gruppe<?= $i ?>" width="28">
                <input type="radio" name="gruppe[<?= $member['seminar_id'] ?>]" value="<?= $i ?>"
                       aria-label="<?= _('Zugeordnet zu Gruppe ') . $i ?>"
                       <? if ($my_sem[$member['seminar_id']]['gruppe'] == $i) echo 'checked'; ?>>
            </td>
        <? endfor; ?>
        </tr>
    <? endforeach; ?>
<? endif; ?>
<? endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan='100%'>
        <?= Button::createAccept(_('Speichern')) ?>
        <input type="hidden" name="gruppesent" value="1">
        </td></tr>
        </tfoot>
        </table>
</form>
