<? $is_new = ($vote->chdate >= object_get_visit($vote->id, 'vote', false, false)) && ($vote->author_id != $GLOBALS['user']->id);
?>
<article class="<?= ContentBoxHelper::classes($vote->id, $is_new) ?>" id="<?= $vote->id ?>">
    <header>
        <h1>
            <a href="<?= ContentBoxHelper::switchhref($vote->id) ?>">
                <?= htmlReady($vote->title) ?>
            </a>
        </h1>
        <nav>
            <a href="<?= $vote->author ? URLHelper::getLink('dispatch.php/profile', array('username' => $vote->author->username)) : '' ?>">
                <?= $vote->author ? htmlReady($vote->author->getFullName()) : '' ?>
            </a>
            <span>
                <?= strftime("%d.%m.%Y", $vote->mkdate) ?>
            </span>
            <span>
                <?= $vote->count ?>
            </span>
            <? if ($admin): ?>
                <a href="<?= URLHelper::getLink('admin_vote.php', array('page' => 'edit', 'type' => 'vote', 'voteID' => $vote->id)) ?>">
                    <?= Assets::img('icons/16/blue/admin.png') ?>
                </a>
            <? endif; ?>
        </nav>
    </header>
    <?= $this->render_partial('vote/_votecontent.php', array('vote' => $vote)); ?>
</article>