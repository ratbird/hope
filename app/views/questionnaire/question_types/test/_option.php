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
    <?= Assets::img("icons/20/blue/trash", array('title' => _("Antwort löschen"), 'class' => "delete")) ?>
    <?= Assets::img("icons/20/blue/add", array('title' => _("Antwort hinzufügen"), 'class' => "add")) ?>
</li>