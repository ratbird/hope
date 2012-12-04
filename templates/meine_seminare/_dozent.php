<?
# Lifter010: TODO
?>
<?= $colon ? ', ' : '' ?><a href="<?= URLHelper::getLink('dispatch.php/profile', array('username' => $_dozent['username'])) ?>"><?= htmlReady($_dozent["Nachname"]) ?></a><? $this->colon = true; ?>
