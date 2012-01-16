<?php
# Lifter010: DONE - no form elements in this page

require_once 'lib/classes/MessageBox.class.php';

include 'lib/include/html_head.inc.php';

$current_page = _('Zugriff verweigert');
?>

<?= $this->render_partial('header', compact('current_page')) ?>

<div style="background-color: white; width: 70%; padding: 1em; margin: auto;">
    <?= MessageBox::exception(_('Zugriff verweigert'), array(htmlentities($exception->getMessage()))) ?>
    <p>
      <?= _('Zurück zur') ?> <a href="<?= URLHelper::getLink('index.php') ?>"><?= _('Startseite') ?></a>
    </p>
</div>

<? include 'lib/include/html_end.inc.php'; ?>
