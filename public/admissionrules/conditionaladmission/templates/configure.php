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
        <?php foreach ($rule->getConditions() as $condition) { ?>
            <div class="condition" id="condition_<?= $condition->getId() ?>">
                <?= $condition->toString() ?>
                <a href="#" onclick="return STUDIP.UserFilter.removeConditionField($(this).parent())"
                    class="conditionfield_delete">
                    <?= Assets::img('icons/16/blue/trash.png'); ?></a>
                <input type="hidden" name="conditions[]" value="<?= htmlentities(serialize($condition), ENT_COMPAT | ENT_HTML401, 'iso-8859-1') ?>"/>
            </div>
        <?php } ?>
    </div>
    <?php } ?>
</div>
<br/>
<a href="<?= URLHelper::getURL('dispatch.php/userfilter/filter/configure/condadmission_conditions') ?>" onclick="return STUDIP.UserFilter.configureCondition('condition', '<?= URLHelper::getURL('dispatch.php/userfilter/filter/configure/condadmission_conditions') ?>')">
    <?= Assets::img('icons/16/blue/add.png', array(
        'alt' => _('Bedingung hinzuf�gen'),
        'title' => _('Bedingung hinzuf�gen'))) ?><?= _('Bedingung hinzuf�gen') ?></a>
<br/>