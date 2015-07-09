<?php
# Lifter010: TODO
?>

<form method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <caption>
            <?= _("Sperrebenen für den Bereich:") ?> <?= $rule_type_names[$lock_rule_type]; ?>
        </caption>
        <colgroup>
            <col width="30%">
            <col width="50%">
            <col width="20%">
        </colgroup>
        <thead>
        <tr>
            <th><?= _('Name') ?></th>
            <th><?= _('Beschreibung') ?></th>
            <th><?= _('Besitzer') ?></th>
            <th><?= _('Aktionen') ?></th>
        </tr>
        </thead>
        <tbody>
        <? foreach ($lock_rules as $rule): ?>
            <tr>
                <td>
                    <?= htmlReady($rule->name) ?>
                </td>
                <td>
                    <?= htmlReady(my_substr($rule->description, 0, 100)) ?>
                </td>
                <td>
                    <?= htmlReady($rule->user_id ? get_fullname($rule->user_id) : '') ?>
                </td>
                <td class="actions">
                    <a href="<?= $controller->url_for('admin/lockrules/edit/' . $rule->lock_id) ?>">
                        <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Diese Regel bearbeiten'))) ?>
                    </a>

                    <?
                    if ($rule->getUsage()) :?>
                        <? $msg = sprintf(_("Sie beabsichtigen die Ebene %s zu löschen. Diese Ebene wird von %s Objekten benutzt. Soll sie trotzdem gelöscht werden?"),
                            $rule->name, $rule->getUsage()) ?>
                    <? else : ?>
                        <? $msg = sprintf(_("Möchten Sie die Ebene %s löschen?"), $rule->name) ?>
                    <? endif ?>
                    <?= Assets::input('icons/16/blue/trash.png',
                        tooltip2(_('Diese Regel löschen')) +
                        array('data-confirm' => $msg,
                              'formaction'   => $controller->url_for('admin/lockrules/delete/' . $rule->lock_id)
                        )) ?>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>
    </table>
</form>


