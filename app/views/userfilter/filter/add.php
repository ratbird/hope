<div class="condition" id="<?= $condition->getId() ?>">
    <?= $condition->toString() ?>
    <input type="hidden" name="conditions[]" value="<?= htmlReady(serialize($condition)) ?>"/>
    <a href="#" onclick="return STUDIP.UserFilter.removeConditionField($(this).parent())"
            class="conditionfield_delete">
        <?= Icon::create('trash', 'clickable')->asImg(); ?></a>
</div>