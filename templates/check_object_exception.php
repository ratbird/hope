<?php
# Lifter010: DONE - no form elements in this page

require_once 'lib/classes/MessageBox.class.php';

include 'lib/include/html_head.inc.php';

$current_page = _('Kein Objekt gewählt')
?>

<?= $this->render_partial('header', compact('current_page')) ?>

<div style="background-color: white; width: 70%; padding: 1em; margin: auto;">
    <?= MessageBox::exception(htmlentities($exception->getMessage()), array(
            _('Dieser Teil des Systems kann nur genutzt werden, wenn Sie vorher ein Objekt (Veranstaltung oder Einrichtung) gewählt haben.'),
            sprintf(_('Dieser Fehler tritt auch auf, wenn Ihre Session abgelaufen ist. Bitte nutzen Sie in diesem Fall den untenstehenden Link, um zurück zur Anmeldung zu gelangen.')))) ?>

    <? if ($last_edited = Request::get('content') . Request::get('description') . Request::get('body')) : ?>
        <p>
            <?= _('Folgender von Ihnen eingegebene Text konnte nicht gespeichert werden:') ?>
        </p>
        <div class="table_row_even" style="padding: 5px; border: 1px solid;">
            <?= htmlentities($last_edited) ?>
        </div>
    <? endif ?>
    <p>
      <?= _('Zurück zur') ?> <a href="<?= URLHelper::getLink('index.php') ?>"><?= _('Startseite') ?></a>
    </p>
</div>

<? include 'lib/include/html_end.inc.php'; ?>
