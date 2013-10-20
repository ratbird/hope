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
<input type="hidden" id="last_check" value="<?= time() ?>">
<input type="hidden" id="base_url" value="plugins.php/blubber/streams/">
<input type="hidden" id="user_id" value="<?= htmlReady($GLOBALS['user']->id) ?>">
<input type="hidden" id="stream" value="course">
<input type="hidden" id="context_id" value="<?= $_SESSION['SessionSeminar'] ?>">
<input type="hidden" id="stream_time" value="<?= time() ?>">
<input type="hidden" id="search" value="<?= htmlReady($search) ?>">
<input type="hidden" id="browser_start_time" value="">
<input type="hidden" id="loaded" value="1">
<div id="editing_question" style="display: none;"><?= _("Wollen Sie den Beitrag wirklich bearbeiten?") ?></div>

<div id="threadwriter">
    <div id="context_selector" style="display: none;">
        <input type="hidden" name="context_type" value="course" checked="checked">
        <input type="hidden" name="context" value="<?= $_SESSION['SessionSeminar'] ?>">
    </div>
    <textarea id="new_posting" placeholder="<?= _("Schreib was, frag was.") ?>"><?= $search ? htmlReady($search) : "" ?></textarea>
</div>

<? if ($GLOBALS['user']->id === "nobody") : ?>
<div id="identity_window_title" style="display: none;"><?= _("Namen eingeben") ?></div>
<div id="identity_window" style="display: none;">
    <input type="hidden" id="identity_window_textarea_id" value="">
    <table>
        <tbody>
            <tr>
                <td><?= _("Name") ?></td>
                <td><input type="text" id="anonymous_name" value="<?= htmlReady($_SESSION['anonymous_name']) ?>"></td>
            </tr>
            <tr>
                <td><?= _("Email") ?></td>
                <td><input type="text" id="anonymous_email" value="<?= htmlReady($_SESSION['anonymous_email']) ?>"></td>
            </tr>
            <tr>
                <? $_SESSION['blubber_anonymous_security'] or $_SESSION['blubber_anonymous_security'] = substr(md5(uniqid()), 0, 5) ?>
                <td><?= _("Sicherheitsfrage! Schreibe folgendes r�ckw�rts: ").strrev($_SESSION['blubber_anonymous_security']) ?></td>
                <td><input type="text" id="anonymous_security" value="<?= $_SESSION['anonymous_email'] ? htmlReady($_SESSION['blubber_anonymous_security']) : "" ?>"></td>
            </tr>
            <tr>
                <td></td>
                <td><?= \Studip\Button::create(_("abschicken"), array('onclick' => "STUDIP.Blubber.submitAnonymousPosting();")) ?></td>
            </tr>
        </tbody>
    </table>
    <br>
</div>
<? endif ?>
<ul id="blubber_threads" class="coursestream" aria-live="polite" aria-relevant="additions">
    <? foreach ($threads as $thread) : ?>
    <?= $this->render_partial("streams/thread.php", array('thread' => $thread)) ?>
    <? endforeach ?>
    <? if ($more_threads) : ?>
    <li class="more"><?= Assets::img("ajax_indicator_small.gif") ?></li>
    <? endif ?>
</ul>

<?

$infobox = array(
    array("kategorie" => _("Informationen"),
          "eintrag"   =>
        array(
            array(
                "icon" => "icons/16/black/info",
                "text" => _("Ein Echtzeitkommunikations-Forum.")
            ),
            array(
                "icon" => "icons/16/black/date",
                "text" => _("Kein Seitenneuladen n�tig. Du siehst sofort, wenn sich was getan hat.")
            )
        )
    ),
    array("kategorie" => _("Profifunktionen"),
          "eintrag"   =>
        array(
            array(
                "icon" => "icons/16/black/forum",
                "text" => _("Dr�cke Shift-Enter, um einen Absatz einzuf�gen.")
            ),
            array(
                "icon" => "icons/16/black/smiley",
                "text" => sprintf(_("Verwende beim Tippen %sTextformatierungen%s und %sSmileys.%s"),
                        '<a href="'.htmlReady(format_help_url("Basis/VerschiedenesFormat")).'" target="_blank">', '</a>',
                        '<a href="'.URLHelper::getLink("dispatch.php/smileys").'" target="_blank">', '</a>')
            ),
            array(
                "icon" => "icons/16/black/upload",
                "text" => _("Ziehe Dateien per Drag & Drop in ein Textfeld, um sie hochzuladen und zugleich zu verlinken.")
            ),
            array(
                "icon" => "icons/16/black/person",
                "text" => _("Erw�hne jemanden mit @username oder @\"Vorname Nachname\". Diese Person wird dann speziell auf Deinen Blubber hingewiesen.")
            ),
            array(
                "icon" => "icons/16/black/hash",
                "text" => sprintf(_("Schreibe %s#Hashtags%s in Blubber und Kommentare."), '<a href="'.URLHelper::getLink("plugins.php/blubber/streams/global", array('hash' => "hashtags")).'">', "</a>")
            )
        )
    )
);
if (count($tags) && $tags[0]) {
    $text = "";
    foreach ($tags as $tag) {
        $text .= '<a href="'.URLHelper::getLink("plugins.php/blubber/streams/forum", array('cid' => $_SESSION['SessionSeminar'], 'hash' => $tag)).'">#'.htmlReady($tag).'</a> ';
    }
    $infobox[] = array(
        "kategorie" => _("Tagwolke"),
        "eintrag"   => array(
            array(
                'icon' => "icons/16/black/link-intern",
                'text' => $text
            )
        )
    );
}
$infobox = array(
    'picture' => CourseAvatar::getAvatar($course_id)->getURL(Avatar::NORMAL),
    'content' => $infobox
);