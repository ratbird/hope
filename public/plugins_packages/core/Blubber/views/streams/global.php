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
<style>
    #layout_container {
        min-width: 900px;
    }
</style>
<input type="hidden" id="last_check" value="<?= time() ?>">
<input type="hidden" id="base_url" value="plugins.php/blubber/streams/">
<input type="hidden" id="user_id" value="<?= htmlReady($GLOBALS['user']->id) ?>">
<input type="hidden" id="stream" value="all">
<input type="hidden" id="stream_time" value="<?= time() ?>">
<input type="hidden" id="search" value="<?= htmlReady($search) ?>">
<input type="hidden" id="browser_start_time" value="">
<script>jQuery(function () { jQuery("#browser_start_time").val(Math.floor(new Date().getTime() / 1000)); });</script>
<input type="hidden" id="loaded" value="1">
<div id="editing_question" style="display: none;"><?= _("Wollen Sie den Beitrag wirklich bearbeiten?") ?></div>

<div id="threadwriter" class="globalstream">
    <div class="row">
        <div class="context_selector" title="<?= _("Kontext der Nachricht auswählen") ?>">
            <?= Assets::img("icons/16/blue/seminar", array('class' => "seminar")) ?>
            <?= Assets::img("icons/16/blue/community", array('class' => "community")) ?>
        </div>
        <textarea id="new_posting" placeholder="<?= _("Schreib was, frag was.") ?>"><?= ($search ? htmlReady($search)." " : "").(Request::get("mention") ? "@".htmlReady(Request::username("mention")).", " : "") ?></textarea>
    </div>
    <div id="context_selector_title" style="display: none;"><?= _("Kontext auswählen") ?></div>
    <div id="context_selector" style="display: none;">
        <input type="hidden" name="content_type" id="context_type" value="">
        <table style="width: 100%">
            <tbody>
                <tr onMousedown="$('#context_type').val('public'); $(this).parent().find('.selected').removeClass('selected'); $(this).addClass('selected'); ">
                    <td style="text-align: center; width: 25%">
                        <label>
                            <?= Assets::img("icons/16/black/rss", array('class' => "text-bottom")) ?>
                            <br>
                            <?= _("Öffentlich") ?>
                        </label>
                    </td>
                    <td style="width: 75%">
                        <?= _("Dein Beitrag wird allen angezeigt, die Dich als Buddy hinzugefügt haben.") ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><hr></td>
                </tr>
                <tr onMousedown="$('#context_type').val('private'); $(this).parent().find('.selected').removeClass('selected'); $(this).addClass('selected'); ">
                    <td style="text-align: center;">
                        <label>
                            <?= Assets::img("icons/16/black/mail", array('class' => "text-bottom")) ?>
                            <br>
                            <?= _("Privat") ?>
                        </label>
                    </td>
                    <td>
                        <? if (count($contact_groups)) : ?>
                        <?= _("An Kontaktgruppe(n)") ?><br>
                        <select multiple name="contact_group[]" id="contact_groups" style="width: 100%" size="<?= count($contact_groups) <= 4 ? count($contact_groups) : "4"  ?>">
                            <? foreach ($contact_groups as $group) : ?>
                            <option value="<?= htmlReady($group['statusgruppe_id']) ?>"><?= htmlReady($group['name']) ?></option>
                            <? endforeach ?>
                        </select>
                        <? else : ?>
                        <a href="<?= URLHelper::getLink("contact_statusgruppen.php") ?>"><?= _("Legen Sie eine Kontaktgruppe an, um an mehrere Kontakte zugleich zu blubbern.") ?></a>
                        <? endif ?>
                        <br>
                        <?= _("Fügen Sie einzelne Personen mittels @Nutzernamen im Text der Nachricht oder der Kommentare hinzu.") ?>
                    </td>
                </tr>
                <? $mycourses = BlubberPosting::getMyBlubberCourses() ?>
                <? if (count($mycourses)) : ?>
                <tr>
                    <td colspan="2"><hr></td>
                </tr>
                <tr onMousedown="$('#context_type').val('course'); $(this).parent().find('.selected').removeClass('selected'); $(this).addClass('selected'); ">
                    <td style="text-align: center;">
                        <label>
                            <?= Assets::img("icons/16/black/seminar", array('class' => "text-bottom")) ?>
                            <br>
                            <?= _("Veranstaltung") ?>
                        </label>
                    </td>
                    <td>
                        <?= _("In Veranstaltung") ?>
                        <select name="context">
                            <? foreach (BlubberPosting::getMyBlubberCourses() as $course_id) : ?>
                            <? $seminar = new Seminar($course_id) ?>
                            <option value="<?= htmlReady($course_id) ?>"><?= htmlReady($seminar->getName()) ?></option>
                            <? endforeach ?>
                        </select>
                    </td>
                </tr>
                <? endif ?>
            </tbody>
        </table>
        <div>
            <button class="button" id="submit_button" style="display: none;" onClick="STUDIP.Blubber.prepareSubmitGlobalPosting();">
                <?= _("abschicken") ?>
            </button>
        </div>
        <br>
    </div>
</div>



<div id="context_background">
<ul id="forum_threads" class="globalstream" aria-live="polite" aria-relevant="additions">
    <? foreach ($threads as $thread) : ?>
    <?= $this->render_partial("streams/thread.php", array('thread' => $thread)) ?>
    <? endforeach ?>
    <? if ($more_threads) : ?>
    <li class="more">...</li>
    <? endif ?>
</ul>
</div>

<?

$infobox = array(
    array("kategorie" => _("Informationen"),
          "eintrag"   =>
        array(
            array(
                "icon" => "icons/16/black/info",
                "text" => _("Ein Echtzeit-ActivityFeed Deiner Freunde und Veranstaltungen.")
            ),
            array(
                "icon" => "icons/16/black/date",
                "text" => _("Kein Seitenneuladen nötig. Du siehst sofort, wenn sich was getan hat.")
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
                        '<a href="http://docs.studip.de/help/2.2/de/Basis/VerschiedenesFormat" target="_blank">', '</a>',
                        '<a href="'.URLHelper::getLink("dispatch.php/smileys").'" target="_blank">', '</a>')
            ),
            array(
                "icon" => "icons/16/black/upload",
                "text" => _("Ziehe Dateien per Drag & Drop in ein Textfeld, um sie hochzuladen und zugleich zu verlinken.")
            ),
            array(
                "icon" => "icons/16/black/person",
                "text" => _("Erwähne jemanden mit @username oder @\"Vorname Nachname\". Diese Person wird dann speziell auf Deinen Blubber hingewiesen.")
            )
        )
    )
);
$infobox = array(
    'picture' => $assets_url . "/images/foam.png",
    'content' => $infobox
);