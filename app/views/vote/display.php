<? if ($admin || $votes || $evaluations): ?>
<section class="contentbox">
    <header>
        <h1>
            <?= Icon::create('vote', 'info')->asImg(); ?>
            <?= _('Umfragen') ?>
        </h1>
        <nav>
        <? if ($admin): ?>
            <a href="<?= URLHelper::getLink('admin_vote.php', array('page' => 'overview')) ?>">
                <?= Icon::create('admin', 'clickable')->asImg(); ?>
            </a>
        <? endif; ?>
        </nav>
    </header>

    <? if (!$votes && !$evaluations): ?>
        <section>
            <?= _('Keine Umfragen vorhanden. Um neue Umfragen zu erstellen, klicken Sie rechts auf die Zahnr�der.') ?>
        </section>
    <? else: ?>
        <? foreach ($votes as $vote): ?>
            <?= $this->render_partial('vote/_vote.php', array('vote' => $vote)); ?>
        <? endforeach; ?>
        <? foreach ($evaluations as $evaluation): ?>
            <?= $this->render_partial('vote/_evaluation.php', array('evaluation' => $evaluation)); ?>
        <? endforeach; ?>
    <? endif; ?>
        <footer>
            <? if (Request::get('show_expired')): ?>
                <a href="<?= URLHelper::getLink('', array('show_expired' => 0)) ?>"><?= _('Abgelaufene Umfragen ausblenden') ?></a>
            <? else: ?>
                <a href="<?= URLHelper::getLink('', array('show_expired' => 1)) ?>"><?= _('Abgelaufene Umfragen einblenden') ?></a>
            <? endif; ?>
        </footer>
</section>
<? endif; ?>