<form method="post" action="<?= $controller->url_for('course/timesrooms/saveStack/' . $cycle_id, $editParams) ?>" class="studip-form" data-dialog="size=big">
    <input type="hidden" name="method" value="preparecancel">

    <?= $this->render_partial('course/timesrooms/_cancel_form.php') ?>

    <footer data-dialog-button>
        <?= Studip\Button::createAccept(_('Übernehmen'), 'cancel') ?>
    <? if (Request::get('fromDialog') == 'true'): ?>
        <?= Studip\LinkButton::create(_('Zurück zur Übersicht'), $controller->url_for('course/timesrooms/index'), array('data-dialog' => 'size=big')) ?>
    <? endif; ?>
    </footer>
</form>
