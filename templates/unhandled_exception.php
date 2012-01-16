<?php
# Lifter010: DONE - no form elements in this page

require_once 'lib/classes/MessageBox.class.php';

include 'lib/include/html_head.inc.php';

$current_page = _('Fehler');
?>

<?= $this->render_partial('header', compact('current_page')) ?>

<div style="background-color: white; width: 70%; padding: 1em; margin: auto;">
    <?= MessageBox::exception(_('Fehler! Bitte wenden Sie sich an Ihren Systemadministrator.'), array(htmlentities($exception->getMessage()))) ?>
    <p>
      <?= _('Zurück zur') ?> <a href="<?= URLHelper::getLink('index.php') ?>"><?= _('Startseite') ?></a>
    </p>
</div>

<? include 'lib/include/html_end.inc.php'; ?>
