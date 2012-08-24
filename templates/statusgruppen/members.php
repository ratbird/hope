<? if ($active): ?>
<a name="anker"></a>
<? endif; ?>
<table class="default" width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
    <colgroup>
        <col width="1%">
        <col width="90%">
        <col width="9%">
    </colgroup>
    <thead>
        <tr>
            <th>
                <a href="<?= URLHelper::getLink('?#anker', array('toggle_group' => $group_id, 'r' => rand())) ?>" class="tree">
                    <?= Assets::img('icons/16/blue/'. ($open ? 'arr_1down' : 'arr_1right')) ?>
                </a>
            </th>
            <th style="font-weight: bold;">
            <? if ($may_assign): ?>
                <a href="<?= URLHelper::getLink('?#anker', array('assign' => $group_id)) ?>">
                    <?= Assets::img('icons/16/yellow/arr_2right', 
                            array('style' => 'vertical-align:bottom')
                            + tooltip2(_('In diese Gruppe eintragen'))) ?>
                </a>
            <? endif; ?>
                <a href="<?= URLHelper::getLink('?#anker', array('toggle_group' => $group_id, 'r' => rand())) ?>" class="tree">
                    <?= htmlReady($title) ?> (<?= (int)$members ?>)
                </a>
            <? if ($limitted): ?>
                <span style="color: <?= $members >= $limit ? '#c00' : '#080' ?>;padding-left: 1em;">
                    <?= sprintf(_('%s von %s Plätzen belegt'), $members, $limit) ?>
                </span>
            <? endif; ?>
            </th>
            <th style="text-align: right;">
            <? if ($folder_id): ?>
                <a href="<?= URLHelper::getLink('folder.php?cmd=tree#anker', array('open' => $folder_id)) ?>">
                    <?= Assets::img('icons/16/blue/files', tooltip2(_('Dateiordner vorhanden'))) ?>
                </a>
            <? endif; ?>
            <? if ($may_mail): ?>
                <a href="<?= URLHelper::getLink('sms_send.php?sms_source_page=statusgruppen.php&emailrequest=1', compact('group_id', 'subject')) ?>">
                    <?= Assets::img('icons/16/blue/move_right/mail', tooltip2(_('Systemnachricht mit Emailweiterleitung an alle Gruppenmitglieder verschicken'))) ?>
                </a>
                <a href="<?= URLHelper::getLink('sms_send.php?sms_source_page=statusgruppen.php', compact('group_id', 'subject')) ?>">
                    <?= Assets::img('icons/16/blue/mail', tooltip2(_('Systemnachricht an alle Gruppenmitglieder verschicken'))) ?>
                </a>
            <? endif; ?>
            </th>
        </tr>
    </thead>
<? if ($open): ?>
    <tbody>
    <? foreach ($data as $row): ?>
        <tr class="<?= TextHelper::cycle('hover_even', 'hover_odd') ?>">
            <td>&nbsp;</td>
            <td>
            <? if ($row['visible'] || $row['user_id'] == $GLOBALS['user']->id || $rechte): ?>
                <a href="<?= URLHelper::getLink('about.php', array('username' => $row['username'])) ?>">
                    <?= htmlReady($row['fullname']) ?>
                </a>
                <? if ($row['user_id'] == $GLOBALS['user']->id && !$row['visible'] && !$rechte): ?>
                    <?= _('(unsichtbar)') ?>
                <? endif; ?>
            <? else: ?>
                <span style="color:#666;"><?= _('(unsichtbareR NutzerIn)') ?></span>
            <? endif; ?>
            </td>
            <td style="text-align: right;">
            <? if ($row['user_id'] == $GLOBALS['user']->id && $self_assign): ?>
                <a href="<?= URLHelper::getLink('', array('delete_id' => $group_id)) ?>">
                    <?= Assets::img('icons/16/blue/trash', tooltip2(_('Aus dieser Gruppe austragen'))) ?>
                </a>
            <? endif; ?>
            <? if (($visio[$row['user_id']] || $rechte) && $row['user_id'] != $GLOBALS['user']->id): ?>
                <a href="<?= URLHelper::getLink('sms_send.php?sms_source_page=teilnehmer.php', array('rec_uname' => $row['username'])) ?>">
                    <?= Assets::img('icons/16/blue/mail', tooltip2(_('Systemnachricht an Benutzer verschicken'))) ?>
                </a>
            <? endif; ?>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
<? endif; ?>
</table>
<br>
