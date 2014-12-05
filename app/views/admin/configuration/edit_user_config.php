<h2 class="hide-in-dialog">
    <?= _('Bearbeiten von Konfigurationsparameter für den Nutzer: ') ?>
    <?= htmlReady(User::find($user_id)->getFullname()) ?>
</h2>

<form action="<?= $controller->url_for('admin/configuration/edit_user_config/' . $user_id . '?id=' . $field) ?>" method="post" data-dialog>
    <?= CSRFProtection::tokenTag() ?>

<table class="default">
    <tbody>
        <tr>
            <td><?= _('Name:') ?> (<em>field</em>)</td>
            <td><?= htmlReady($field) ?></td>
        </tr>
        <tr>
            <td>
                <label for="item-value">
                    <?= _('Inhalt:') ?> (<em>value</em>)
                </label>
            </td>
            <td>
                <?= $this->render_partial('admin/configuration/type-edit.php', $config) ?>
            </td>
        </tr>
        <tr>
            <td><?= _('Beschreibung:') ?> (<em>description</em>)</td>
            <td><?= htmlReady($config['description']) ?></td>
        </tr>
    </tbody>
    <tfoot data-dialog-button>
        <tr>
            <td colspan="2">
                <?= Studip\Button::createAccept(_('Speichern')) ?>
                <?= Studip\LinkButton::createCancel(_('Abbrechen'),
                        $controller->url_for('admin/configuration/user_configuration', compact('user_id'))) ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>
