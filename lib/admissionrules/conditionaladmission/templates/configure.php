<h3><?= $rule->getName() ?></h3>
<?= $tpl ?>
<br/>
<label for="conditionlist" class="caption">
    <?= _('Anmeldebedingungen') ?>:
</label>
<div id="condadmission_conditions">
    <?php if (!$rule->getConditions()) { ?>
    <span class="nofilter">
        <i><?= _('Sie haben noch keine Bedingungen festgelegt.'); ?></i>
    </span>
    <?php } else { ?>
    <div class="userfilter">
        <?php foreach ($rule->getConditions() as $condition) {
            $condition->show_user_count = true; ?>
            <div class="condition" id="condition_<?= $condition->getId() ?>">
                <?= $condition->toString() ?>
                <a href="#" onclick="return STUDIP.UserFilter.removeConditionField($(this).parent())"
                    class="conditionfield_delete">
                    <?= Icon::create('trash', 'clickable')->asImg(); ?></a>
                <input type="hidden" name="conditions[]" value="<?= htmlReady(serialize($condition)) ?>"/>
            </div>
        <?php } ?>
    </div>
    <?php } ?>
</div>
<br/>
<a href="<?= URLHelper::getURL('dispatch.php/userfilter/filter/configure/condadmission_conditions') ?>" onclick="return STUDIP.UserFilter.configureCondition('condition', '<?= URLHelper::getURL('dispatch.php/userfilter/filter/configure/condadmission_conditions') ?>')">
    <?= Icon::create('add', 'clickable', ['title' => _('Bedingung hinzufügen')])->asImg(16, ["alt" => _('Bedingung hinzufügen')]) ?><?= _('Bedingung hinzufügen') ?></a>
<br/>