<form method="POST" action="<?=URLHelper::getLink('?add_root_user=TRUE') ?>">
<?= CSRFProtection::tokenTag() ?>

<table class="default zebra" style="margin: 0 1%; width: 98%;">
    <colgroup>
        <col width="4%">
        <col width="52%">
        <col width="10%">
        <col width="4%">
        <col width="30%">
    </colgroup>
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th><?= _('Name') ?></th>
            <th style="text-align: center;"><?= _('Aktionen') ?></th>
            <th>&nbsp;</th>
            <th style="text-align: center;"><?= _('Suchen/hinzuf&uuml;gen') ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>&nbsp;</td>
            <td>
                <?= _('Diese NutzerInnen sind als globale Ressourcen-Administratoren mit folgenden Rechten eingetragen:') ?>
            </td>
            <td colspan="2">&nbsp;</td>
            <td valign="top">
                <label>
                    <?= _('NutzerInnen hinzuf&uuml;gen') ?><br>
                    <? showSearchForm('search_root_user', $search_string_search_root_user, TRUE, FALSE, TRUE) ?>
                </label>
            </td>
        </tr>
    <? foreach ($users as $user): ?>
        <tr>
            <td>&nbsp;</td>
            <td valign="top">
                <a href="<?= $resObject->getOwnerLink($user['user_id']) ?>">
                    <?= $resObject->getOwnerName(TRUE, $user['user_id']) ?>
                </a>
                (<?= get_username($user['user_id']); ?>)
                <br>
            <? if ($user['perms'] == 'admin'): ?>
                <?= _('<b>Admin</b>: Nutzer kann s&auml;mtliche Belegungen und Eigenschaften &auml;ndern und Rechte vergeben') ?>
            <? elseif ($user['perms'] == 'tutor'): ?>
                <?= _('<b>Tutor</b>: Nutzer kann s&auml;mtliche Belegungen &auml;ndern') ?>
            <? elseif ($user['perms'] == 'autor'): ?>
                <?= _('<b>Autor</b>: Nutzer kann nur eigene Belegungen &auml;ndern') ?>
            <? endif; ?>
            </td>
            <td valign="middle" align="center">
                <a href="<?=URLHelper::getLink('?delete_root_user_id=' . $user['user_id']) ?>">
                    <?=Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' => _('Berechtigungen l�schen'))) ?>
                </a>
            </td>
            <td colspan="2">&nbsp;</td>
        </tr>
    <? endforeach; ?>
    </tbody>
</table>
</form>
<br><br>
