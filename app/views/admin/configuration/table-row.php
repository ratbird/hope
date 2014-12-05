<tr>
    <td>
        <?= htmlReady($field) ?>
    <? if (!empty($description)): ?>
        <br><small><?= htmlReady($description)?></small>
    <? endif; ?>
    </td>
    <td class="wrap-content">
    <? if ($type === 'string'): ?>
        <em><?= htmlReady($value) ?></em>
    <? elseif ($type === 'integer'): ?>
        <?= (int)$value ?>
    <? elseif ($type === 'boolean'): ?>
        <?if ($value):?>
            <?= Assets::img('icons/16/green/accept.png', array('title' => _('TRUE'))) ?>
        <? else :?>
            <?= Assets::img('icons/16/red/decline.png', array('title' => _('FALSE'))) ?>
        <? endif; ?>
    <? endif; ?>
    </td>
    <td><?= htmlReady($type) ?></td>
    <td class="actions">
        <a data-dialog href="<?= $controller->url_for($linkchunk . $field) ?>">
            <?= Assets::img('icons/16/blue/edit.png',
                            tooltip2(_('Konfigurationsparameter bearbeiten'))) ?>
        </a>
    </td>
</tr>
