<?php if ($steps) : ?>
    <?php if (!$has_enabled) : ?>
    <?= MessageBox::info(_('Es gibt keine aktiven Schritte f�r den Anlegeassistenten!')) ?>
    <?php endif ?>
<table class="default">
    <caption>
        <?= _('Vorhandene Schritte im Anlegeassistenten f�r Veranstaltungen') ?>
        <span class="actions">
            <a href="<?= $controller->url_for('admin/coursewizardsteps/edit') ?>" data-dialog="size=auto">
                <?= Icon::create('add', 'clickable')->asImg() ?></a>
        </span>
    </caption>
    <thead>
        <th width="30%"><?= _('Name') ?></th>
        <th width="30%"><?= _('PHP-Klasse') ?></th>
        <th width="5%"><?= _('Nummer') ?></th>
        <th width="5%"><?= _('aktiv?') ?></th>
        <th width="10%"><?= _('Aktionen') ?></th>
    </thead>
    <tbody>
    <?php foreach ($steps as $step) : ?>
        <tr>
            <td><?= htmlReady($step->name) ?></td>
            <td><?= htmlReady($step->classname) ?></td>
            <td><?= $step->number ?></td>
            <td><?= $step->enabled ? Icon::create('checkbox-checked', 'info')->asImg() :
                    Icon::create('checkbox-unchecked', 'info')->asImg() ?></td>
            <td>
                <a href="<?= $controller->url_for('admin/coursewizardsteps/edit', $step->id) ?>" data-dialog="size=auto">
                    <?= Icon::create('edit', 'clickable')->asImg() ?></a>
                <a href="<?= $controller->url_for('admin/coursewizardsteps/ask_delete',
                    $step->id) ?>" data-dialog="size=auto">
                <?= Icon::create('trash', 'clickable')->asImg() ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
<?php else : ?>
<?= MessageBox::error(_('Es sind keine Schritte f�r den Veranstaltungsanlegeassistenten registriert!')); ?>
<?php endif ?>