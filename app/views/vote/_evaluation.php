<? $is_new = ($evaluation->chdate >= object_get_visit($evaluation->id, 'eval', false, false)) && ($evaluation->author_id != $GLOBALS['user']->id);
?>
<article class="<?= ContentBoxHelper::classes($evaluation->id, $is_new) ?>" id="<?= $evaluation->id ?>">
    <header>
        <nav>
            <a href="<?= $evaluation->author ? URLHelper::getLink('dispatch.php/profile', array('username' => $evaluation->author->username)) : '' ?>">
                <?= $evaluation->author ? htmlReady($evaluation->author->getFullName()) : '' ?>
            </a> |
            <?= strftime("%d.%m.%Y", $evaluation->mkdate) ?>
            <? if ($admin): ?>
                <a href="<?= URLHelper::getLink('admin_evaluation.php', array('openID' => $evaluation->id, 'rangeID' => $range_id)) ?>">
                    <?= Assets::img('icons/16/blue/admin.png') ?>
                </a>
                <? if (!$evaluation->enddate || $evaluation->enddate > time()): ?>
                    <a href="<?= URLHelper::getLink('admin_evaluation.php', array('evalID' => $evaluation->id, 'evalAction' => 'stop')) ?>">
                        <?= Assets::img('icons/16/blue/pause.png') ?>
                    </a>
                <? else: ?>
                    <a href="<?= URLHelper::getLink('admin_evaluation.php', array('evalID' => $evaluation->id, 'evalAction' => 'continue')) ?>">
                        <?= Assets::img('icons/16/blue/play.png') ?>
                    </a>
                <? endif; ?>
                <a href="<?= URLHelper::getLink('admin_evaluation.php', array('evalID' => $evaluation->id, 'evalAction' => 'delete_request')) ?>">
                    <?= Assets::img('icons/16/blue/trash.png') ?>
                </a>
                <a href="<?= URLHelper::getLink('admin_evaluation.php', array('evalID' => $evaluation->id, 'evalAction' => 'export_request')) ?>">
                    <?= Assets::img('icons/16/blue/export.png') ?>
                </a>
                <a href="<?= URLHelper::getLink('eval_summary.php', array('eval_id' => $evaluation->id)) ?>">
                    <?= Assets::img('icons/16/blue/vote.png') ?>
                </a>
            <? endif; ?>
        </nav>
        <h1>
            <a href="<?= ContentBoxHelper::switchhref($evaluation->id) ?>">
                <?= htmlReady($evaluation->title) ?>
            </a>
        </h1>
    </header>
    <section>
        <?= formatReady($evaluation->text); ?>
    </section>
    <section>
        <?= \Studip\LinkButton::create(_('Anzeigen'), URLHelper::getURL('show_evaluation.php', array('evalID' => $evaluation->id))) ?>
    </section>
    <footer>
        <p>
            <?= _('Teilnehmer') ?>: <?= count($evaluation->participants) ?>
        </p>
        <p>
            <?= _('Anonym') ?>: <?= $evaluation->anonymous ? _('Ja') : _('Nein') ?>
        </p>
        <p>
            <?= _('Endzeitpunkt') ?>: <?= $evaluation->enddate ? strftime('%d.%m.%y, %H:%M', $evaluation->enddate) : _('Unbekannt') ?>
        </p>
    </footer>
</article>