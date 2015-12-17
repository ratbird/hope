<? if ($active): ?>
<a name="anker"></a>
<? endif; ?>
<table class="default">
    <colgroup>
        <col width="1%">
        <col width="90%">
        <col width="9%">
    </colgroup>
    <thead>
        <tr>
            <th>
                <a href="<?= URLHelper::getLink('?toggle_group=non_members#anker', array('r' => rand())) ?>" class="tree">
                    <?= Icon::create($open ? 'arr_1down' : 'arr_1right', 'clickable')->asImg() ?>
                </a>
            </th>
            <th style="font-weight: bold;" colspan="2">
                <a href="<?= URLHelper::getLink('?toggle_group=non_members#anker', array('r' => rand())) ?>" class="tree">
                    <?= _('keiner Funktion oder Gruppe zugeordnet') ?>
                    (<?= $non_members ?>)
                </a>
            </th>
        </tr>
    </thead>
<? if ($open): ?>
    <tbody>
    <? foreach ($data as $row): ?>
        <tr class="<?= TextHelper::cycle('hover_even', 'hover_odd') ?>">
            <td>&nbsp;</td>
        <? if ($rechte || $row['visible'] || $row['user_id'] == $GLOBALS['user']->id): ?>
            <td>
                <a href=" <?= URLHelper::getLink('dispatch.php/profile?username=' . $row['username']) ?>">
                    <?= htmlReady($row['fullname']) ?>
                </a>
            <? if ($row['user_id'] == $GLOBALS['user']->id && !$row['visible']): ?>
                <?= _('(unsichtbar)') ?>
            <? endif; ?>
            </td>
            <td style="text-align: right;">
                <a href="<?= URLHelper::getLink('dispatch.php/messages/write', array('rec_uname' => $row['username'])) ?>" data-dialog>
                    <?= Icon::create('mail', 'clickable', ['title' => _('Systemnachricht an Benutzer verschicken')])->asImg() ?>
                </a>
            </td>
        <? else: ?>
            <td colspan="2">
                <span style="color:#666;"><?= _('unsichtbareR NutzerIn') ?></span>
            </td>
        <? endif; ?>
        </tr>
    <? endforeach; ?>
    </tbody>
<? endif; ?>
</table>
<br>
