<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
if (!$last_visit) {
    $last_visit = object_get_visit($_SESSION['SessionSeminar'], "forum");
}
$author = $posting->getUser();
$author_name = $author->getName();
$author_url = $author->getURL();
?>
<li class="comment posting<?= $posting['mkdate'] > $last_visit ? " new" : "" ?>" id="posting_<?= $posting->getId() ?>" mkdate="<?= htmlReady($posting['mkdate']) ?>" data-autor="<?= htmlReady($posting['user_id']) ?>">
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
            <span class="time" data-timestamp="<?= (int) $posting['mkdate'] ?>">
                <?= (date("j.n.Y", $posting['mkdate']) == date("j.n.Y")) ? sprintf(_("%s Uhr"), date("G:i", $posting['mkdate'])) : date("j.n.Y", $posting['mkdate']) ?>
            </span>
            <? if ($GLOBALS['perm']->have_studip_perm("tutor", $posting['Seminar_id']) or ($posting['user_id'] === $GLOBALS['user']->id)) : ?>
            <a href="#" class="edit" onClick="return false;" style="vertical-align: middle; opacity: 0.6;">
                <?= Assets::img('icons/16/grey/tools.png', tooltip2(_('Bearbeiten')) + array('size' => '14')) ?>
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
            <?= BlubberPosting::format($posting['description']) ?>
        </div>
        <div class="opengraph_area"><? 
            if (count(OpenGraphURL::$tempURLStorage)) {
                $og = new OpenGraphURL(OpenGraphURL::$tempURLStorage[0]);
                if (!$og->isNew()) {
                    echo $og->render();
                } 
            } 
        ?></div>
    </div>
</li>