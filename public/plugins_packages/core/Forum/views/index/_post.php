<? if (!is_array($highlight)) $highlight = array(); ?>
<? $is_new =  ((isset($visitdate) && $post['mkdate'] >= $visitdate) || !(isset($visitdate))) ?>
<? if (!$constraint) $constraint = ForumEntry::getConstraints (ForumEntry::getParentTopicId($post['topic_id'])) ?>
<!-- Anker, um zu diesem Posting springen zu können -->
<a name="<?= $post['topic_id'] ?>"></a>

<form method="post" data-topicid="<?= $post['topic_id'] ?>" action="<?= PluginEngine::getLink('coreforum/index/update_entry/' . $post['topic_id']) ?>">
    <?= CSRFProtection::tokenTag() ?>
    
<div class="posting<?= $highlight_topic == $post['topic_id'] ? ' highlight' : '' ?>" style="position: relative;" id="forumposting_<?= htmlReady($post['topic_id']) ?>">
    <a class="marked" href="<?= PluginEngine::getLink('coreforum/index/unset_favorite/'. $post['topic_id']) ?>"
            onClick="STUDIP.Forum.unsetFavorite('<?= $post['topic_id'] ?>'); return false;" title="<?= _('Beitrag nicht mehr merken') ?>"
            <?= ($post['fav']) ?: 'style="display: none;"' ?> data-topic-id="<?= $post['topic_id'] ?>">
        <div></div>
    </a>

    <div class="postbody">
        <div class="title">

            <div class="small_screen" style="margin-bottom: 5px">
                <? if ($post['anonymous']): ?>
                    <strong><?= _('Anonym') ?></strong>
                    <?= strftime($time_format_string_short, (int)$post['mkdate']) ?>
                <? elseif (!$post['user_id']) : ?>
                    <?= Avatar::getAvatar('nobody')->getImageTag(Avatar::SMALL,
                        array('title' => _('Stud.IP'))) ?>
                    <?= _('von Stud.IP erstellt') ?>, 
                    <?= strftime($time_format_string_short, (int)$post['mkdate']) ?>
                <? else : ?>
                <a href="<?= URLHelper::getLink('about.php?username='. get_username($post['user_id'])) ?>">
                    <?= Avatar::getAvatar($post['user_id'])->getImageTag(Avatar::SMALL,
                        array('title' => get_username($post['user_id']))) ?>

                    <?= htmlReady(get_fullname($post['user_id'])) ?>,
                    <?= strftime($time_format_string_short, (int)$post['mkdate']) ?>
                </a>
                <? endif ?>

                <br>
            </div>

            <? if ($post['depth'] < 3) : ?>  
            <span data-edit-topic="<?= $post['topic_id'] ?>" <?= Request::get('edit_posting') == $post['topic_id'] ? '' : 'style="display: none;"' ?>>
                <input type="text" name="name" value="<?= htmlReady($post['name_raw']) ?>" data-reset="<?= htmlReady($post['name_raw']) ?>" style="width: 100%">
            </span>
            <? else : ?>
                <? $parent_topic = ForumEntry::getConstraints(ForumEntry::getParentTopicId($post['topic_id'])) ?>

                <? if($constraint['closed']) : ?>
                <?= Assets::img('icons/16/black/lock-locked.png', array(
                    'title' => _('Dieses Thema wurde geschlossen. Sie können daher nicht auf diesen Beitrag antworten.')
                )) ?>
                <? endif ?>

                <span data-edit-topic="<?= $post['topic_id'] ?>">
                    <span name="name" value="<?= htmlReady($parent_topic['name']) ?>"></span>
                </span>
            <? endif ?>
            
            <span data-show-topic="<?= $post['topic_id'] ?>">
                <a href="<?= PluginEngine::getLink('coreforum/index/index/' . $post['topic_id'] .'?'. http_build_query(array('highlight' => $highlight)) ) ?>#<?= $post['topic_id'] ?>">
                <? if ($show_full_path) : ?>
                    <?= ForumHelpers::highlight(htmlReady(implode(' >> ', ForumEntry::getFlatPathToPosting($post['topic_id']))), $highlight) ?>
                <? elseif ($post['depth'] < 3) : ?>
                <span data-topic-name="<?= $post['topic_id'] ?>">
                    <? if (Request::get('edit_posting') != $post['topic_id']) : ?>
                    <?= ($post['name_raw'] && $post['depth'] < 3) ? ForumHelpers::highlight(htmlReady($post['name_raw']), $highlight) : ''?>
                    <? endif ?>
                </span>
                <? endif ?>
                </a>
            </span>
        </div>

        <!-- Postinginhalt -->
        <div class="content">
            <span data-edit-topic="<?= $post['topic_id'] ?>" <?= Request::get('edit_posting') == $post['topic_id'] ? '' : 'style="display: none;"' ?>>
                <textarea data-textarea="<?= $post['topic_id'] ?>" data-reset="<?= htmlReady($post['content_raw']) ?>" name="content" class="add_toolbar"><?= htmlReady($post['content_raw']) ?></textarea>
            </span>
            
            <span data-show-topic="<?= $post['topic_id'] ?>" data-topic-content="<?= $post['topic_id'] ?>" <?= Request::get('edit_posting') != $post['topic_id'] ? '' : 'style="display: none;"' ?>>
                <?= ForumHelpers::highlight($post['content'], $highlight) ?>
            </span>
        </div>
        <div class="opengraph_area"><?= $post['opengraph'] ?></div>

        <!-- Buttons for this Posting -->
        <div class="buttons">
            <div class="button-group">
        <? if (ForumPerm::hasEditPerms($post['topic_id'])) : ?>
        <span data-edit-topic="<?= $post['topic_id'] ?>" <?= Request::get('edit_posting') == $post['topic_id'] ? '' : 'style="display: none;"' ?>>
            <!-- Buttons für den Bearbeitungsmodus -->
            <?= Studip\Button::createAccept(_('Änderungen speichern'), '',
                array('onClick' => "STUDIP.Forum.saveEntry('". $post['topic_id'] ."'); return false;")) ?>

            <?= Studip\LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('coreforum/index/index/'. $post['topic_id'] .'#'. $post['topic_id']),
                array('onClick' => "STUDIP.Forum.cancelEditEntry('". $post['topic_id'] ."'); return false;")) ?>
            
            <?= Studip\LinkButton::create(_('Vorschau'), "javascript:STUDIP.Forum.preview('". $post['topic_id'] ."', 'preview_". $post['topic_id'] ."');") ?>
        </span>
        <? endif ?>
                
        <span data-show-topic="<?= $post['topic_id'] ?>" <?= Request::get('edit_posting') != $post['topic_id'] ? '' : 'style="display: none;"' ?>>
            <!-- Aktions-Buttons für diesen Beitrag -->

            <? if (ForumPerm::has('add_entry', $seminar_id)) : ?>
                <?= Studip\LinkButton::create(_('Beitrag zitieren'), PluginEngine::getLink('coreforum/index/cite/' . $post['topic_id']), array(
                    'onClick' => "javascript:STUDIP.Forum.citeEntry('". $post['topic_id'] ."'); return false;",
                    'class'   => 'hideWhenClosed',
                    'style'   => $constraint['closed'] ? 'display: none' : ''
                )) ?>
            <? endif ?>

            <? if ($section == 'index' && ForumPerm::hasEditPerms($post['topic_id'])) : ?>
                <?= Studip\LinkButton::create(_('Beitrag bearbeiten'), PluginEngine::getUrl('coreforum/index/index/' 
                      . $post['topic_id'] .'/?edit_posting=' . $post['topic_id']), array(
                          'onClick' => "STUDIP.Forum.editEntry('". $post['topic_id'] ."'); return false;",
                          'class'   => 'hideWhenClosed',
                          'style'   => $constraint['closed'] ? 'display: none' : ''
                )) ?>
            <? endif ?>
            
            <? if ($section == 'index' && (ForumPerm::hasEditPerms($post['topic_id']) || ForumPerm::has('remove_entry', $seminar_id))) : ?>
                <? $confirmLink = PluginEngine::getURL('coreforum/index/delete_entry/' . $post['topic_id'])  ?>
                <? $confirmLinkApproved = PluginEngine::getURL('coreforum/index/delete_entry/' . $post['topic_id'] . '?approve_delete=1')  ?>
                <? if ($constraint['depth'] == $post['depth']) : /* this is not only a posting, but a thread */ ?>
                    <? $confirmText = _('Wenn Sie diesen Beitrag löschen wird ebenfalls das gesamte Thema gelöscht. Sind Sie sicher, dass Sie das tun möchten?')  ?>
                    <?= Studip\LinkButton::create(_('Thema löschen'), $confirmLink,
                        array('onClick' => "STUDIP.Forum.showDialog('$confirmText', '$confirmLinkApproved'); return false;")) ?>
                <? else : ?>
                    <? $confirmText = _('Möchten Sie diesen Beitrag wirklich löschen?') ?>
                    <?= Studip\LinkButton::create(_('Beitrag löschen'), $confirmLink,
                        array('onClick' => "STUDIP.Forum.showDialog('$confirmText', '$confirmLinkApproved'); return false;")) ?>
                <? endif ?>
            <? endif ?>

            <? if (ForumPerm::has('forward_entry', $seminar_id)) : ?>
            <?= Studip\LinkButton::create(_('Beitrag weiterleiten'), 
                    "javascript:STUDIP.Forum.forwardEntry('". $post['topic_id'] ."')", array('class' => 'js')) ?>
            <? endif ?>
        </span>
            </div>
        </div>

    </div>

    <? if (ForumPerm::hasEditPerms($post['topic_id'])) : ?>
    <span data-edit-topic="<?= $post['topic_id'] ?>" <?= Request::get('edit_posting') == $post['topic_id'] ? '' : 'style="display: none;"' ?>>
        <dl class="postprofile">
            <dt>
                <?= $this->render_partial('index/_smiley_favorites', array('textarea_id' => $post['topic_id'])) ?>
            </dt>
        </dl>
    </span>
    <? endif ?>

    <!-- Infobox rechts neben jedem Posting -->
    <span data-show-topic="<?= $post['topic_id'] ?>" <?= Request::get('edit_posting') != $post['topic_id'] ? '' : 'style="display: none;"' ?>>
        <dl class="postprofile">
            <? if ($post['anonymous']): ?>
                <dd class="anonymous_post" data-profile="<?= $post['topic_id'] ?>"><strong><?= _('Anonym') ?></strong></dd>
            <? endif; ?>
            <? if (!$post['anonymous'] || $post['user_id'] == $GLOBALS['user']->id || $GLOBALS['perm']->have_perm('root')): ?>
            <dt>
                <? if ($post['user_id'] != 'nobody' && $post['user_id']) : ?>
                <a href="<?= URLHelper::getLink('about.php?username='. get_username($post['user_id'])) ?>">
                    <?= Avatar::getAvatar($post['user_id'])->getImageTag(Avatar::MEDIUM,
                        array('title' => get_username($post['user_id']))) ?>
                </a>
                <br>
                <? endif ?>

                <? if ($post['user_id'] == 'nobody') : ?>
                    <?= Assets::img('icons/16/black/community.png') ?>
                    <span class="username" data-profile="<?= $post['topic_id'] ?>">
                        <?= htmlReady($post['author']) ?>
                    </span>
                <? elseif ($post['user_id']) : ?>

                    <!-- Online-Status -->
                    <? $status = ForumHelpers::getOnlineStatus($post['user_id']) ?>
                    <? if ($status == 'available') : ?>
                        <img src="<?= $picturepath ?>/community.png" title="<?= _('Online') ?>">
                    <? elseif ($status == 'away') : ?>
                        <?= Assets::img('icons/16/grey/community.png', array('title' => _('Abwesend'))) ?>
                    <? elseif ($status == 'offline') : ?>
                        <?= Assets::img('icons/16/black/community.png', array('title' => _('Offline'))) ?>
                    <? endif ?>

                    <a href="<?= URLHelper::getLink('about.php?username='. get_username($post['user_id'])) ?>">
                        <span class="username" data-profile="<?= $post['topic_id'] ?>">
                            <?= htmlReady(get_fullname($post['user_id'])) ?>
                        </span>
                    </a>
                <? endif ?>
            </dt>

            <dd>
                <?= ForumHelpers::translate_perm($GLOBALS['perm']->get_studip_perm($constraint['seminar_id'], $post['user_id']))?>
            </dd>
            <? if ($post['user_id']) : ?>
            <dd>
                Beiträge:
                <?= ForumEntry::countUserEntries($post['user_id']) ?><br>
                <?= _('Erhaltene "Gefällt mir!":') ?>
                <?= ForumLike::receivedForUser($post['user_id']) ?>
            </dd>
            <? endif ?>
            <? endif; ?>
            <dd>
                <? if (!$post['user_id']) : ?>
                    <?= _('von Stud.IP erstellt') ?><br>
                <? endif ?>
            </dd>
            
            <dd class="posting_icons">
                <!-- Favorit -->
                <span id="favorite_<?= $post['topic_id'] ?>">
                    <?= $this->render_partial('index/_favorite', array('topic_id' => $post['topic_id'], 'favorite' => $post['fav'])) ?>
                </span>
                    
                <!-- Permalink -->
                <a href="<?= PluginEngine::getLink('coreforum/index/index/' . $post['topic_id'] .'#'. $post['topic_id']) ?>">
                    <?= Assets::img('icons/16/blue/group.png', array('title' => _('Link zu diesem Beitrag'))) ?>
                </a>
                <br>

                <!-- Like -->
                <span class="likes" id="like_<?= $post['topic_id'] ?>">
                    <?= $this->render_partial('index/_like', array('topic_id' => $post['topic_id'])) ?>
                </span>
            </dd>

            <? foreach (PluginEngine::sendMessage('PostingApplet', 'getHTML', $post['name_raw'], $post['content_raw'],
                    PluginEngine::getLink('coreforum/index/index/' . $post['topic_id'] .'#'. $post['topic_id']),
                    $post['user_id']) as $applet_data) : ?>
            <dd>
                <?= $applet_data ?>
            </dd>
            <? endforeach ?>
        </dl>
        
        <? if ($is_new): ?>
        <span class="new_posting">
            <?= Assets::img('icons/16/red/new/forum.png', array(
                'title' => _("Dieser Beitrag ist seit Ihrem letzten Besuch hinzugekommen.")
            )) ?>
        </span>
        <? endif ?>  
    </span>

    <div class="clear"></div>
</div>
</form>

<?= $this->render_partial('index/_preview', array('preview_id' => 'preview_' . $post['topic_id'])) ?>
