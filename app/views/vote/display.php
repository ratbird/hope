<? if (get_config('VOTE_ENABLE')): ?>
<section class="contentbox">
    <header>
        <h1><?= Assets::img('icons/16/black/vote.png'); ?><?= _('Umfragen') ?></h1>
    </header>

    <? if (!$votes): ?>
    <p>
        <?= _('Keine Umfragen vorhanden') ?>
    </p>
    <? endif; ?>
    <? foreach ($votes as $vote): ?>
    <?= $this->render_partial('vote/_vote.php', array('vote' => $vote)); ?>
    <? endforeach; ?>
</section>
<? endif;