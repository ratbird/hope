<?
# Lifter010: TODO
?>
<a href="<?= URLHelper::getLink("calendar.php?caluser=self&cmd=showweek&atime=" . $start) ?>">
    <?= Assets::img('icons/16/blue/schedule.png',
                    tooltip2(sprintf(_('Zum %s in den persönlichen Terminkalender springen'),
                                     date('d.m.', $start)))) ?>
</a>