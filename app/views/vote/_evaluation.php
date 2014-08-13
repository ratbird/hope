<? $is_new = ($evaluation->chdate >= object_get_visit($evaluation->id, 'eval', false, false)) && ($evaluation->author_id != $GLOBALS['user']->id);
?>
<article class="<?= ContentBoxHelper::classes($evaluation->id, $is_new) ?>" id="<?= $evaluation->id ?>" data-visiturl="<?=URLHelper::getScriptLink('dispatch.php/vote/visit')?>">
    <header>
        <h1>
            <a href="<?= ContentBoxHelper::switchhref($evaluation->id, array('contentbox_type' => 'eval')) ?>">
                <?= htmlReady($evaluation->title) ?>
            </a>
        </h1>
        <nav>
            <a href="<?= $evaluation->author ? URLHelper::getLink('dispatch.php/profile', array('username' => $evaluation->author->username)) : '' ?>">
                <?= $evaluation->author ? htmlReady($evaluation->author->getFullName()) : '' ?>
            </a> |
            <?= strftime("%d.%m.%Y", $evaluation->mkdate) ?>
            <? if ($admin): ?>
                <a title="<?= _("Evaluation bearbeiten") ?>" href="<?= URLHelper::getLink('admin_evaluation.php', array('openID' => $evaluation->id, 'rangeID' => $range_id)) ?>">
                    <?= Assets::img('icons/16/blue/admin.png') ?>
                </a>
                <? if (!$evaluation->enddate || $evaluation->enddate > time()): ?>
                    <a title="<?= _("Evaluation stoppen") ?>" href="<?= URLHelper::getLink('admin_evaluation.php', array('evalID' => $evaluation->id, 'evalAction' => 'stop')) ?>">
                        <?= Assets::img('icons/16/blue/pause.png') ?>
                    </a>
                <? else: ?>
                    <a title="<?= _("Evaluation fortsetzen") ?>" href="<?= URLHelper::getLink('admin_evaluation.php', array('evalID' => $evaluation->id, 'evalAction' => 'continue')) ?>">
                        <?= Assets::img('icons/16/blue/play.png') ?>
                    </a>
                <? endif; ?>
                <a title="<?= _("Evaluation löschen") ?>" href="<?= URLHelper::getLink('admin_evaluation.php', array('evalID' => $evaluation->id, 'evalAction' => 'delete_request')) ?>">
                    <?= Assets::img('icons/16/blue/trash.png') ?>
                </a>
                <a title="<?= _("Evaluation exportieren") ?>" href="<?= URLHelper::getLink('admin_evaluation.php', array('evalID' => $evaluation->id, 'evalAction' => 'export_request')) ?>">
                    <?= Assets::img('icons/16/blue/export.png') ?>
                </a>
                <a title="<?= _("Evaluation auswerten") ?>" href="<?= URLHelper::getLink('eval_summary.php', array('eval_id' => $evaluation->id)) ?>">
                    <?= Assets::img('icons/16/blue/vote.png') ?>
                </a>
            <? endif; ?>
        </nav>
    </header>
    <section>
        <?= formatReady($evaluation->text); ?>
    </section>
    <section>
        <?= \Studip\LinkButton::create(_('Anzeigen'), URLHelper::getURL('show_evaluation.php', array('evalID' => $evaluation->id)), array('data-dialog' => '', 'target' => '_blank')) ?>
    </section>
    <footer>
        <p>
            <?= _('Teilnehmer') ?>: <?= $evaluation->getNumberOfVotes() ?>
        </p>
        <p>
            <?= _('Anonym') ?>: <?= $evaluation->anonymous ? _('Ja') : _('Nein') ?>
        </p>
        <p>
            <?= _('Endzeitpunkt') ?>: <?= $evaluation->enddate ? strftime('%d.%m.%y, %H:%M', $evaluation->enddate) : _('Unbekannt') ?>
        </p>
    </footer>
</article>
