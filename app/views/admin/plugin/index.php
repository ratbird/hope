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
              'approvalLink' => $controller->url_for('admin/plugin/delete', $delete_plugin['id']).'?ticket='.get_ticket(),
              'disapprovalLink' => $controller->url_for('admin/plugin'))) ?>
<? endif ?>

<h3>
    <?= _('Verwaltung von Plugins')?>
</h3>

<? if (count($plugins) == 0): ?>
    <?= MessageBox::info(_('Es sind noch keine Plugins in diesem Stud.IP vorhanden.'), array(
            _('Sie können Plugins aus dem Marktplatz installieren oder manuell hochladen.'),
            sprintf(_('Benutzen Sie dafür die Funktion "%sweitere Plugins installieren%s" in der Info-Box.'),
                '<a href="'.$controller->url_for('admin/plugin/search').'">', '</a>'))) ?>
<? else: ?>
    <form action="<?= $controller->url_for('admin/plugin/save') ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <input type="hidden" name="ticket" value="<?= get_ticket() ?>">
        <input type="hidden" name="plugin_filter" value="<?= $plugin_filter ?>">
        <table class="default">
            <tr>
                <th><?= _('Aktiv') ?></th>
                <th><?= _('Name')?></th>
                <th><?= _('Typ') ?></th>
                <th><?= _('Version') ?></th>
                <th><?= _('Position') ?></th>
                <th colspan="4"><?= _('Aktionen') ?></th>
            </tr>

            <? foreach ($plugins as $plugin): ?>
                <? $pluginid = $plugin['id'] ?>
                <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
                    <td style="padding-left: 1ex;" width="30">
                        <input type="checkbox" name="enabled_<?= $pluginid ?>" value="1" <?= $plugin['enabled'] ? 'checked' : '' ?>>
                    </td>
                    <td>
                        <a href="<?= $controller->url_for('admin/plugin/manifest', $pluginid) ?>">
                            <?= htmlspecialchars($plugin['name']) ?>
                        </a>
                    </td>
                    <td <?= $plugin['enabled'] ? '' : 'class="quiet"' ?>>
                        <?= join(', ', $plugin['type']) ?>
                    </td>
                    <td <?= $plugin['enabled'] ? '' : 'class="quiet"' ?>>
                        <?= htmlspecialchars($update_info[$pluginid]['version']) ?>
                    </td>
                    <td>
                        <input name="position_<?= $pluginid ?>" type="text" size="2" value="<?= $plugin['position'] ?>" <?= $plugin['enabled'] ? '' : 'disabled' ?>>
                    </td>
                    <td width="20">
                        <? if (in_array('StandardPlugin', $plugin['type'])): ?>
                            <a href="<?= $controller->url_for('admin/plugin/default_activation', $pluginid) ?>">
                                <?= Assets::img('icons/16/blue/add/seminar.png', array('title' => _('In Veranstaltungen aktivieren'))) ?>
                            </a>
                        <? endif ?>
                    </td>
                    <td width="20">
                        <a href="<?= $controller->url_for('admin/role/assign_plugin_role', $pluginid) ?>">
                            <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Zugriffsrechte bearbeiten'))) ?>
                        </a>
                    </td>
                    <td width="20">
                        <? if (!$plugin['depends'] && isset($update_info[$pluginid]['version'])): ?>
                            <a href="<?= $controller->url_for('admin/plugin/download', $pluginid) ?>">
                                <?= Assets::img('icons/16/blue/download.png', array('title' => _('Herunterladen'))) ?>
                            </a>
                        <? endif ?>
                    </td>
                    <td width="20">
                        <? if (!$plugin['depends']): ?>
                            <a href="<?= $controller->url_for('admin/plugin/ask_delete', $pluginid) ?>">
                                <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Deinstallieren'))) ?>
                            </a>
                        <? endif ?>
                    </td>
                </tr>
            <? endforeach ?>
        </table>

        <div style="padding-top: 1em; text-align: center;">
            <?= Button::createAccept(_('Speichern'), 'save', array('title' => _('Einstellungen speichern')))?>
        </div>
    </form>
<? endif ?>

<?
$infobox_content = array(
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/black/add/plugin.png',
                'text' => '<a href="'.$controller->url_for('admin/plugin/search').'">'._('Weitere Plugins installieren').'</a>'
                          . $this->render_partial('admin/plugin/upload-drag-and-drop')
            )
        )
    ), array(
        'kategorie' => _('Anzeigefilter:'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/black/search.png',
                'text' => $this->render_partial('admin/plugin/plugin_filter')
            )
        )
    ), array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                "icon" => "icons/16/black/info.png",
                'text' => _('Per Default-Aktivierung lassen sich Standard-Plugins automatisch in allen Veranstaltungen einer Einrichtung aktivieren.')
            ), array(
                "icon" => "icons/16/black/info.png",
                'text' => _('Position gibt die Reihenfolge des Plugins in der Navigation an. Erlaubt sind nur Werte größer als 0.')
            )
        )
    )
);

$infobox = array('picture' => 'infobox/modules.jpg', 'content' => $infobox_content);
?>
