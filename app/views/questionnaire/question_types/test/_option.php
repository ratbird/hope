<li>
    <?= Assets::img("anfasser_24.png", array('title' => _("Antwort verschieben"), 'class' => "move")) ?>
    <? $questiondata = $vote->questiondata ? $vote->questiondata->getArrayCopy() : array() ?>
    <input type="checkbox"
           name="questions[<?= $vote->getId() ?>][questiondata][correctanswer][]"
           value="<?= $index + 1 ?>" title="<?= _("Ist diese Antwort korrekt?") ?>"
           <?= $forcecorrect || in_array($index + 1, (array) $questiondata['correctanswer']) ? " checked" : "" ?>>
    <input type="text"
           name="questions[<?= $vote->getId() ?>][questiondata][options][]"
           value="<?= htmlReady($option) ?>" placeholder="<?= _("Antwort ...") ?>"
           aria-label="<?= _("Geben Sie eine Antwortmöglichkeit zu der von Ihnen gestellten Frage ein.") ?>">
    <?= Icon::create("trash", "clickable")->asimg("20px", array('class' => "text-bottom delete", 'title' => _("Antwort löschen"))) ?>
    <?= Icon::create("add", "clickable")->asimg("20px", array('class' => "text-bottom add", 'title' => _("Antwort hinzufügen"))) ?>
</li>