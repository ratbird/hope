<?php
$current_page = _('Zugriff verweigert');
?>
<?= MessageBox::exception(_('Zugriff verweigert'), array(htmlReady($exception->getMessage()))) ?>
    <p>
      <?= _('Zur�ck zur') ?> <a href="<?= URLHelper::getLink('index.php') ?>"><?= _('Startseite') ?></a>
    </p>
