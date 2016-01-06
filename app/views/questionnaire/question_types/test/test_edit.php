<label>
    <?= _("Frage") ?>
    <textarea name="questions[<?= $vote->getId() ?>][questiondata][question]" class="size-l"><?=
        isset($vote['questiondata']['question']) ? htmlReady($vote['questiondata']['question']) : ""
        ?></textarea>
</label>


<ol class="clean options" data-optiontemplate="<?= htmlReady($this->render_partial("questionnaire/question_types/test/_option.php", array('vote' => $vote, 'option' => ""))) ?>"><?
    if (isset($vote['questiondata']['options'])) {
        foreach ($vote['questiondata']['options'] as $index => $option) {
            echo $this->render_partial("questionnaire/question_types/test/_option.php", array('vote' => $vote, 'option' => $option, 'index' => $index));
        }
    }
    echo $this->render_partial("questionnaire/question_types/test/_option.php", array('vote' => $vote, 'option' => "", 'index' => $index + 1, 'forcecorrect' => (!isset($vote['questiondata']['options']) || (count($vote['questiondata']['options']) === 0))));
?></ol>

<div style="padding-left: 13px; margin-bottom: 20px;">
    <?= tooltipIcon(_("Wählen Sie über die Auswahlboxen aus, welche Antwortmöglichkeit korrekt ist.")) ?>
</div>

<label>
    <input type="checkbox" name="questions[<?= $vote->getId() ?>][questiondata][multiplechoice]" value="1"<?= $vote->isNew() || $vote['questiondata']['multiplechoice'] ? " checked" : "" ?>>
    <?= _("Mehrere Antworten sind erlaubt.") ?>
</label>

<label>
    <input type="checkbox" name="questions[<?= $vote->getId() ?>][questiondata][randomize]" value="1"<?= !isset($vote['questiondata']['randomize']) || $vote['questiondata']['randomize'] ? " checked" : "" ?>>
    <?= _("Antworten den Teilnehmenden zufällig präsentieren.") ?>
</label>

<div style="display: none" class="delete_question"><?= _("Diese Antwortmöglichkeit wirklich löschen?") ?></div>

<script>
    jQuery(function () {
        jQuery(".options").sortable({
            "axis": "y",
            "containment": "parent",
            "handle": ".move",
            "update": function () {
                STUDIP.Questionnaire.Test.updateCheckboxValues();
            }
        });
    });
</script>