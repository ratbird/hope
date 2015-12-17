<?php
# Lifter010: TODO
?>

<form method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <caption>
            <?= _('Sperrebenen f�r den Bereich:') ?> <?= $rule_type_names[$lock_rule_type]; ?>
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
        <? if(count($lock_rules) > 0) : ?>
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
                            <?= Icon::create('edit', 'clickable', ['title' => _('Diese Regel bearbeiten')])->asImg() ?>
                        </a>

                        <?
                        if ($rule->getUsage()) :?>
                            <? $msg = sprintf(_('Sie beabsichtigen die Ebene %s zu l�schen. Diese Ebene wird von %s Objekten benutzt. Soll sie trotzdem gel�scht werden?'),
                                $rule->name, $rule->getUsage()) ?>
                        <? else : ?>
                            <? $msg = sprintf(_('M�chten Sie die Ebene %s l�schen?'), $rule->name) ?>
                        <? endif ?>
                        <?= Icon::create('trash', 'clickable', ['title' => _('Diese Regel l�schen')])->asInput(array('data-confirm'=>$msg,'formaction'=>$controller->url_for('admin/lockrules/delete/'.$rule->lock_id))) ?>
                    </td>
                </tr>
            <? endforeach; ?>
        <? else :?>
            <tr>
                <td colspan="4" style="text-align: center">
                    <?=_('Keine Sperrebenen vorhanden')?>
                </td>
            </tr>
        <? endif?>
        </tbody>
    </table>
</form>


