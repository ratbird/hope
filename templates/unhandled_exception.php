<?php
# Lifter010: DONE - no form elements in this page

include 'lib/include/html_head.inc.php';

$current_page = _('Fehler');

$title = _('Fehler! Bitte wenden Sie sich an Ihren Systemadministrator.');
$details = array(htmlentities($exception->getMessage()));

if (Studip\ENV == 'development') {
    $title = "Houston, we've got a problem.";
    $details = array(display_exception($exception, true, true));
}
?>

<?= $this->render_partial('header', compact('current_page')) ?>

<div style="background-color: white; width: 70%; padding: 1em; margin: auto;">
    <?= MessageBox::exception($title, $details) ?>
    <p>
      <?= _('Zurück zur') ?> <a href="<?= URLHelper::getLink('index.php') ?>"><?= _('Startseite') ?></a>
    </p>
</div>

<? include 'lib/include/html_end.inc.php'; ?>
