<?php
require_once 'lib/classes/MessageBox.class.php';
require_once 'lib/visual.inc.php';

include 'lib/include/html_head.inc.php';

$current_page = _("Zugriff verweigert");
?>

<?= $this->render_partial('header', compact('current_page')) ?>

<div style="index_box blank">
    <?= MessageBox::exception(_("Zugriff verweigert"), array(htmlentities($exception->getMessage()))) ?>
    <p>
      <?= _("Zurück zur") ?> <a href="<?= URLHelper::getLink('index.php') ?>"><?= _("Startseite") ?></a>
    </p>
</div>

<? include 'lib/include/html_end.inc.php'; ?>
