<article class="<?= ContentBoxHelper::classes($vote->id) ?>" id="<?= $vote->id ?>">
    <header>
        <nav>
            <?= $vote->count ?> |
            <a href="<?= ContentBoxHelper::switchhref($vote->id) ?>">
                <?= htmlReady($vote->author->getFullName()) ?>
            </a> |
            <?= strftime("%d.%m.%Y", $vote->mkdate) ?>
            <a href="<?= URLHelper::getLink('admin_vote.php', array('page' => 'edit', 'type' => 'vote', 'voteID' => $vote->id)) ?>">
                <?= Assets::img('icons/16/blue/admin.png') ?>
            </a>
        </nav>
        <h1>
            <a href="<?= URLHelper::getLink('', array('voteopenID' => $vote->id)) ?>">
                <?= htmlReady($vote->title) ?>
            </a>
        </h1>
    </header>
    <?= $this->render_partial('vote/_votecontent.php', array('vote' => $vote)); ?>
</article>