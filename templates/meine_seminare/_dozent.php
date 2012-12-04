<?
# Lifter010: TODO
?>
<?= $colon ? ', ' : '' ?><a href="<?= URLHelper::getLink('dispatch.php/about', array('username' => $_dozent['username'])) ?>"><?= htmlReady($_dozent["Nachname"]) ?></a><? $this->colon = true; ?>
