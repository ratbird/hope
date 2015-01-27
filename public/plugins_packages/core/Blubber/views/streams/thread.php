<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

$last_visit = object_get_visit($_SESSION['SessionSeminar'], "forum");
BlubberPosting::$course_hashes = ($thread['context_type'] === "course" ? $thread['Seminar_id'] : false);
$related_users = $thread['context_type'] === "private" ? $thread->getRelatedUsers() : array();
$author = $thread->getUser();
$author_name = $author->getName();
$author_url = $author->getURL();
$commentable = $GLOBALS['perm']->have_perm("autor") ? true : (bool) $commentable;
?>
<? if (@$single_thread): ?>
<input type="hidden" id="base_url" value="plugins.php/blubber/streams/">
<input type="hidden" id="context_id" value="<?= htmlReady($thread->getId()) ?>">
<input type="hidden" id="stream" value="thread">
<input type="hidden" id="last_check" value="<?= time() ?>">
<input type="hidden" id="user_id" value="<?= htmlReady($GLOBALS['user']->id) ?>">
<input type="hidden" id="stream_time" value="<?= time() ?>">
<input type="hidden" id="browser_start_time" value="">
<div id="editing_question" style="display: none;"><?= _("Wollen Sie den Beitrag wirklich bearbeiten?") ?></div>
<? if (Navigation::hasItem("/community/blubber")) : ?>
<p>
    <? switch ($thread['context_type']) {
        case "course":
            $overview_url = URLHelper::getURL("plugins.php/blubber/streams/forum", array('cid' => $thread['Seminar_id']));
            break;
        case "public":
            $overview_url = URLHelper::getURL("plugins.php/blubber/streams/profile", array('user_id' => $thread['user_id'], 'extern' => $thread['external_contact'] ? $thread['external_contact'] : null));
            break;
        default:
            $overview_url = URLHelper::getURL("plugins.php/blubber/streams/global");
    } ?>
    <a href="<?= URLHelper::getLink($overview_url) ?>">
        <?= Assets::img('icons/16/blue/arr_1left', array('class' => 'text-top')) ?>
        <?= _('Zurück zur Übersicht') ?>
    </a>
</p>
<? endif ?>

<ul id="blubber_threads" class="coursestream singlethread" aria-live="polite" aria-relevant="additions">
<? endif; ?>
<li id="posting_<?= htmlReady($thread->getId()) ?>" mkdate="<?= htmlReady($thread['discussion_time']) ?>" data-discussion_time="<?= htmlReady($thread['discussion_time']) ?>" class="thread posting<?= $last_visit < $thread['mkdate'] ? " new" : "" ?> <?= $thread['context_type'] ?>" data-autor="<?= htmlReady($thread['user_id']) ?>">
    <? $thread['discussion_time'] ?>
    <div class="hiddeninfo">
        <input type="hidden" name="context" value="<?= htmlReady($thread['Seminar_id']) ?>">
        <input type="hidden" name="context_type" value="<?= $thread['Seminar_id'] === $thread['user_id'] ? "public" : "course" ?>">
    </div>
    <? if ($thread['context_type'] === "course") : ?>
    <a href="<?= URLHelper::getLink("plugins.php/blubber/streams/forum", array('cid' => $thread['Seminar_id'])) ?>"
       <? $title = get_object_name($thread['Seminar_id'], "sem") ?>
       title="<?= _("Veranstaltung")." ".htmlReady($title['name']) ?>"
       class="contextinfo"
       style="background-image: url('<?= CourseAvatar::getAvatar($thread['Seminar_id'])->getURL(Avatar::NORMAL) ?>');">
        <div class="name"><?= htmlReady(Course::find($thread['Seminar_id'])->name) ?></div><div class="empty"></div>
    </a>
    <? elseif($thread['context_type'] === "private") : ?>
    <?
        if (count($related_users) > 20) {
            $title = _("Privat: ").sprintf(_("%s Personen"), count($related_users));
        } else {
            $title = _("Privat: ");
            foreach ($related_users as $key => $user_id) {
                if ($key > 0) {
                    $title .= ", ";
                }
                $title .= get_fullname($user_id);
            }
        }
    ?>
    <div class="contextinfo" title="<?= htmlReady($title) ?>" style="background-image: url('<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>/plugins_packages/core/Blubber/assets/images/private<?= $GLOBALS['auth']->auth['devicePixelRatio'] > 1.2 ? "@2x" : "" ?>.png');">
        <div class="name"><?= _("Privat") ?>
            <? if (count($related_users) > 1) : ?>
            <br><?= sprintf("%s Personen", count($related_users)) ?>
            <? endif ?>
            </div><div class="empty"></div>
    </div>
    <div class="related_users"></div>
    <? else : ?>
    <div class="contextinfo" title="<?= _("Öffentlich") ?>" style="background-image: url('<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>/plugins_packages/core/Blubber/assets/images/public<?= $GLOBALS['auth']->auth['devicePixelRatio'] > 1.2 ? "@2x" : "" ?>.png');">
        <div class="name"><?= _("Öffentlich") ?></div><div class="empty"></div>
    </div>
    <? endif ?>
    <? if ($thread['context_type'] === "public") : ?>
    <? $sharingusers = $thread->getSharingUsers() ?>
    <? $sharing_user_ids = array_map(function ($v) { return $v['user_id']; }, $sharingusers) ?>
    <div class="reshares<?= count($sharingusers) > 0 ? " reshared" : "" ?>">
        <? if (count($sharingusers)) : ?>
            <? if ((!User::findCurrent()->isFriendOf($thread) || $thread['external_contact']) && ($GLOBALS['user']->id !== $thread['user_id'])) : ?>
                <? $sharingcontacts = "" ?>
                <? $othersharing = 0 ?>
                <? foreach ($sharingusers as $key => $user) {
                    if (User::findCurrent()->isFriendOf($user)) {
                        $url = $user->getURL();
                        $name = $user->getName();
                        if ($url) {
                            $sharingcontacts .= '<a href="'.$url.'" title="'.htmlReady($name).'">';
                        }
                        $sharingcontacts .= $user->getAvatar()->getImageTag(Avatar::SMALL, array('title' => $name));
                        if ($url) {
                            $sharingcontacts .= '</a>';
                        }
                    } else {
                        $othersharing++;
                    }
                } ?>
                <? if ($sharingcontacts) : ?>
                <?= $sharingcontacts ?>
                <a href="#" class="open_reshare_context"><?= $othersharing > 0 ? sprintf(_("und %s weitere haben das weitergesagt"), $othersharing) : _("haben das weitergesagt") ?></a>
                <? else : ?>
                <a href="#" class="open_reshare_context"><?= $othersharing > 1 ? sprintf(_("%s Personen haben das weitergesagt"), $othersharing) : _("Eine Person hat das weitergesagt") ?></a>
                <? endif ?>
            <? else : ?>
                <a href="#" class="open_reshare_context"><?= count($sharingusers) > 1 ? sprintf(_("%s Personen haben das weitergesagt"), count($sharingusers)) : _("Eine Person hat das weitergesagt") ?></a>
            <? endif ?>
        <? endif ?>
        <span class="reshare_link">
        <? if (!in_array($GLOBALS['user']->id, $sharing_user_ids) && $GLOBALS['user']->id !== $thread['user_id']) : ?>
        <?= Assets::img("icons/16/blue/blubber", array('class' => "text-bottom reshare_blubber", 'title' => _("Diesen Blubber weitersagen"))) ?>
        <? elseif($GLOBALS['user']->id !== $thread['user_id']) : ?>
        <a href="#" class="open_reshare_context"><?= Assets::img("icons/16/grey/blubber", array('class' => "text-bottom", 'title' => _("Weitergesagt von diesen Personen"))) ?></a>
        <? endif ?>
        </span>
    </div>
    <? endif ?>
    <div class="avatar_column">
        <div class="avatar">
            <? if ($author_url) : ?>
            <a href="<?= URLHelper::getLink($author_url, array(), true) ?>">
            <? endif ?>
                <div style="background-image: url('<?= $author->getAvatar()->getURL(Avatar::MEDIUM)?>');" class="avatar_image"<?= $author->isNew() ? ' title="'._("Nicht registrierter Nutzer").'"' : "" ?>></div>
            <? if ($author_url) : ?>
            </a>
            <? endif ?>
        </div>
    </div>
    <div class="content_column">
        <div class="timer">
            <a href="<?= URLHelper::getLink('plugins.php/blubber/streams/thread/' . $thread->getId(), array('cid' => $thread['Seminar_id'])) ?>" class="permalink" title="<?= _("Permalink") ?>">
                <span class="time" data-timestamp="<?= (int) $thread['mkdate'] ?>">
                    <?= (date("j.n.Y", $thread['mkdate']) == date("j.n.Y")) ? sprintf(_("%s Uhr"), date("G:i", $thread['mkdate'])) : date("j.n.Y", $thread['mkdate']) ?>
                </span>
            </a>
            <? if (($thread['Seminar_id'] !== $thread['user_id'] && $GLOBALS['perm']->have_studip_perm("tutor", $thread['Seminar_id']))
                    or ($thread['user_id'] === $GLOBALS['user']->id)) : ?>
            <a href="#" class="edit icon" onClick="return false;">
                <?= Assets::img('icons/16/grey/tools.png', tooltip2(_('Bearbeiten')) + array('size' => 14)) ?>
            </a>
            <? endif ?>
        </div>
        <div class="name">
            <? if ($author_url) : ?>
            <a href="<?= URLHelper::getLink($author_url, array(), true) ?>">
            <? endif ?>
                <?= htmlReady($author_name) ?>
            <? if ($author_url) : ?>
            </a>
            <? endif ?>
        </div>
        <div class="content">
            <?
            $content = $thread['description'];
            if ($thread['name'] && strpos($thread['description'], $thread['name']) === false) {
                $content = $thread['name']."\n".$content;
            }
            ?>
            <?= BlubberPosting::format($content) ?>
        </div>
        <div class="additional_tags"><? foreach ($thread->getTags() as $tag) : ?>
            <? if (stripos($content, "#".$tag) === false) : ?>
            <? $link = $thread['context_type'] === "course" ? URLHelper::getLink("plugins.php/blubber/streams/forum", array('cid' => $thread['Seminar_id'], 'hash' => $tag)) : URLHelper::getLink("plugins.php/blubber/streams/global", array('hash' => $tag)) ?>
            <a href="<?= $link ?>"><?= htmlReady("#".$tag) ?></a>
            <? endif ?>
        <? endforeach ?></div>
        <div class="opengraph_area"><?
            if (count(OpenGraphURL::$tempURLStorage)) {
                $og = new OpenGraphURL(OpenGraphURL::$tempURLStorage[0]);
                if (!$og->isNew()) {
                    echo $og->render();
                }
            }
        ?></div>
    </div>
    <ul class="comments">
    <? $postings = $thread->getChildren(0, 4) ?>
    <? if ($postings) : ?>
        <? $more_comments = $thread->getNumberOfChildren() - 3 ?>
        <? if ($more_comments > 0) : ?>
        <li class="more">
            <?= sprintf(ngettext('%u weiterer Kommentar anzeigen', '%u weitere Kommentare anzeigen', $more_comments), $more_comments) ?>
        </li>
        <? endif; ?>
        <? foreach (array_slice(array_reverse($postings), -3) as $posting) : ?>
        <?= $this->render_partial("streams/comment.php", array('posting' => $posting, 'last_visit' => $last_visit)) ?>
        <? endforeach ?>
    <? endif ?>
    </ul>
    <? if ($commentable) : ?>
    <div class="writer">
        <textarea placeholder="<?= _("Kommentiere dies") ?>" aria-label="<?= _("Kommentiere dies") ?>" id="writer_<?= md5(uniqid()) ?>"></textarea>
    </div>
    <? endif ?>
</li>

<? if (@$single_thread): ?>
</ul>
<? endif; ?>