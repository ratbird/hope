<h1><?= _('Registrierte Konsumenten') ?></h1>

<? if (!empty($consumers)): ?>
<table class="default">
    <thead>
        <tr>
            <th><?= ('Aktiv') ?></th>
            <th><?= _('Name') ?></th>
            <th><?= _('Typ') ?></th>
            <th><?= _('Kontakt') ?></th>
            <th><?= _('Kommerziell') ?></th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
<? foreach ($consumers as $consumer): ?>
        <tr>
            <td id="<?= $consumer->id ?>">
                <a href="<?= $controller->url_for('admin/api/toggle', $consumer->id, $consumer->active ? 'off' : 'on') ?>">
                    <?= Assets::img('icons/16/blue/checkbox-' . ($consumer->active ? '' : 'un') . 'checked') ?>
                </a>
            </td>
            <td>
            <? if ($consumer->url): ?>
                <a href="<?= htmlReady($consumer->url) ?>" target="_blank">
                    <?= htmlReady($consumer->title) ?>
                </a>
            <? else: ?>
                <?= htmlReady($consumer->title) ?>
            <? endif; ?>
            </td>
            <td><?= $types[$consumer->type] ?: '&nbsp;' ?></td>
            <td>
                <a href="mailto:<?= htmlReady($consumer->email) ?>">
                    <?= htmlReady($consumer->contact) ?>
                </a>
            </td>
            <td><?= Assets::img('icons/16/blue/checkbox-' . ($consumer->commercial ? '' : 'un') . 'checked') ?></td>
            <td align="right">
                <a href="<?= $controller->url_for('admin/api/keys', $consumer->id) ?>"
                   data-lightbox="size=auto"
                   title="<?= htmlReady(sprintf(_('Schlüssel anzeigen für Applikation "%s"'), $consumer->title)) ?>">
                    <?= Assets::img('icons/16/blue/info-circle.png') ?>
                </a>
                <a href="<?= $controller->url_for('admin/api/edit', $consumer->id) ?>" title="<?= _('Applikation bearbeiten') ?>">
                    <?= Assets::img('icons/16/blue/edit.png') ?>
                </a>
                <a href="<?= $controller->url_for('admin/api/permissions', $consumer->id) ?>" title="<?= _('Zugriffsberechtigungen verwalten') ?>">
                    <?= Assets::img('icons/16/blue/admin.png') ?>
                </a>
                <a href="<?= $controller->url_for('admin/api/delete', $consumer->id) ?>"
                   title="<?= htmlReady(sprintf(_('Applikation "%s" entfernen'), $consumer->title)) ?>">
                    <?= Assets::img('icons/16/blue/trash.png') ?>
                </a>
            </td>
        </tr>
<? endforeach; ?>
    </tbody>
</table>
<? else: ?>
<p>
    <?= _('Es wurde noch keine Applikation registriert.') ?>
    <?= sprintf(_('Klicken Sie <a href="%s">hier</a>, um eine Applikation zu registrieren.'), $controller->url_for('admin/api/edit')) ?>
</p>
<? endif; ?>
