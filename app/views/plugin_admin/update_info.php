<?= sprintf(ngettext('Es ist ein Update für ein Plugin verfügbar', 'Es sind Updates für %d Plugins verfügbar', $num_updates), $num_updates) ?>

<form action="<?= $controller->url_for('plugin_admin/install_updates') ?>" method="post">
    <input type="hidden" name="ticket" value="<?= get_ticket() ?>">
    <div style="margin: 1ex;">
        <? foreach ($plugins as $plugin): ?>
            <? $pluginid = $plugin['id'] ?>
            <? if (isset($update_info[$pluginid]['update']) && !$plugin['depends']): ?>
                <div>
                    <label>
                        <input type="checkbox" name="update[]" value="<?= $pluginid ?>" checked>
                        <?= htmlspecialchars(sprintf(_('%s: Version %s installieren'), $plugin['name'], $update_info[$pluginid]['update']['version'])) ?>
                    </label>
                </div>
            <? endif ?>
        <? endforeach ?>
    </div>

    <?= makeButton('starten', 'input', _('Updates installieren'), 'update') ?>
</form>
