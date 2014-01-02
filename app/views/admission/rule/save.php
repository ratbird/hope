<div class="hover_box" id="rule_<?= $rule->getId() ?>">
    <span id="rule_data_<?= $rule->getId() ?>">
        <?= $via_ajax ? studip_utf8encode($rule->toString()) : $rule->toString() ?>
        <input type="hidden" name="rules[]" value="<?= htmlReady($via_ajax ? studip_utf8encode(serialize($rule)) : serialize($rule)) ?>"/>
    </span>
    <span class="hover_symbols" id="rule_actions_<?= $rule->getId() ?>">
        <a href="#" onclick="return STUDIP.Admission.configureRule('<?= get_class($rule) ?>', '<?= $controller->url_for('admission/rule/configure', get_class($rule), $rule->getId()) ?>')">
            <?= Assets::img('icons/16/blue/edit.png'); ?></a>
        <a href="#" onclick="return STUDIP.Dialogs.showConfirmDialog('<?= 
                    _('Soll die Anmelderegel wirklich gelöscht werden?') ?>', 
                    'javascript:STUDIP.Admission.removeRule(\'rule_<?= $rule->getId() ?>\', \'rules\')')">
            <?= Assets::img('icons/16/blue/trash.png'); ?></a>
    </span>
</div>
