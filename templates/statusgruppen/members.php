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
                    <?= Icon::create($open ? 'arr_1down' : 'arr_1right', 'clickable')->asImg() ?>
                </a>
            </th>
            <th style="font-weight: bold;">
            <? if ($may_assign): ?>
                <a href="<?= URLHelper::getLink('?#anker', array('assign' => $group_id)) ?>">
                    <?= Icon::create('arr_2right', 'sort', ['title' => _('In diese Gruppe eintragen')])->asImg(["style" => 'vertical-align:bottom']) ?>
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
                    <?= Icon::create('files', 'clickable', ['title' => _('Dateiordner vorhanden')])->asImg() ?>
                </a>
            <? endif; ?>
            <? if ($may_mail && $members > 0): ?>
                <a href="<?= URLHelper::getLink('dispatch.php/messages/write', array('emailrequest' => 1, 'group_id' => $group_id, 'default_subject' => $subject)) ?>" data-dialog>
                    <?= Icon::create('mail+move_right', 'clickable', ['title' => _('Systemnachricht mit Emailweiterleitung an alle Gruppenmitglieder verschicken')])->asImg(16) ?>
                </a>
                <a href="<?= URLHelper::getLink('dispatch.php/messages/write', array('group_id' => $group_id, 'default_subject' => $subject)) ?>" data-dialog>
                    <?= Icon::create('mail', 'clickable', ['title' => _('Systemnachricht an alle Gruppenmitglieder verschicken')])->asImg() ?>
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
                <a href="<?= URLHelper::getLink('dispatch.php/profile', array('username' => $row['username'])) ?>">
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
                    <?= Icon::create('trash', 'clickable', ['title' => _('Aus dieser Gruppe austragen')])->asImg() ?>
                </a>
            <? endif; ?>
            <? if (($visio[$row['user_id']] || $rechte) && $row['user_id'] != $GLOBALS['user']->id): ?>
                <a href="<?= URLHelper::getLink('dispatch.php/messages/write', array('rec_uname' => $row['username'])) ?>" data-dialog>
                    <?= Icon::create('mail', 'clickable', ['title' => _('Systemnachricht an Benutzer verschicken')])->asImg() ?>
                </a>
            <? endif; ?>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
<? endif; ?>
</table>
<br>
