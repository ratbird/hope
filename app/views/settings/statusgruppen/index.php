<? if ($verify_action === 'delete' && $verify_id): ?>
<?= $controller->verifyDialog(
        _('Wollen Sie die Zuordnung zu der Funktion wirklich löschen?'),
        array('settings/statusgruppen/delete', $verify_id, true),
        array('settings/statusgruppen#' . $verify_id)
    ) ?>
<? endif; ?>

<? if (count($institutes) === 0): ?>
<p>
    <strong><?= _('Sie sind keinem Institut / keiner Einrichtung zugeordnet!') ?></strong>
</p>
<? else: ?>

<table class="default zebra tree">
    <colgroup>
        <col width="10px">
        <col>
        <col width="80px">
    </colgroup>
<?
    $inst_count = 0;
    foreach ($institutes as $inst_id => $institute):
?>
    <tbody>
        <tr class="header">
            <td colspan="2">
                <a href="<?= $controller->url_for('settings/statusgruppen/switch', $inst_id) ?>"
                   name="<?= $inst_id ?>"
                   class="link <?= $open === $inst_id ? 'open' : 'closed'; ?>">
                    <?= htmlReady($institute['Name']) ?>
                </a>
            </td>
            <td style="text-align: right;">
            <? if (!$locked && $inst_count > 0) : ?>
                <a href="<?= $controller->url_for('settings/statusgruppen/move', $inst_id, 'up') ?>">
                    <?= Assets::img('icons/16/yellow/arr_2up') ?>
                </a>
            <? elseif (!$locked && count($institutes) > 1): ?>
                <?= Assets::img('icons/16/grey/arr_2up') ?>
            <? endif; ?>

            <? if (!$locked && $inst_count + 1 < count($institutes)): ?>
                <a href="<?= $controller->url_for('settings/statusgruppen/move', $inst_id, 'down') ?>">
                    <?= Assets::img('icons/16/yellow/arr_2down') ?>
                </a>
            <? elseif (!$locked && count($institutes) > 1): ?>
                <?= Assets::img('icons/16/grey/arr_2down') ?>
            <? endif; ?>

            <? if ($GLOBALS['perm']->have_studip_perm('admin', $inst_id)) : ?>
                <a href="<?= URLHelper::getURL('inst_admin.php?list=true', array('admin_inst_id' => $inst_id)) ?>">
                    <?= Assets::img('icons/16/blue/link-intern', tooltip2(_('Zur Einrichtung'))) ?>
                </a>
            <? else: ?>
                <a href="<?= URLHelper::getURL('institut_main.php', array('auswahl' => $inst_id)) ?>">
                    <?= Assets::img('icons/16/blue/link-intern', tooltip2(_('Zur Einrichtung'))) ?>
                </a>
            <? endif; ?>
            </td>
        </tr>
    <? if ($open == $inst_id): ?>
        <?= $this->render_partial('settings/statusgruppen/modify_institute',
                array('followers' => count($institute['flattened']) > 0,
                      'inst_id'   => $inst_id,
                      'institute' => $institute)) ?>
    <? endif; ?>

    <? 
        $role_count = 1;
        $max_roles  = count($institute['flattened']);
        foreach ($institute['flattened'] as $role_id => $role):
    ?>
        <tr class="header">
            <td class="<?= $max_roles > $role_count ? 'leaf' : 'end' ?>">&nbsp;</td>
            <td>
            <? if (count($institute['datafields'][$role_id]) > 0): ?>
                <a href="<?= $controller->url_for('settings/statusgruppen/switch', $role_id) ?>"
                   name="<?= $role_id ?>"
                   class="link <?= $open === $role_id ? 'open' : 'closed' ?>">
                    <?= htmlReady($role['name_long']) ?>
                </a>
            <? else: ?>
                <a class="link" href="<?= $controller->url_for('settings/statusgruppen#' . $role_id) ?>">
                    <?= htmlReady($role['name_long']) ?>
                </a>
            <? endif; ?>
            </td>
            <td style="text-align: right;">
            <? if ($GLOBALS['perm']->have_studip_perm('admin', $inst_id) && !$locked) : ?>
                <a href="<?= $controller->url_for('settings/statusgruppen/verify/delete', $role_id) ?>#<?= $role_id ?>">
                    <?= Assets::img('icons/16/blue/trash', tooltip2(_('Löschen'))) ?>
                </a>

                <a href="<?= URLHelper::getURL('admin_roles.php', array('range_id' => $inst_id, 'role_id' => $role_id)) ?>#<?= $role_id ?>">
                    <?= Assets::img('icons/16/blue/link-intern', tooltip2(_('Zur Funktion'))) ?>
                </a>
            <? endif; ?>
            </td>
        </tr>
        <? if (($open === $role_id) && (count($institute['datafields'][$role_id]) > 0)): ?>
            <?= $this->render_partial('settings/statusgruppen/modify',
                    array('followers'  => $role_count < $max_roles,
                          'inst_id'    => $inst_id,
                          'role_id'    => $role_id,
                          'datafields' => $institute['datafields'][$role_id],
                          'role'       => $role['role'])) ?>
        <? endif; ?>

    <?
        $role_count += 1;
        endforeach; // roles
    ?>
    </tbody>
<? 
    $inst_count += 1;
    endforeach; // institutes
?>
</table>

<br>

<table class="default">
<? if ($GLOBALS['perm']->have_perm('admin') && !$locked): ?>
    <?= $this->render_partial('settings/statusgruppen/assign', compact(words('subview_id admin_insts sub_admin_insts'))) ?>
<? endif; ?>
</table>
<? endif; ?>
