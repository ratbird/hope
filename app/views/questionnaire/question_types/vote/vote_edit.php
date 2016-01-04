<label>
    <?= _("Frage") ?>
    <textarea name="questions[<?= $vote->getId() ?>][questiondata][question]" class="size-l"><?= htmlReady($vote['questiondata']['question']) ?></textarea>
</label>

<ol class="clean options" data-optiontemplate="<?= htmlReady($this->render_partial("questionnaire/question_types/vote/_option.php", array('vote' => $vote, 'option' => ""))) ?>"><?
    if ($vote['questiondata']['options']) {
        foreach ($vote['questiondata']['options'] as $option) {
            echo $this->render_partial("questionnaire/question_types/vote/_option.php", array('vote' => $vote, 'option' => $option));
        }
    }
    echo $this->render_partial("questionnaire/question_types/vote/_option.php", array('vote' => $vote, 'option' => ""));
?></ol>

<label>
    <input type="checkbox" name="questions[<?= $vote->getId() ?>][questiondata][multiplechoice]" value="1"<?= $vote->isNew() || $vote['questiondata']['multiplechoice'] ? " checked" : "" ?>>
    <?= _("Mehrere Antworten sind erlaubt.") ?>
</label>

<label>
    <input type="checkbox" name="questions[<?= $vote->getId() ?>][questiondata][randomize]" value="1"<?= $vote['questiondata']['randomize'] ? " checked" : "" ?>>
    <?= _("Antworten den Teilnehmenden zufällig präsentieren.") ?>
</label>

<div style="display: none" class="delete_question"><?= _("Diese Antwortmöglichkeit wirklich löschen?") ?></div>

<script>
    jQuery(function () {
        jQuery(".options").sortable({
            "axis": "y",
            "containment": "parent",
            "handle": ".move"
        });
    });
</script>