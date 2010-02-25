<? if (isset($error)): ?>
    <?= MessageBox::error($error, $error_detail) ?>
<? endif ?>

<? if (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['message'])): ?>
    <?= MessageBox::success($flash['message']) ?>
<? elseif ($num_updates): ?>
    <?= MessageBox::info($this->render_partial('plugin_admin/update_info')) ?>
<? endif ?>

<? if ($delete_plugin): ?>
    <?= $GLOBALS['template_factory']->render('shared/question',
        array('question' => sprintf(_('Wollen Sie wirklich "%s" deinstallieren?'), $delete_plugin['name']),
              'approvalLink' => $controller->url_for('plugin_admin/delete', $delete_plugin['id']).'?ticket='.get_ticket(),
              'disapprovalLink' => $controller->url_for('plugin_admin'))) ?>
<? endif ?>

<h3>
    <?= _('Verwaltung von Plugins')?>
</h3>

<form action="<?= $controller->url_for('plugin_admin/save') ?>" method="post">
    <input type="hidden" name="ticket" value="<?= get_ticket() ?>">
    <input type="hidden" name="plugin_filter" value="<?= $plugin_filter ?>">
    <table class="default">
        <tr>
            <th><?= _('Aktiv') ?></th>
            <th><?= _('Name')?></th>
            <th><?= _('Typ') ?></th>
            <th><?= _('Version') ?></th>
            <th><?= _('Position') ?></th>
            <th colspan="3"><?= _('Aktionen') ?></th>
        </tr>

        <? foreach ($plugins as $plugin): ?>
            <? $pluginid = $plugin['id'] ?>
            <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
                <td style="padding-left: 1ex;">
                    <input type="checkbox" name="enabled_<?= $pluginid ?>" value="1" <?= $plugin['enabled'] ? 'checked' : '' ?>>
                </td>
                <td>
                    <a href="<?= $controller->url_for('plugin_admin/manifest', $pluginid) ?>">
                        <?= htmlspecialchars($plugin['name']) ?>
                    </a>
                    <? if (in_array('StandardPlugin', $plugin['type'])): ?>
                        <a href="<?= $controller->url_for('plugin_admin/default_activation', $pluginid) ?>">
                            <?= Assets::img('config.png', array('class' => 'middle', 'title' => _('Default-Aktivierung'))) ?>
                        </a>
                    <? endif ?>
                <td <?= $plugin['enabled'] ? '' : 'class="quiet"' ?>>
                    <?= join(', ', $plugin['type']) ?>
                </td>
                <td <?= $plugin['enabled'] ? '' : 'class="quiet"' ?>>
                    <?= htmlspecialchars($update_info[$pluginid]['version']) ?>
                </td>
                <td>
                    <input name="position_<?= $pluginid ?>" type="text" size="2" value="<?= $plugin['position'] ?>" <?= $plugin['enabled'] ? '' : 'disabled' ?>>
                </td>
                <td>
                    <a href="<?= $controller->url_for('role_admin/assign_plugin_role', $pluginid) ?>">
                        <?= Assets::img('edit_transparent.gif', array('title' => _('Zugriffsrechte bearbeiten'))) ?>
                    </a>
                </td>
                <td>
                    <? if (!$plugin['depends']): ?>
                        <a href="<?= $controller->url_for('plugin_admin/download', $pluginid) ?>">
                            <?= Assets::img('icon-disc.gif', array('title' => _('Herunterladen'))) ?>
                        </a>
                    <? endif ?>
                </td>
                <td>
                    <? if (!$plugin['depends']): ?>
                        <a href="<?= $controller->url_for('plugin_admin/ask_delete', $pluginid) ?>">
                            <?= Assets::img('trash.gif', array('title' => _('Deinstallieren'))) ?>
                        </a>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
    </table>

    <div style="padding-top: 1em; text-align: center;">
        <?= makeButton('speichern', 'input', _('Einstellungen speichern'), 'save') ?>
    </div>
</form>

<?
$infobox_content = array(
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                'icon' => 'link_intern.gif',
                'text' => '<a href="'.$controller->url_for('plugin_admin/search').'">'._('Weitere Plugins installieren').'</a>'
            )
        )
    ), array(
        'kategorie' => _('Anzeigefilter:'),
        'eintrag'   => array(
            array(
                'icon' => 'suchen.gif',
                'text' => $this->render_partial('plugin_admin/plugin_filter')
            )
        )
    ), array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                'icon' => 'ausruf_small.gif',
                'text' => _('Per Default-Aktivierung lassen sich Standard-Plugins automatisch in allen Veranstaltungen einer Einrichtung aktivieren.')
            ), array(
                'icon' => 'ausruf_small.gif',
                'text' => _('Position gibt die Reihenfolge des Plugins in der Navigation an. Erlaubt sind nur Werte größer als 0.')
            )
        )
    )
);

$infobox = array('picture' => 'modules.jpg', 'content' => $infobox_content);
?>
