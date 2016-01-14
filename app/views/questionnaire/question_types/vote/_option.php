<li>
    <?= Assets::img("anfasser_24.png", array('title' => _("Antwort verschieben"), 'class' => "move")) ?>
    <input type="text"
           name="questions[<?= $vote->getId() ?>][questiondata][options][]"
           value="<?= htmlReady($option) ?>" placeholder="<?= _("Antwort ...") ?>"
           aria-label="<?= _("Geben Sie eine Antwortmöglichkeit zu der von Ihnen gestellten Frage ein.") ?>">
    <?= Icon::create("trash", "clickable")->asimg("20px", array('class' => "text-bottom delete", 'title' => _("Antwort löschen"))) ?>
    <?= Icon::create("add", "clickable")->asimg("20px", array('class' => "text-bottom add", 'title' => _("Antwort hinzufügen"))) ?>
</li>