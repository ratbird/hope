<h2 class="hide-in-dialog"><?= _('Bearbeiten von Konfigurationsparameter') ?></h2>
<form action="<?= $controller->url_for('admin/configuration/edit_configuration?id=' . $config['field']) ?>" method="post" data-dialog>
    <?= CSRFProtection::tokenTag() ?>
<table class="default">
    <tbody>
        <tr>
            <td><?= _('Name') ?>:</td>
            <td><?= htmlReady($config['field']) ?></td>
        </tr>
        <tr>
            <td>
                <label for="item-value"><?= _('Inhalt') ?>:</label>
            </td>
            <td>
                <?= $this->render_partial('admin/configuration/type-edit.php', $config) ?>
            </td>
        </tr>
        <tr>
            <td>
                <label for="comment"><?= _('Kommentar') ?>:</label>
            </td>
            <td>
                <textarea cols="80" rows="2" name="comment" id="comment"><?= htmlReady($config['comment']) ?></textarea>
            </td>
        </tr>
        <tr>
            <td><?= _('Standard') ?>:</td>
            <td>
            <? if ($config['is_default'] === '1'): ?>
                <?= Assets::img('icons/16/black/checkbox-checked.png', tooltip2(_('Ja'))) ?>
            <? elseif ($config['is_default'] === '0'): ?>
                <?= Assets::img('icons/16/black/checkbox-checked.png', tooltip2(_('Nein'))) ?>
            <? elseif ($config['is_default'] === null): ?>
                <em>- <?= _('kein Eintrag vorhanden') ?> -</em>
            <? endif; ?>
            </td>
        </tr>
        <tr>
            <td><?= _('Typ') ?></td>
            <td><?= htmlReady($config['type']) ?></td>
        </tr>
        <tr>
            <td><?= _('Bereich') ?>:</td>
            <td><?= htmlReady($config['range']) ?></td>
        </tr>
        <tr>
            <td><label for="section"><?= _('Kategorie') ?>:</label></td>
            <td>
                <select name= "section" onchange="$(this).next('input').val( $(this).val() );">
                <? foreach (array_keys($allconfigs) as $section): ?>
                    <option <? if ($config['section'] === $section) echo 'selected'; ?>>
                        <?= htmlReady($section) ?>
                    </option>
                <? endforeach; ?>
                </select>
                <input type="text" name="section_new" id="section">
                (<em><?= _('Bitte die neue Kategorie eingeben')?></em>)
            </td>
        </tr>
    </tbody>
    <tfoot data-dialog-button>
        <tr>
            <td colspan="2">
                <?= Studip\Button::createAccept(_('Übernehmen')) ?>
                <?= Studip\LinkButton::createCancel(_('Abbrechen'),
                    $controller->url_for('admin/configuration/configuration/' . $config['section'])) ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>