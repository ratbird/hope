<br>

<? if (trim($constraint['content'])) : ?>
<div class="posting">
    <span class="corners-top"><span></span></span>
    <div class="postbody">
        <div class="content"><?= formatReady(ForumEntry::killEdit($constraint['content'])) ?></div>
    </div>
    <span class="corners-bottom"><span></span></span>
</div>
<? endif ?>

<? if (!empty($list)) foreach ($list as $category_id => $entries) : ?>
<table class="default forum" data-category-id="<?= $category_id ?>">

    <colgroup>
        <col>
        <col>
        <col>
        <col>
    </colgroup>

    <thead>
        <tr>
            <th colspan="2"><?= _('Thema') ?></th>
            <th data-type="answers"><?= _("Beitr�ge") ?></th>
            <th data-type="last_posting"><?= _("letzte Antwort") ?></th>
        </tr>
    </thead>

    <tbody>
    
    <? if (!empty($entries)) foreach ($entries as $entry) :
        $jump_to_topic_id = ($entry['last_unread'] ?: $entry['topic_id']); ?>
 
    <tr data-area-id="<?= $entry['topic_id'] ?>">

        <td class="icon">
            <a href="<?= PluginEngine::getLink('coreforum/index/index/'. $jump_to_topic_id .'#'. $jump_to_topic_id) ?>">
            <? if ($entry['chdate'] >= $visitdate && $entry['owner_id'] != $GLOBALS['user']->id): ?>
                <? $jump_to_topic_id = $entry['topic_id'] ?>
                <?= Assets::img('icons/16/red/new/forum.png', array(
                    'title' => _('Dieser Eintrag ist neu!')
                )) ?>
            <? else : ?>
                <? $num_postings = ForumVisit::getCount($entry['topic_id'], $visitdate) ?>
                <? $text = ForumHelpers::getVisitText($num_postings, $entry['topic_id'], $constraint['depth']) ?>
                <? if ($num_postings > 0) : ?>
                    <?= Assets::img('icons/16/red/forum.png', array(
                        'title' => $text
                    )) ?>
                <? else : ?>
                    <?= Assets::img('icons/16/black/forum.png', array(
                        'title' => $text
                    )) ?>
                <? endif ?>
            <? endif ?>
            </a>
        </td>

        <td class="areaentry">
            <div style="position: relative;">
                <a href="<?= PluginEngine::getLink('coreforum/index/index/'. $entry['topic_id'] .'#'. $entry['topic_id']) ?>">
                    <span class="areaname"><?= htmlReady($entry['name_raw'] ?: _('Ohne Titel')) ?></span>
                </a>

                <span class="action-icons">
                    <? if (ForumPerm::has('move_thread', $seminar_id)) : ?>
                    <a href="javascript:STUDIP.Forum.moveThreadDialog('<?= $entry['topic_id'] ?>');">
                        <?= Assets::img('icons/16/blue/move_right/folder-full.png',
                            array('class' => 'move-thread', 'title' => 'Dieses Thema verschieben')) ?>
                    </a>
                    
                    <div id="dialog_<?= $entry['topic_id'] ?>" style="display: none" title="<?= _('Bereich, in den dieser Thread verschoben werden soll:') ?>">
                        <? $path = ForumEntry::getPathToPosting($entry['topic_id']);
                        $parent = array_pop(array_slice($path, sizeof($path) - 2, 1)); ?>

                        <? foreach ($areas['list'] as $area_id => $area): ?>
                        <? if ($area_id != $parent['id']) : ?>
                        <div style="font-size: 16px; margin-bottom: 5px;">
                            <a href="<?= PluginEngine::getLink('coreforum/index/move_thread/'. $entry['topic_id'].'/'. $area_id) ?>">
                            <?= Assets::img('icons/16/yellow/arr_2right.png') ?>
                            <?= htmlReady($area['name_raw']) ?>
                            </a>
                        </div>
                        <? endif ?>
                        <? endforeach ?>
                    </div>                    
                    <? endif ?>
                    
                    <? if (ForumPerm::has('remove_entry', $seminar_id)) : ?>
                    <a href="<?= PluginEngine::getURL('coreforum/index/delete_entry/' . $entry['topic_id']) ?>"
                        onClick="STUDIP.Forum.showDialog('<?= _('M�chten Sie dieses Thema wirklich l�schen?') ?>',
                       '<?= PluginEngine::getURL('coreforum/index/delete_entry/' . $entry['topic_id'] .'?approve_delete=1&page='. (ForumHelpers::getPage() + 1)) ?>',
                       'tr[data-area-id=<?= $entry['topic_id'] ?>] td.areaentry'); return false;">
                        <?= Assets::img('icons/16/blue/trash.png', 
                            array('class' => 'move-thread', 'title' => 'Dieses Thema l�schen')) ?>
                    </a>
                    <? endif ?>
                </span>

                <?= _("von") ?>
            <? if ($entry['anonymous']): ?>
                <?= _('Anonym') ?>
            <? endif; ?>
            <? if (!$entry['anonymous'] || $entry['user_id'] == $GLOBALS['user']->id || $GLOBALS['perm']->have_perm('root')): ?>
                <a href="<?= UrlHelper::getLink('about.php?username='. get_username($entry['owner_id'])) ?>">
                    <?= htmlReady($entry['author']) ?>
                </a>
                <? endif; ?>
                <?= _("am") ?> <?= strftime($time_format_string_short, (int)$entry['mkdate']) ?>
                <br>

                <? if ($entry['content_short'] && strlen($entry['content'] > strlen($entry['content_short']))) : ?>
                    <?= $entry['content_short'] ?>...
                <? else : ?>
                    <?= $entry['content_short'] ?>
                <? endif ?>
            </div>
        </td>

        <td class="postings">
            <?= $entry['num_postings'] ?>
        </td>

        <td class="answer">
            <? if (is_array($entry['last_posting'])) : ?>
            <?= _("von") ?>
            <? if ($entry['last_posting']['anonymous']): ?>
                <?= _('Anonym') ?>
            <? endif; ?>
            <? if (!$entry['last_posting']['anonymous'] || $entry['last_posting']['user_id'] == $GLOBALS['user']->id || $GLOBALS['perm']->have_perm('root')): ?>
            <a href="<?= UrlHelper::getLink('about.php?username='. $entry['last_posting']['username']) ?>">
                    <?= htmlReady($entry['last_posting']['user_fullname']) ?>
            </a>
            <? endif; ?>
            <br>
            <?= _("am") ?> <?= strftime($time_format_string_short, (int)$entry['last_posting']['date']) ?>
            <a href="<?= PluginEngine::getLink('coreforum/index/index/'. $entry['last_posting']['topic_id']) ?>#<?= $entry['last_posting']['topic_id'] ?>" alt="<?= $infotext ?>" title="<?= $infotext ?>">
                <?= Assets::img('icons/16/blue/link-intern.png', array('title' => $infotext = _("Direkt zum Beitrag..."))) ?>
            </a>
            <? else: ?>
            <br>
            <?= _('keine Antworten') ?>
            <? endif; ?>
        </td>
    </tr>
    <? endforeach; ?>
    </tbody>
</table>
<? endforeach ?>