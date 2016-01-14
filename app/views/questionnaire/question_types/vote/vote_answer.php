<h3>
    <?= Icon::create((is_a($vote, "Test") ? "test" : "vote"), "info")->asimg("20px", array('class' => "text-bottom")) ?>
    <?= formatReady($vote['questiondata']['question']) ?>
</h3>

<?
$questiondata = $vote['questiondata']->getArrayCopy();
$map = range(0, count($questiondata['options']) - 1);
if ($questiondata['randomize']) {
    shuffle($map);
}
?>

<ul class="clean">
    <? foreach ($map as $index) : ?>
        <li>
            <label>
                <? $answer = $vote->getMyAnswer() ?>
                <? $answerdata = $answer['answerdata'] ? $answer['answerdata']->getArrayCopy() : array() ?>
                <? if ($questiondata['multiplechoice']) : ?>
                    <input type="checkbox" name="answers[<?= $vote->getId() ?>][answerdata][answers][]" value="<?= $index + 1 ?>"<?= in_array($index + 1, (array) $answerdata['answers']) ? " checked" : "" ?>>
                <? else : ?>
                    <input type="radio" name="answers[<?= $vote->getId() ?>][answerdata][answers]" value="<?= $index + 1 ?>"<?= $index + 1 == $answerdata['answers'] ? " checked" : "" ?>>
                <? endif ?>
                <?= formatReady($questiondata['options'][$index]) ?>
            </label>
        </li>
    <? endforeach ?>
</ul>
