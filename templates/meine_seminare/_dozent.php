<?= $colon ? ', ' : '' ?><a href="<?= URLHelper::getLink('about.php', array('username' => $_dozent['username'])) ?>"><?= htmlReady($_dozent["Nachname"]) ?></a><? $this->colon = true; ?>
