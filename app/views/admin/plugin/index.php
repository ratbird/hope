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
            _('Sie k�nnen Plugins aus dem Marktplatz installieren oder manuell hochladen.'),
            sprintf(_('Benutzen Sie daf�r die Funktion "%sweitere Plugins installieren%s" in der Info-Box.'),
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
                                <a href="<?= $controller->url_for('admin/plugin/migrate', $pluginid) ?>" title="<?= sprintf(_("Update auf Version %d verf�gbar"), $migrations[$pluginid]['migration_top_version']) ?>">
                                <?= Icon::create('plugin+new', 'clickable')->asImg(20);?>
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
                                <?= Icon::create('seminar+add', 'clickable', ['title' => _('In Veranstaltungen aktivieren')])->asImg(20) ?>
                            </a>
                        <? endif ?>
                    </td>
                    <td class="actions"  width="20">
                        <a href="<?= $controller->url_for('admin/role/assign_plugin_role', $pluginid) ?>">
                            <?= Icon::create('edit', 'clickable', ['title' => _('Zugriffsrechte bearbeiten')])->asImg(20) ?>
                        </a>
                    </td>
                    <td class="actions" width="20">
                        <? if (!$plugin['depends'] && isset($update_info[$pluginid]['version']) && !$plugin['core']): ?>
                        <a href="<?= $controller->url_for('admin/plugin/edit_automaticupdate', $pluginid) ?>" data-dialog>
                            <? if ($plugin['automatic_update_url']) : ?>
                                <?= Icon::create('plugin+move_down', 'attention', ['title' => _('Automatisches Update verwalten (eingerichtet)')])->asImg(20) ?>
                            <? else : ?>
                                <?= Icon::create('plugin+move_down', 'clickable', ['title' => _('Automatisches Update verwalten')])->asImg(20) ?>
                            <? endif ?>
                        </a>
                        <? endif ?>
                    </td>
                    <td class="actions"  width="20">
                        <? if (!$plugin['depends'] && isset($update_info[$pluginid]['version']) && !$plugin['core']): ?>
                            <a href="<?= $controller->url_for('admin/plugin/download', $pluginid) ?>">
                                <?= Icon::create('download', 'clickable', ['title' => _('Herunterladen')])->asImg(20) ?>
                            </a>
                        <? endif ?>
                    </td>
                    <td class="actions"  width="20">
                        <? if (!$plugin['depends'] && !$plugin['core']): ?>
                            <a href="<?= $controller->url_for('admin/plugin/ask_delete', $pluginid) ?>">
                                <?= Icon::create('trash', 'clickable', ['title' => _('Deinstallieren')])->asImg(20) ?>
                            </a>
                        <? endif ?>
                    </td>
                </tr>
            <? endforeach ?>
        </tbody>
        <tfoot>
            <tr>
                <td style="text-align: center;" colspan="11">
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
    $actions->addLink(_('Weitere Plugins installieren'), $controller->url_for('admin/plugin/search'), Icon::create('add', 'clickable'));
    $actions->addLink(_('Plugin von URL installieren'), $controller->url_for('admin/plugin/edit_automaticupdate'), Icon::create('download', 'clickable'), array('data-dialog' => "true"));
    $sidebar->addWidget($actions);

    $widget = new SidebarWidget();
    $widget->setTitle(_('Weitere Plugins installieren'));
    $widget->addElement(new WidgetElement($this->render_partial('admin/plugin/upload-drag-and-drop')));
    $sidebar->addWidget($widget);
}

