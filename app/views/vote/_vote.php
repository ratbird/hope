<? $is_new = ($vote->chdate >= object_get_visit($vote->id, 'vote', false, false)) && ($vote->author_id != $GLOBALS['user']->id);
?>
<article class="<?= ContentBoxHelper::classes($vote->id, $is_new) ?>" id="<?= $vote->id ?>" data-visiturl="<?=URLHelper::getScriptLink('dispatch.php/vote/visit')?>">
    <header>
        <h1>
            <a href="<?= ContentBoxHelper::switchhref($vote->id, array('contentbox_type' => 'vote')) ?>">
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
                    <?= Icon::create('admin', 'clickable')->asImg() ?>
                </a>
            <? endif; ?>
        </nav>
    </header>
    <?= $this->render_partial('vote/_votecontent.php', array('vote' => $vote)); ?>
</article>