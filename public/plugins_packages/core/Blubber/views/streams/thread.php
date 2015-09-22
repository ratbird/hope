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
<input type="hidden" id="base_url" value="plugins.php/blubber/streams/">
<input type="hidden" id="context_id" value="<?= htmlReady($thread->getId()) ?>">
<input type="hidden" id="stream" value="thread">
<input type="hidden" id="last_check" value="<?= time() ?>">
<input type="hidden" id="user_id" value="<?= htmlReady($GLOBALS['user']->id) ?>">
<input type="hidden" id="stream_time" value="<?= time() ?>">
<input type="hidden" id="browser_start_time" value="">
<input type="hidden" id="orderby" value="mkdate">
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
        <?= _('Zur�ck zur �bersicht') ?>
    </a>
</p>
<? endif ?>

<ul id="blubber_threads" class="coursestream singlethread" aria-live="polite" aria-relevant="additions">
    <?= $this->render_partial("streams/_blubber.php", compact("thread")) ?>
</ul>