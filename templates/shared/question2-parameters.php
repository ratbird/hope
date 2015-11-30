<? foreach ($parameters as $key => $value): ?>
    <? if (is_array($value)): ?>
        <?= $this->render_partial('shared/question2-parameters.php', array(
                'parameters' => $value,
                'fieldname'  => $key . '[]',
        )) ?>
    <? else: ?>
        <input type="hidden" name="<?= $fieldname ?: $key ?>" value="<?= $value ?>">
    <? endif; ?>
<? endforeach; ?>
