<?php
# Lifter010: DONE - no form elements in this page

require_once 'lib/classes/MessageBox.class.php';

include 'lib/include/html_head.inc.php';

$current_page = _('Fehler');

$title = _('Fehler! Bitte wenden Sie sich an Ihren Systemadministrator.');
$details = array(htmlentities($exception->getMessage()));

if (Studip\ENV == 'development') {
    $title = "Houston, we've got a problem.";
    $details = array();

    do {
        $details[] = 'Type: ' . get_class($exception);
        $details[] = 'Message: '.htmlentities($exception->getMessage());
        $details[] = 'Code: ' . $exception->getCode();

        $trace = sprintf("#$ %s(%u)\n", $exception->getFile(), $exception->getLine())
               . $exception->getTraceAsString();
        $trace = str_replace($GLOBALS['STUDIP_BASE_PATH'] . '/', '', $trace);
        $details[] = 'Stack trace:<br>'
                   . nl2br(htmlentities($trace))
                   . '<br><br>'; // Separator from potential previous exception
    } while ($exception = $exception->getPrevious());
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
