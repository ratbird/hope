<?= $this->render_partial('course/timesrooms/_cancel_form.php', compact('termin'))?>
<footer>
    <?= Studip\Button::createAccept(_('Übernehmen'), 'editDeletedSingleDate', array(
            'formaction'  => $controller->url_for('course/timesrooms/saveComment/' . $termin->id),
            'data-dialog' => 'size=big'
    )) ?>
    <?= Studip\LinkButton::createCancel(_('Abbrechen'), '?#' . $termin_id) ?>
</footer>
