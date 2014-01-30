<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<?= $this->render_partial('admin/role/status_message') ?>

<h3>
    <?= _('Rollenverwaltung f�r Plugins') ?>
</h3>

<form action="<?= $controller->url_for('admin/role/assign_plugin_role') ?>" style="margin-bottom: 1em;" method="POST">
    <?= CSRFProtection::tokenTag() ?>
    <select name="pluginid" style="min-width: 300px;">
        <? foreach ($plugins as $plugin): ?>
            <option value="<?= $plugin['id'] ?>" <?= $plugin['id'] == $pluginid ? 'selected' : '' ?>>
                <?= htmlReady($plugin['name']) ?>
            </option>
        <? endforeach ?>
    </select>

    <?= Button::create(_('Ausw�hlen'), 'select', array('title' => _('Plugin ausw�hlen')))?>
</form>

<? if ($pluginid): ?>
    <form action="<?= $controller->url_for('admin/role/save_plugin_role', $pluginid) ?>" method="POST">
        <?= CSRFProtection::tokenTag() ?>
        <input type="hidden" name="ticket" value="<?= get_ticket() ?>">
        <table class="default nohover">
            <tr>
                <th style="text-align: center;"><?= _('Gegenw�rtig zugewiesene Rollen') ?></th>
                <th></th>
                <th><?= _('Verf�gbare Rollen') ?></th>
            </tr>
            <tr class="table_row_even">
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
                    <?= Assets::input("icons/16/yellow/arr_2left.png", array('type' => "image", 'class' => "middle", 'name' => "assign_role", 'title' => _('Markierte Rollen dem Plugin zuweisen'))) ?>
                    <br>
                    <br>
                    <?= Assets::input("icons/16/yellow/arr_2right.png", array('type' => "image", 'class' => "middle", 'name' => "remove_role", 'title' => _('Markierte Rollen entfernen'))) ?>
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
                'icon' => 'icons/16/black/roles.png',
                'text' => '<a href="'.$controller->url_for('admin/role').'">'._('Rollen verwalten').'</a>'
            ), array(
                'icon' => 'icons/16/black/person.png',
                'text' => '<a href="'.$controller->url_for('admin/role/assign_role').'">'._('Benutzerzuweisungen bearbeiten').'</a>'
            ), array(
                'icon' => 'icons/16/black/plugin.png',
                'text' => '<a href="'.$controller->url_for('admin/role/assign_plugin_role').'">'._('Pluginzuweisungen bearbeiten').'</a>'
            ), array(
                'icon' => 'icons/16/black/log.png',
                'text' => '<a href="'.$controller->url_for('admin/role/show_role').'">'._('Rollenzuweisungen anzeigen').'</a>'
            )
        )
    ), array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                "icon" => "icons/16/black/info.png",
                'text' => _('Sie k�nnen hier den Zugriff auf ein Plugin durch die Auswahl von Rollen beschr�nken.')
            ), array(
                "icon" => "icons/16/black/info.png",
                'text' => _('W�hlen Sie z.B. "Evaluationsbeauftragte", so k�nnen alle Nutzer, die sich in der Rolle "Evaluationsbeauftragte" befinden, dieses Plugin sehen und nutzen.')
            )
        )
    )
);

$infobox = array('picture' => 'infobox/modules.jpg', 'content' => $infobox_content);
?>
