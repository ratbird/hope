<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
?>
<div id="messageboxes"></div>
<input type="hidden" id="last_check" value="<?= time() ?>">
<input type="hidden" id="base_url" value="plugins.php/blubber/streams/">
<input type="hidden" id="user_id" value="<?= htmlReady($GLOBALS['user']->id) ?>">
<input type="hidden" id="context_id" value="<?= htmlReady($user->getId()) ?>">
<input type="hidden" id="extern" value="<?= is_a($user, "BlubberExternalContact") ? 1 : 0 ?>">
<input type="hidden" id="stream" value="profile">
<input type="hidden" id="stream_time" value="<?= time() ?>">
<input type="hidden" id="browser_start_time" value="">
<input type="hidden" id="loaded" value="1">
<input type="hidden" id="orderby" value="mkdate">
<div id="editing_question" style="display: none;"><?= _("Wollen Sie den Beitrag wirklich bearbeiten?") ?></div>

<? if ($user->getId() === $GLOBALS['user']->id) : ?>
<div id="threadwriter">
    <div id="context_selector" style="display: none;">
        <input type="hidden" name="context_type" value="public" checked="checked">
        <input type="hidden" name="context" value="<?= htmlReady($user->getId()) ?>">
    </div>
    <textarea id="new_posting" placeholder="<?= _("Schreib was, frag was.") ?>"></textarea>
</div>
<? endif ?>

<ul id="blubber_threads" class="profilestream" aria-live="polite" aria-relevant="additions">
    <? foreach ($threads as $thread) : ?>
    <?= $this->render_partial("streams/thread.php", array('thread' => $thread)) ?>
    <? endforeach ?>
    <? if ($more_threads) : ?>
    <li class="more"><?= Assets::img("ajax_indicator_small.gif", array('alt' => "loading")) ?></li>
    <? endif ?>
</ul>

<?

$sidebar = Sidebar::get();
$sidebar->setImage("sidebar/blubber-sidebar");
$sidebar->setContextAvatar(Avatar::getAvatar($user->getId()));
URLHelper::addLinkParam('user_id', $user->getId());
$controller->addTagCloudWidgetToSidebar($tags, 'profile');
