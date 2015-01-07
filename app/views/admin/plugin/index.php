<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<? if (isset($error)): ?>
    <?= MessageBox::error($error, $error_detail) ?>
<? endif ?>

<? if (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['message'])): ?>
    <?= MessageBox::success($flash['message']) ?>
<? elseif ($num_updates): ?>
    <?= MessageBox::info($this->render_partial('admin/plugin/update_info')) ?>
<? endif ?>

<? if ($delete_plugin): ?>
    <?= $GLOBALS['template_factory']->render('shared/question',
        array('question' => sprintf(_('Wollen Sie wirklich "%s" deinstallieren?'), $delete_plugin['name']),
              'approvalLink' => $controller->url_for('admin/plugin/delete/' . $delete_plugin['id'],
                                                     array('studip_ticket' => get_ticket())),
              'disapprovalLink' => $controller->url_for('admin/plugin'))) ?>
<? endif ?>

<? if (count($plugins) == 0): ?>
    <?= MessageBox::info(_('Es sind noch keine Plugins in diesem Stud.IP vorhanden.'), array(
            _('Sie können Plugins aus dem Marktplatz installieren oder manuell hochladen.'),
            sprintf(_('Benutzen Sie dafür die Funktion "%sweitere Plugins installieren%s" in der Info-Box.'),
                '<a href="'.$controller->url_for('admin/plugin/search').'">', '</a>'))) ?>
<? else: ?>
    <form action="<?= $controller->url_for('admin/plugin/save') ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <input type="hidden" name="studip_ticket" value="<?= get_ticket() ?>">
        <input type="hidden" name="plugin_filter" value="<?= $plugin_filter ?>">
        <table class="default">
        <caption>
                <?= _('Verwaltung von Plugins')?>
        </caption>
        <thead>
            <tr>
                <th><?= _('Aktiv') ?></th>
                <th><?= _('Name')?></th>
                <th><?= _('Typ') ?></th>
                <th><?= _('Version') ?></th>
                <th><?= _('Schema') ?></th>
                <th><?= _('Position') ?></th>
                <th class="actions" colspan="5"><?= _('Aktionen') ?></th>
            </tr>
        </thead>
        <tbody>
            <? foreach ($plugins as $plugin): ?>
                <? $pluginid = $plugin['id'] ?>
                <tr>
                    <td style="padding-left: 1ex;" width="30">
                        <input type="checkbox" name="enabled_<?= $pluginid ?>" value="1" <?= $plugin['enabled'] ? 'checked' : '' ?>>
                    </td>
                    <td>
                        <a href="<?= $controller->url_for('admin/plugin/manifest', $pluginid) ?>">
                            <?= htmlReady($plugin['name']) ?>
                            <?= $plugin['core'] ? '<i>('. _('Kern-Plugin') . ')</i>' : '' ?>
                        </a>
                    </td>
                    <td <?= $plugin['enabled'] ? '' : 'class="quiet"' ?>>
                        <?= join(', ', $plugin['type']) ?>
                    </td>
                    <td <?= $plugin['enabled'] ? '' : 'class="quiet"' ?>>
                        <?= htmlReady($update_info[$pluginid]['version']) ?>
                    </td>
                    <td <?= $plugin['enabled'] ? '' : 'class="quiet"' ?>>
                        <? if (!$plugin['depends']) : ?>
                        <?= htmlReady($migrations[$pluginid]['schema_version']) ?>
                            <? if ($migrations[$pluginid]['schema_version'] < $migrations[$pluginid]['migration_top_version']) :?>
                                <a href="<?= $controller->url_for('admin/plugin/migrate', $pluginid) ?>" title="<?= sprintf(_("Update auf Version %d verfügbar"), $migrations[$pluginid]['migration_top_version']) ?>">
                                <?= Assets::img('icons/20/blue/new/plugin.png');?>
                                </a>
                            <? endif; ?>
                        <? endif; ?>
                    </td>
                    <td>
                        <input name="position_<?= $pluginid ?>" type="text" size="2" value="<?= $plugin['position'] ?>" <?= $plugin['enabled'] ? '' : 'disabled' ?>>
                    </td>
                    <td class="actions" width="20">
                        <? if (in_array('StandardPlugin', $plugin['type'])): ?>
                            <a href="<?= $controller->url_for('admin/plugin/default_activation', $pluginid) ?>">
                                <?= Assets::img('icons/20/blue/add/seminar.png', array('title' => _('In Veranstaltungen aktivieren'))) ?>
                            </a>
                        <? endif ?>
                    </td>
                    <td class="actions"  width="20">
                        <a href="<?= $controller->url_for('admin/role/assign_plugin_role', $pluginid) ?>">
                            <?= Assets::img('icons/20/blue/edit.png', array('title' => _('Zugriffsrechte bearbeiten'))) ?>
                        </a>
                    </td>
                    <td class="actions" width="20">
                        <? if (!$plugin['depends'] && isset($update_info[$pluginid]['version']) && !$plugin['core']): ?>
                        <a href="<?= $controller->url_for('admin/plugin/edit_automaticupdate', $pluginid) ?>" data-dialog>
                            <? if ($plugin['automatic_update_url']) : ?>
                                <?= Assets::img('icons/20/red/move_down/plugin', array('title' => _('Automatisches Update verwalten (eingerichtet)'))) ?>
                            <? else : ?>
                                <?= Assets::img('icons/20/blue/move_down/plugin', array('title' => _('Automatisches Update verwalten'))) ?>
                            <? endif ?>
                        </a>
                        <? endif ?>
                    </td>
                    <td class="actions"  width="20">
                        <? if (!$plugin['depends'] && isset($update_info[$pluginid]['version']) && !$plugin['core']): ?>
                            <a href="<?= $controller->url_for('admin/plugin/download', $pluginid) ?>">
                                <?= Assets::img('icons/20/blue/download.png', array('title' => _('Herunterladen'))) ?>
                            </a>
                        <? endif ?>
                    </td>
                    <td class="actions"  width="20">
                        <? if (!$plugin['depends'] && !$plugin['core']): ?>
                            <a href="<?= $controller->url_for('admin/plugin/ask_delete', $pluginid) ?>">
                                <?= Assets::img('icons/20/blue/trash.png', array('title' => _('Deinstallieren'))) ?>
                            </a>
                        <? endif ?>
                    </td>
                </tr>
            <? endforeach ?>
        </tbody>
        <tfoot>
            <tr>
                <td style="text-align: center;" colspan=10>
                    <?= Button::createAccept(_('Speichern'), 'save', array('title' => _('Einstellungen speichern')))?>
                </td>
            </tr>
        </tfoot>
        </table>
    </form>
<? endif ?>

<?
$sidebar = Sidebar::Get();
$sidebar->setTitle(_('Plugins'));
$sidebar->setImage('sidebar/plugin-sidebar.png');



if (get_config('PLUGINS_UPLOAD_ENABLE')) {
    $actions = new ActionsWidget();
    $actions->addLink(_('Weitere Plugins installieren'), $controller->url_for('admin/plugin/search'), 'icons/16/blue/add.png');
    $actions->addLink(_('Plugin von URL installieren'), $controller->url_for('admin/plugin/edit_automaticupdate'), 'icons/16/blue/download.png', array('data-dialog' => "true"));
    $sidebar->addWidget($actions);

    $widget = new SidebarWidget();
    $widget->setTitle(_('Weitere Plugins installieren'));
    $widget->addElement(new WidgetElement($this->render_partial('admin/plugin/upload-drag-and-drop')));
    $sidebar->addWidget($widget);
}

