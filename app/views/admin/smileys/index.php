<? use Studip\Button; ?>

<form action="<?= $controller->url_for('/admin/smileys/delete/bulk', $view) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

    <table class="default">
        <colgroup>
            <col width="20px">
            <col>
            <col>
            <col width="50px">
            <col>
            <col width="50px">
        <? if ($favorites_enabled): ?>
            <col width="50px">
        <? endif; ?>
            <col width="50px">
        </colgroup>
        <thead>
            <tr>
                <th>&nbsp;</th>
                <th><?= _('Smiley') ?></th>
                <th><?= _('Smileyname') ?></th>
                <th>&Sigma;</th>
                <th><?= _('K�rzel') ?></th>
                <th>&Sigma;</th>
            <? if ($favorites_enabled): ?>
                <th><?= _('Favoriten') ?></th>
            <? endif; ?>
                <th>&nbsp;</th>
            </tr>
        </thead>
    <? if (empty($smileys)): ?>
        <tbody>
            <tr>
                <td align="center" class="blank" colspan="<?= $favorites_enabled ? 8 : 7 ?>">
                    <?= _('Keine Smileys vorhanden.') ?>
                </td>
            </tr>
        </tbody>
    <? else: ?>
        <tbody>
        <? foreach ($smileys as $smiley): ?>
            <tr id="smiley<?= $smiley->id ?>">
                <td><input type="checkbox" name="smiley_id[]" value="<?= $smiley->id ?>"></td>
                <td><?= $smiley->getImageTag() ?></td>
                <td><?= htmlReady($smiley->name) ?></td>
                <td><?= $smiley->count ?></td>
            <? if ($smiley->short): ?>
                <td class="separator"><?= htmlReady($smiley->short) ?></td>
                <td><?= $smiley->short_count ?></td>
            <? else: ?>
                <td class="separator" colspan="2" align="center">-</td>
            <? endif; ?>
            <? if ($favorites_enabled): ?>
                <td class="separator"><?= $smiley->fav_count ?></td>
            <? endif; ?>
                <td align="right">
                    <a href="<?= $controller->url_for('admin/smileys/edit', $smiley->id, $view) ?>"
                       title="<?= htmlReady(sprintf(_('Smiley "%s" bearbeiten'), $smiley->name)) ?>">
                        <?= Assets::img('icons/16/blue/edit.png') ?>
                    </a>
                    <a href="<?= $controller->url_for('admin/smileys/delete', $smiley->id, $view) ?>"
                       title="<?= htmlReady(sprintf(_('Smiley "%s" l�schen'), $smiley->name)) ?>">
                        <?= Assets::img('icons/16/blue/trash.png') ?>
                    </a>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td>
                    <input class="middle" type="checkbox" data-proxyfor=":checkbox[name^=smiley_id]" name="check_all" title="<?= _('Alle Benutzer ausw�hlen') ?>">
                </td>
                <td colspan="<?= $favorites_enabled ? 7 : 6 ?>">
                    <?= Studip\Button::createCancel(_('Markierte l�schen'), 'bulk-delete') ?>
                </td>
            </tr>
        </tfoot>
    <? endif; ?>
    </table>
</form>
