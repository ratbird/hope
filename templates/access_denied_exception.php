<?php
$current_page = _('Zugriff verweigert');
?>
<?= MessageBox::exception(_('Zugriff verweigert'), array(htmlReady($exception->getMessage()))) ?>
<p>
    <?= sprintf(_('Zur�ck zur %sStartseite%s'),
                sprintf('<a href="%s">', URLHelper::getLink('index.php')),
                '</a>') ?>
</p>
