<? if($rss): ?>
<table class="default nohover">
    <caption>
        <?= Assets::img('icons/16/grey/rss.png'); ?>
        <?= _('RSS Feeds') ?>
        <a href="<?= URLHelper::getLink('dispatch.php/admin/rss_feeds') ?>">
            <?= Assets::img('icons/16/blue/admin.png'); ?>
        </a>
    </caption>
    <? foreach ($rss as $feed): ?>
        <? if ($feed->hidden) continue; ?>  
        <thead>
            <tr>
                <th>
                    <?= Assets::img('icons/16/black/rss.png'); ?>
                    <?= trim(studip_utf8decode($feed->name)) ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <? if (!$content = $feed->getContent()): ?>
            <tbody>
                <tr>
                    <td>
                        <?= _("Zeitüberschreitung beim Laden von") . " " . $feed->url ?>
                    </td>
                </tr>
            </tbody>
        <? else: ?> 
            <? foreach ($content as $entry): ?>
                <tr>
                    <th>
                        <?= trim(studip_utf8decode(htmlReady($entry['title']))) ?>
                    </th>
                </tr>
                <tr>
                    <td>
                        <?= studip_utf8decode($entry['summary']) ?>
                    </td>
                </tr>
            <? endforeach; ?>
        </tbody>
    <? endif; ?>
<? endforeach; ?>
</table>
<? endif; ?>