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

<table class="default nohover tree">
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
                    <?= Icon::create('arr_2up', 'sort')->asImg() ?>
                </a>
            <? elseif (!$locked && count($institutes) > 1): ?>
                <?= Icon::create('arr_2up', 'inactive')->asImg() ?>
            <? endif; ?>

            <? if (!$locked && $inst_count + 1 < count($institutes)): ?>
                <a href="<?= $controller->url_for('settings/statusgruppen/move', $inst_id, 'down') ?>">
                    <?= Icon::create('arr_2down', 'sort')->asImg() ?>
                </a>
            <? elseif (!$locked && count($institutes) > 1): ?>
                <?= Icon::create('arr_2down', 'inactive')->asImg() ?>
            <? endif; ?>

            <? if ($GLOBALS['perm']->have_studip_perm('admin', $inst_id)) : ?>
                <a href="<?= URLHelper::getURL('dispatch.php/institute/members', array('cid' => $inst_id, 'admin_view' => 1)) ?>">
                    <?= Icon::create('link-intern', 'clickable', ['title' => _('Zur Einrichtung')])->asImg() ?>
                </a>
            <? else: ?>
                <a href="<?= URLHelper::getURL('dispatch.php/institute/overview', array('auswahl' => $inst_id)) ?>">
                    <?= Icon::create('link-intern', 'clickable', ['title' => _('Zur Einrichtung')])->asImg() ?>
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
                    <?= Icon::create('trash', 'clickable', ['title' => _('Löschen')])->asImg() ?>
                </a>

                <a href="<?= URLHelper::getURL('dispatch.php/admin/statusgroups', array('cid' => $inst_id)) ?>#group-<?= $role_id ?>">
                    <?= Icon::create('link-intern', 'clickable', ['title' => _('Zur Funktion')])->asImg() ?>
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
