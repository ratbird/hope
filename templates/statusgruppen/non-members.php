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
                    <?= Assets::img('icons/16/blue/'. ($open ? 'arr_1down' : 'arr_1right')) ?>
                </a>
            </th>
            <th style="font-weight: bold;" colspan="2">
                <a href="<?= URLHelper::getLink('?toggle_group=non_members#anker', array('r' => rand())) ?>" class="tree">
                    <?= _('keiner Funktion oder Gruppe zugeordnet') ?>
                    (<?= count($data) ?>)
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
                <a href=" <?= URLHelper::getLink('about.php?username=' . $row['username']) ?>">
                    <?= htmlReady($row['fullname']) ?>
                </a>
            <? if ($row['user_id'] == $GLOBALS['user']->id && !$row['visible']): ?>
                <?= _('(unsichtbar)') ?>
            <? endif; ?>
            </td>
            <td style="text-align: right;">
                <a href="<?= URLHelper::getLink('sms_send.php?sms_source_page=teilnehmer.php&rec_uname=' . $row['username']) ?>">
                    <?= Assets::img('icons/16/blue/mail', tooltip2(_('Systemnachricht an Benutzer verschicken'))) ?>
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
