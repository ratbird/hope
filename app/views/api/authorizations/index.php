<? use Studip\Button, Studip\LinkButton; ?>

<h1><?= _('Applikationen') ?></h1>
<? if (empty($consumers)): ?>
<p><?= _('Sie haben noch keinen Apps Zugriff auf Ihren Account gewährt.') ?></p>
<? else: ?>
<table class="oauth-apps default">
    <thead>
        <tr>
            <th><?= _('Name') ?></th>
            <th>&nbsp;</th>
    </thead>
    <tbody>
    <? foreach ($consumers as $consumer): ?>
        <tr>
            <td>
                <h2>
                <? if ($consumer->url): ?>
                    <a href="<?= htmlReady($consumer->url) ?>" target="_blank">
                        <?= htmlReady($consumer->title) ?>
                    </a>
                <? else: ?>
                    <?= htmlReady($consumer->title) ?>
                <? endif; ?>
                <? if ($type = $types[$consumer->type]): ?>
                    <small>(<?= htmlReady($type) ?>)</small>
                <? endif; ?>
                </h2>
            <? if ($consumer->description): ?>
                <p><?= htmlReady($consumer->description) ?></p>
            <? endif; ?>
            </td>
            <td>
                <?= LinkButton::createCancel(_('App entfernen'),
                                             $controller->url_for('api/authorizations/revoke', $consumer->id),
                                             array('data-behaviour' => 'confirm')) ?>
            </td>
        </tr>
<? endforeach; ?>
    </tbody>
</table>
<? endif; ?>