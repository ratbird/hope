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

$infobox = array(
    array("kategorie" => _("Informationen"),
          "eintrag"   =>
        array(
            array(
                "icon" => "icons/16/black/info",
                "text" => _("Alle öffentlichen Nachrichten dieses Kontakts als Feed und in Echtzeit.")
            ),
            array(
                "icon" => "icons/16/black/date",
                "text" => _("Kein Seitenneuladen nötig. Du siehst sofort, wenn sich was getan hat.")
            )
        )
    ),
    ($isBuddy or !isset($isBuddy)) ? null : array(
        "kategorie" => _("Aktionen"),
          "eintrag"   =>
        array(
            array(
                "icon" => "icons/16/black/add/person",
                "text" => '<a id="blubber_add_buddy" href="" onClick="STUDIP.Blubber.followUser(); return false;">'._("Füge diesen Kontakt als Buddy hinzu und erhalte seine Nachrichten im globalen Blubberstream").'</a>'
            )
        )
    ),
    array("kategorie" => _("Profifunktionen"),
          "eintrag"   =>
        array(
            array(
                "icon" => "icons/16/black/forum",
                "text" => _("Drücke Shift-Enter, um einen Absatz einzufügen.")
            ),
            array(
                "icon" => "icons/16/black/smiley",
                "text" => sprintf(_("Verwende beim Tippen %sTextformatierungen%s und %sSmileys.%s"),
                        '<a href='.htmlReady(format_help_url("Basis/VerschiedenesFormat")).' target="_blank">', '</a>',
                        '<a href="'.URLHelper::getLink("dispatch.php/smileys").'" target="_blank">', '</a>')
            ),
            array(
                "icon" => "icons/16/black/upload",
                "text" => _("Ziehe Dateien per Drag & Drop in ein Textfeld, um sie hochzuladen und zugleich zu verlinken.")
            ),
            array(
                "icon" => "icons/16/black/person",
                "text" => _("Erwähne jemanden mit @username oder @\"Vorname Nachname\". Diese Person wird dann speziell auf Deinen Blubber hingewiesen.")
            ),
            array(
                "icon" => "icons/16/black/hash",
                "text" => sprintf(_("Schreibe %s#Hashtags%s in Blubber und Kommentare."), '<a href="'.URLHelper::getLink("plugins.php/blubber/streams/global", array('hash' => "hashtags")).'">', "</a>")
            )
        )
    )
);
$infobox = array(
    'picture' => $user->getAvatar()->getURL(Avatar::NORMAL),
    'content' => $infobox
);