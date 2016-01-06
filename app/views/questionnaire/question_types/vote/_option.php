<li>
    <?= Assets::img("anfasser_24.png", array('title' => _("Antwort verschieben"), 'class' => "move")) ?>
    <input type="text"
           name="questions[<?= $vote->getId() ?>][questiondata][options][]"
           value="<?= htmlReady($option) ?>" placeholder="<?= _("Antwort ...") ?>"
           aria-label="<?= _("Geben Sie eine Antwortm�glichkeit zu der von Ihnen gestellten Frage ein.") ?>">
    <?= Assets::img("icons/20/blue/trash", array('title' => _("Antwort l�schen"), 'class' => "delete")) ?>
    <?= Assets::img("icons/20/blue/add", array('title' => _("Antwort hinzuf�gen"), 'class' => "add")) ?>
</li>