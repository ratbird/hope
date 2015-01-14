<? $jump_to_topic_id = $entry['topic_id'] ?>
<tr id="tutorArea" data-area-id="<?= $entry['topic_id'] ?>" <?= (ForumPerm::has('sort_area', $seminar_id)) ? 'class="movable"' : '' ?>>
    <td class="icon">
        <? if (ForumPerm::has('sort_area', $seminar_id)) : ?>
        <img src="<?= $picturepath ?>/anfasser_48.png" class="handle js" id="tutorMoveArea">
        <? endif ?>

        <a href="<?= PluginEngine::getLink('coreforum/index/index/'. $jump_to_topic_id .'#'. $jump_to_topic_id) ?>">
        <? if ($entry['chdate'] >= $visitdate && $entry['user_id'] != $GLOBALS['user']->id): ?>
            <?= Assets::img('icons/16/red/new/forum.png', array(
                'title' => _('Dieser Eintrag ist neu!'),
                'id'    => 'tutorNotificationIcon',
                'style' => 'margin-bottom: 15px;'
            )) ?>
        <? else : ?>
            <? $num_postings = ForumVisit::getCount($entry['topic_id'], $visitdate) ?>
            <? $text = ForumHelpers::getVisitText($num_postings, $entry['topic_id'], $constraint['depth']) ?>
            <? if ($num_postings > 0) : ?>
                <?= Assets::img('icons/16/red/forum.png', array(
                    'title' => $text,
                    'id'    => 'tutorNotificationIcon',
                    'style' => 'margin-bottom: 15px;'
                )) ?>
            <? else : ?>
                <?= Assets::img('icons/16/black/forum.png', array(
                    'title' => $text,
                    'id'    => 'tutorNotificationIcon',
                    'style' => 'margin-bottom: 15px;'
                )) ?>
            <? endif ?>
        <? endif ?>
        </a>
    </td>
    <td class="areaentry">
        <div style="position: relative;<?= Request::get('edit_area') == $entry['topic_id'] ? 'height: auto;' : '' ?>">

            <span class="areadata" <?= Request::get('edit_area') != $entry['topic_id'] ? '' : 'style="display: none;"' ?>>
                <a href="<?= PluginEngine::getLink('coreforum/index/index/'. $jump_to_topic_id .'#'. $jump_to_topic_id) ?>">
                    <span class="areaname"><?= htmlReady($entry['name_raw']) ?></span>
                </a>
                <div class="areacontent" data-content="<?= htmlReady($entry['content_raw']) ?>">
                    <? $description = ForumEntry::killFormat(ForumEntry::killEdit($entry['content_raw'])) ?>
                    <?= htmlReady(substr($description, 0, 150))
                    ?><?= (strlen($description) > 150) ? '&hellip;' : '' ?>
                </div>
            </span>


            <? if (ForumPerm::has('edit_area', $seminar_id) && Request::get('edit_area') == $entry['topic_id']) : ?>
            <span style="text-align: center;">
                <div style="width: 90%">
                    <?= $this->render_partial('area/_edit_area_form', compact('entry')) ?>
                </div>
            </span>
            <? endif ?>

            <span class="action-icons" <? if(ForumPerm::has('edit_area', $seminar_id)) : ?> id="tutorAreaIcons"<? endif ?> <?= Request::get('edit_area') != $entry['topic_id'] ? '' : 'style="display: none;"' ?>>
                <? if (ForumPerm::has('edit_area', $seminar_id)) : ?>
                <a href="<?= PluginEngine::getLink('coreforum/index/?edit_area=' . $entry['topic_id']) ?>"
                    onClick="STUDIP.Forum.editArea('<?= $entry['topic_id'] ?>');return false;">
                    <?= Assets::img('icons/16/blue/edit.png',
                        array('class' => 'edit-area', 'title' => 'Name/Beschreibung des Bereichs ändern')) ?>
                </a>
                <? endif ?>

                <? if (ForumPerm::has('remove_area', $seminar_id)) : ?>
                <a href="<?= PluginEngine::getLink('coreforum/index/delete_entry/' . $entry['topic_id']) ?>"
                   onClick="STUDIP.Forum.deleteArea(this, '<?= $entry['topic_id'] ?>'); return false;">
                    <?= Assets::img('icons/16/blue/trash.png',
                        array('class' => 'delete-area', 'title' => 'Bereich mitsamt allen Einträgen löschen!')) ?>
                </a>
                <? endif ?>
            </span>
        </div>
    </td>

    <td class="postings">
        <span id="tutorNumPostings">
            <?= ($entry['num_postings'] > 0) ? ($entry['num_postings'] - 1) : 0 ?>
        </span>
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
        </a><br>
        <?= _("am") ?> <?= strftime($time_format_string_short, (int)$entry['last_posting']['date']) ?>
        <a href="<?= PluginEngine::getLink('coreforum/index/index/'. $entry['last_posting']['topic_id']) ?>#<?= $entry['last_posting']['topic_id'] ?>" alt="<?= $infotext ?>" title="<?= $infotext ?>">
            <?= Assets::img('icons/16/blue/link-intern.png', array('title' => $infotext = _("Direkt zum Beitrag..."), 'id' => 'tutorLatestAnswer')) ?>
        </a>
        <? else: ?>
        <br>
        <?= _('keine Antworten') ?>
        <? endif; ?>
        <? endif; ?>
    </td>

    <td class="icon" style="text-align: right; padding-right: 2px;">
        <? if (ForumPerm::has('sort_area', $seminar_id)) : ?>
        <img src="<?= $picturepath ?>/anfasser_48.png" class="handle js" id="tutorMoveArea">
        <? endif ?>
    </td>

</tr>