<? use Studip\Button, Studip\LinkButton; ?>

<?
    function module_icon($area, $color = 'white')
    {
        $mapping = array(
            'documents'           => 'files',
            'elearning_interface' => 'learnmodule',
            'scm'                 => 'infopage',
            'votes'               => 'vote',
            'basic_data'          => 'seminar',
            'participants'        => 'persons',
        );
        return sprintf('icons/16/%s/%s.png', $color, $mapping[$area] ?: $area);
    }
?>

<form method="post" action="<?= $controller->url_for('settings/notification/store') ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">

    <table class="zebra-hover notification settings" id="main_content" style="width: 100%">
        <colgroup>
            <col width="7px">
            <col>
        <? for ($i = 0; $i < count($modules); $i += 1): ?>
            <col width="20px">
        <? endfor; ?>
        </colgroup>
        <thead>
            <tr>
                <th colspan="2" width="90%"><?= _('Veranstaltung') ?></th>
            <? foreach ($modules as $name => $data): ?>
                <th>
                    <?= Assets::img(module_icon($name), array('class' => 'middle', 'title' => $data['name'])) ?>
                </th>
            <? endforeach; ?>
                <th><?= _('Alle') ?></th>
            </tr>
            <tr>
                <td colspan="2" align="right">
                    <?= _('Benachrichtigung für alle aufgelisteten Veranstaltungen:') ?>
                </td>
            <? for ($i = 0; $i < count($modules); $i += 1): ?>
                <td>
                    <input type="checkbox" name="all[columns][]" value="<?= $i ?>"
                           <? if (count(array_filter($checked, function ($item) use ($i) { return $item[$i]; })) == count($checked)) echo 'checked'; ?>>
                </td>
            <? endfor; ?>
                <td>
                    <input type="checkbox" name="all[all]" value="all"
                           <? if (count(array_filter($checked, function ($item) { return $item['all']; })) == count($checked)) echo 'checked'; ?>>

                </td>
            </tr>
        </thead>
<? foreach ($groups as $id => $members): ?>
        <tbody>
        <? if ($group_field !== 'not_grouped'): ?>
            <tr class="divider">
                <td class="table_header_bold" valign="top" height="20" colspan="<?= 3 + count($modules) ?>">
                <? if (isset($open[$id])): ?>
                    <a class="tree" style="font-weight:bold" name="<?= $id ?>"
                       href="<?= $controller->url_for('settings/notification/close', $id) ?>#<?= $id ?>"
                       <?= tooltip(_('Gruppierung schließen'), true) ?>>
                       <?= Assets::img('icons/16/blue/arr_1down.png') ?>
                <? else: ?>
                    <a class="tree" name="<?= $id ?>"
                        href="<?= $controller->url_for('settings/notification/open', $id) ?>#<?= $id ?>"
                       <?= tooltip(_('Gruppierung öffnen'), true) ?>>
                       <?= Assets::img('icons/16/blue/arr_1right.png') ?>
                <? endif; ?>
                        <?= htmlReady(my_substr(implode(' &gt; ', (array)$group_names[$id]), 0, 70)) ?>
                    </a>
                </td>
            </tr>
        <? endif; ?>
    <? if (isset($open[$id])): ?>
        <? foreach ($members as $member): ?>
            <tr>
                <td class="gruppe<?= $seminars[$member['seminar_id']]['gruppe'] ?>">&nbsp;</td>
                <td>
                    <a href="seminar_main.php?auswahl=<?= $member['seminar_id'] ?>">
                        <?= htmlReady(my_substr($seminars[$member['seminar_id']]['name'], 0, 70)) ?>
                    </a>
                <? if (!$seminars[$member['seminar_id']]['visible']): ?>
                    <?= _('(versteckt)') ?>
                <? endif; ?>
                    <input type="hidden" name="m_checked[<?= $member['seminar_id'] ?>][33]" value="0">
                </td>
            <? foreach (array_values($modules) as $index => $data): ?>
                <td>
                    <input type="checkbox" name="m_checked[<?= $member['seminar_id'] ?>][<?= $index ?>]"
                           value="<?= pow(2, $data['id']) ?>"
                           <? if ($checked[$member['seminar_id']][$index]) echo 'checked'; ?>>
                </td>
            <? endforeach; ?>
                <td>
                    <input type="checkbox" name="all[rows][]" value="<?= $members['seminar_id'] ?>"
                           <? if (count(array_filter($checked[$member['seminar_id']])) == count($modules) + 1) echo 'checked'; ?>>
                </td>
            </tr>
        <? endforeach; ?>
    <? endif; ?>
        </tbody>
<? endforeach; ?>
        <tfoot>
            <tr>
                <td class="blank" align="center" colspan="<?= count($modules) + 3 ?>">
                    <?= Button::create(_('Übernehmen'), 'store', array('title' => _('Änderungen übernehmen'))) ?>
                    <?= LinkButton::create(_('Zurücksetzen'), $controller->url_for('settings/notification')) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
