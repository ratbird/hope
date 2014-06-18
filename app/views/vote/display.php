<? if (get_config('VOTE_ENABLE') && ($admin || $votes)): ?>
<section class="contentbox">
    <header>
        <nav>
            <? if($admin): ?>
            <a href="<?= URLHelper::getLink('admin_vote.php', array('page' => 'overview')) ?>">
                <?= Assets::img('icons/16/blue/admin.png'); ?>
            </a>
            <? endif; ?>
        </nav>
        <h1><?= Assets::img('icons/16/black/vote.png'); ?><?= _('Umfragen') ?></h1>
    </header>

    <? if (!$votes): ?>
    <section>
        <?= _('Keine Umfragen vorhanden. Um neue Umfragen zu erstellen, klicken Sie rechts auf die Zahnräder.') ?>
    </section>
    <? endif; ?>
    <? foreach ($votes as $vote): ?>
    <?= $this->render_partial('vote/_vote.php', array('vote' => $vote)); ?>
    <? endforeach; ?>
</section>
<? endif;