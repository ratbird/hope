<?= $this->render_partial('role_admin/status_message') ?>

<h3>
    <?= _('Rollenverwaltung für Plugins') ?>
</h3>

<form action="<?= $controller->url_for('role_admin/assign_plugin_role') ?>" style="margin-bottom: 1em;" method="POST">
    <select name="pluginid" style="min-width: 300px;">
        <? foreach ($plugins as $plugin): ?>
            <option value="<?= $plugin['id'] ?>" <?= $plugin['id'] == $pluginid ? 'selected' : '' ?>>
                <?= htmlspecialchars($plugin['name']) ?>
            </option>
        <? endforeach ?>
    </select>

    <?= makeButton('auswaehlen', 'input', _('Plugin auswahlen'), 'select') ?>
</form>

<? if ($pluginid): ?>
    <form action="<?= $controller->url_for('role_admin/save_plugin_role', $pluginid) ?>" method="POST">
        <input type="hidden" name="ticket" value="<?= get_ticket() ?>">
        <table class="default">
            <tr>
                <th style="text-align: center;"><?= _('Gegenwärtig zugewiesene Rollen') ?></th>
                <th></th>
                <th><?= _('Verfügbare Rollen') ?></th>
            </tr>
            <tr class="steel1">
                <td style="text-align: right;">
                    <select multiple name="assignedroles[]" size="10" style="width: 300px;">
                        <? foreach ($assigned as $assignedrole): ?>
                            <option value="<?= $assignedrole->getRoleid() ?>">
                                <?= htmlReady($assignedrole->getRolename()) ?>
                                <? if ($assignedrole->getSystemtype()): ?>[<?= _('Systemrolle') ?>]<? endif ?>
                            </option>
                        <? endforeach ?>
                    </select>
                </td>
                <td style="text-align: center;">
                    <input type="image" name="assign_role" src="<?= Assets::image_path('move_left.gif') ?>" title="<?= _('Markierte Rollen dem Plugin zuweisen') ?>">
                    <br>
                    <br>
                    <input type="image" name="remove_role" src="<?= Assets::image_path('move_right.gif') ?>" title="<?= _('Markierte Rollen entfernen') ?>">
                </td>
                <td>
                    <select multiple name="rolesel[]" size="10" style="width: 300px;">
                        <? foreach ($roles as $role): ?>
                            <option value="<?= $role->getRoleid() ?>">
                                <?= htmlReady($role->getRolename()) ?>
                                <? if ($role->getSystemtype()): ?>[<?= _('Systemrolle') ?>]<? endif ?>
                            </option>
                        <? endforeach ?>
                    </select>
                </td>
            </tr>
        </table>
    </form>
<? endif ?>

<?
$infobox_content = array(
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                'icon' => 'link_intern.gif',
                'text' => '<a href="'.$controller->url_for('role_admin').'">'._('Rollen verwalten').'</a>'
            ), array(
                'icon' => 'link_intern.gif',
                'text' => '<a href="'.$controller->url_for('role_admin/assign_role').'">'._('Benutzerzuweisungen bearbeiten').'</a>'
            ), array(
                'icon' => 'link_intern.gif',
                'text' => '<a href="'.$controller->url_for('role_admin/assign_plugin_role').'">'._('Pluginzuweisungen bearbeiten').'</a>'
            ), array(
                'icon' => 'link_intern.gif',
                'text' => '<a href="'.$controller->url_for('role_admin/show_role').'">'._('Rollenzuweisungen anzeigen').'</a>'
            )
        )
    ), array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                'icon' => 'ausruf_small.gif',
                'text' => _('Sie können hier den Zugriff auf ein Plugin durch die Auswahl von Rollen beschränken.')
            ), array(
                'icon' => 'ausruf_small.gif',
                'text' => _('Wählen Sie z.B. "Evaluationsbeauftragte", so können alle Nutzer, die sich in der Rolle "Evaluationsbeauftragte" befinden, dieses Plugin sehen und nutzen.')
            )
        )
    )
);

$infobox = array('picture' => 'modules.jpg', 'content' => $infobox_content);
?>
