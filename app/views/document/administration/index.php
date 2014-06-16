<? use Studip\Button, Studip\LinkButton; ?>

<form action="<?= $controller->url_for('document/administration/filter') ?>" method="post" class="studip_form">
    <fieldset>
        <legend><?= _('Vorhandene Konfigurationen') ?></legend>

        <label for="showFilter">
            <?=_('Art der Konfiguration:')?>
            <select name="showFilter" id="showFilter">
                <option value="all" <? if ($_SESSION['document_config_filter'] == 'all') echo 'selected'; ?>>
                    <?= _('Alle') ?>
                </option>
                <option value="group" <? if ($_SESSION['document_config_filter'] == 'group') echo 'selected'; ?>>
                    <?= _('Gruppe') ?>
                </option>
                <option value="individual" <? if ($_SESSION['document_config_filter'] == 'individual') echo 'selected'; ?>>
                    <?= _('Individuell') ?>
                </option>
            </select>
        </label>

        <?= Button::create(_('Filtern'), 'filter') ?>
    </fieldset>
</form>

<!--show configurations-->
<table class="default">
    <colgroup>
        <col>
        <col width="15%">
        <col width="10%">
        <col width="20%">
        <col width="10%">
        <col width="15%">
        <col width="48px">
    </colgroup>
    <thead>
        <tr>
            <th><?= _('Konfiguration für') ?></th>
            <th><?= _('Max. Uploadgröße') ?></th>
            <th><?= _('Nutzerquota') ?></th>
            <th><?= _('Untersagte Dateitypen') ?></th>
            <th><?= _('gesperrt')?></th>
            <th><?= _('Upload deaktiviert') ?></th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($viewData['configs'] as $index => $config): ?>
        <tr id="cfg_<?= $index ?: 0 ?>">
            <td><?= htmlReady($config['name']) ?></td>
            <td><?= relsize($config['upload_quota'], false) ?></td>
            <td><?= relsize($config['quota'], false) ?></td>
            <td>
            <? if(!empty($config['types'])): ?>
                <? foreach($config['types'] as $typ): ?>
                    <?= htmlReady($typ['type']) ?>
                <? endforeach; ?>
            <? endif; ?>
            </td>
            <td>
            <? if($config['closed']): ?>
                <a href="<?= $controller->url_for('document/administration/activateDocumentArea', $config['id']) ?>" data-behaviour="ajax-toggle"> 
                    <?= Assets::img('icons/16/blue/checkbox-checked.png', tooltip2(_('Dateibereich öffnen'))) ?>
                </a>
            <? else : ?>
                <a data-dialog href="<?= $controller->url_for('document/administration/deactivateDocumentArea', $config['id']) ?>">
                    <?= Assets::img('icons/16/blue/checkbox-unchecked.png', tooltip2(_('Dateibereich sperren'))) ?>
                </a>
            <? endif; ?>
            </td>
            <td>
            <? if($config['forbidden']): ?>
                <a href="<?= $controller->url_for('document/administration/activateUpload', $config['id']) ?>" data-behaviour="ajax-toggle">
                    <?= Assets::img('icons/16/blue/checkbox-checked.png', tooltip2(_('Upload aktivieren'))) ?>
                </a>
            <? else : ?>
                <a href="<?= $controller->url_for('document/administration/deactivateUpload', $config['id']) ?>" data-behaviour="ajax-toggle">
                    <?= Assets::img('icons/16/blue/checkbox-unchecked.png', tooltip2(_('Upload deaktivieren'))) ?>
                </a>
            <? endif;?>
            </td>
            <td class="actions">
                <a data-dialog href="<?= $controller->url_for('document/administration/edit/'.$config['id']) ?>">
                    <?= Assets::img('icons/16/blue/edit.png', tooltip2(_('Konfiguration bearbeiten'))) ?>
                </a>
            <? if($config['name'] != 'default'): ?>
                <a href="<?= $controller->url_for('document/administration/delete/'.$config['id']) ?>">
                    <?= Assets::img('icons/16/blue/trash.png', tooltip2(_('Konfiguration löschen'))) ?>
                </a>
            <? endif; ?>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
</table>
