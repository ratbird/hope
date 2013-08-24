<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<?= $this->render_partial('admin/role/status_message') ?>

<? if ($delete_role): ?>
    <?= $GLOBALS['template_factory']->render('shared/question',
        array('question' => sprintf(_('Wollen Sie wirklich die Rolle "%s" l�schen?'), $roles[$delete_role]->getRolename()),
              'approvalLink' => $controller->url_for('admin/role/remove_role', $delete_role).'?ticket='.get_ticket(),
              'disapprovalLink' => $controller->url_for('admin/role'))) ?>
<? endif ?>

<table class="default">
<caption>
    <?= _('Vorhandene Rollen') ?>
</caption>
<thead>
    <tr>
        <th><?= _('Name') ?></th>
        <th style="text-align: right;"><?= _('Benutzer') ?></th>
        <th style="text-align: right;"><?= _('Plugins') ?></th>
        <th></th>
    </tr>
</thead>
<tbody>
    <? foreach ($roles as $role): ?>
        <? $role_id = $role->getRoleid() ?>
        <tr>
            <td>
                <a href="<?= $controller->url_for('admin/role/show_role', $role_id) ?>">
                    <?= htmlReady($role->getRolename()) ?>
                    <? if ($role->getSystemtype()): ?>[<?= _('Systemrolle') ?>]<? endif ?>
                </a>
            </td>
            <td style="text-align: right;">
                <?= $stats[$role_id]['users'] ?>
            </td>
            <td style="text-align: right;">
                <?= $stats[$role_id]['plugins'] ?>
            </td>
            <td class="actions">
                <? if (!$role->getSystemtype()): ?>
                    <a href="<?= $controller->url_for('admin/role/ask_remove_role', $role_id) ?>">
                        <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Rolle l�schen'))) ?>
                    </a>
                <? endif ?>
            </td>
        </tr>
    <? endforeach ?>
</tbody>
</table>

<h3>
    <?= _('Neue Rolle anlegen') ?>
</h3>

<form action="<?= $controller->url_for('admin/role/create_role') ?>" method="POST">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="ticket" value="<?= get_ticket() ?>">
    Name: <input type="text" name="name" size="25" value="">
    <?= Button::create(_('Anlegen'), 'createrolebtn', array('title' => _('Rolle anlegen')))?>
</form>

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
                'text' => _('Zum Erstellen neuer Rollen geben Sie den Namen ein und klicken Sie auf "anlegen".')
            ), array(
                "icon" => "icons/16/black/info.png",
                'text' => _('Zum L�schen von Rollen klicken Sie auf das M�lleimersymbol. Systemrollen k�nnen jedoch nicht gel�scht werden.')
            )
        )
    )
);

$infobox = array('picture' => 'infobox/modules.jpg', 'content' => $infobox_content);
?>
