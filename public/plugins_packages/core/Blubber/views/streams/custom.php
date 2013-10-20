<style>
    #layout_container {
        min-width: 900px;
    }
</style>
<input type="hidden" id="last_check" value="<?= time() ?>">
<input type="hidden" id="base_url" value="plugins.php/blubber/streams/">
<input type="hidden" id="user_id" value="<?= htmlReady($GLOBALS['user']->id) ?>">
<input type="hidden" id="context_id" value="<?= $stream->getId() ?>">
<input type="hidden" id="stream" value="custom">
<input type="hidden" id="stream_time" value="<?= time() ?>">
<input type="hidden" id="browser_start_time" value="">
<script>jQuery(function () { jQuery("#browser_start_time").val(Math.floor(new Date().getTime() / 1000)); });</script>
<input type="hidden" id="loaded" value="1">
<div id="editing_question" style="display: none;"><?= _("Wollen Sie den Beitrag wirklich bearbeiten?") ?></div>

<div id="threadwriter" class="globalstream">
    <div class="row">
        <div class="context_selector select" title="<?= _("Kontext der Nachricht auswählen") ?>">
            <?= Assets::img("icons/32/blue/group2", array('class' => "select")) ?>
            <img src="<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>/plugins_packages/core/Blubber/assets/images/public_32_blue.png" class="public">
            <?= Assets::img("icons/32/blue/group3", array('class' => "private")) ?>
            <?= Assets::img("icons/32/blue/seminar", array('class' => "seminar")) ?>
        </div>
        <textarea style="margin-top: 7px;" id="new_posting" placeholder="<?= _("Schreib was, frag was.") ?>" aria-label="<?= _("Schreib was, frag was.") ?>"><?= ($search ? htmlReady($search)." " : "").(Request::get("mention") ? "@".htmlReady(Request::username("mention")).", " : "") ?></textarea>
    </div>
    <div id="context_selector_title" style="display: none;"><?= _("Kontext auswählen") ?></div>
    <div id="context_selector" style="display: none;">
        <input type="hidden" name="content_type" id="context_type" value="">
        <table style="width: 100%">
            <tbody>
                <tr onMousedown="$('#context_type').val('public'); $('#threadwriter .context_selector').removeAttr('class').addClass('public context_selector'); $(this).parent().find('.selected').removeClass('selected'); $(this).addClass('selected'); ">
                    <td style="text-align: center; width: 15%">
                        <label>
                            <img src="<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>/plugins_packages/core/Blubber/assets/images/public_32.png" class="text-bottom">
                            <br>
                            <?= _("Öffentlich") ?>
                        </label>
                    </td>
                    <td style="width: 70%">
                        <?= _("Dein Beitrag wird allen angezeigt, die Dich als Buddy hinzugefügt haben.") ?>
                    </td>
                    <td style="width: 15%">
                        <?= Assets::img("icons/16/black/checkbox-checked", array('class' => "text-bottom check")) ?>
                        <?= Assets::img("icons/16/black/checkbox-unchecked", array('class' => "text-bottom uncheck")) ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="3"><hr></td>
                </tr>
                <tr onMousedown="$('#context_type').val('private'); $('#threadwriter .context_selector').removeAttr('class').addClass('private context_selector'); $(this).parent().find('.selected').removeClass('selected'); $(this).addClass('selected'); ">
                    <td style="text-align: center;">
                        <label>
                            <?= Assets::img("icons/32/black/group3", array('class' => "text-bottom")) ?>
                            <br>
                            <?= _("Privat") ?>
                        </label>
                    </td>
                    <td>
                        <? if (count($contact_groups)) : ?>
                        <label>
                        <?= _("An Kontaktgruppe(n)") ?><br>
                        <select multiple name="contact_group[]" id="contact_groups" style="width: 100%" size="<?= count($contact_groups) <= 4 ? count($contact_groups) : "4"  ?>">
                            <? foreach ($contact_groups as $group) : ?>
                            <option value="<?= htmlReady($group['statusgruppe_id']) ?>"><?= htmlReady($group['name']) ?></option>
                            <? endforeach ?>
                        </select>
                        </label>
                        <? else : ?>
                        <a href="<?= URLHelper::getLink("contact_statusgruppen.php") ?>"><?= _("Legen Sie eine Kontaktgruppe an, um an mehrere Kontakte zugleich zu blubbern.") ?></a>
                        <? endif ?>
                        <br>
                        <?= _("Fügen Sie einzelne Personen mittels @Nutzernamen im Text der Nachricht oder der Kommentare hinzu.") ?>
                    </td>
                    <td style="width: 15%">
                        <?= Assets::img("icons/16/black/checkbox-checked", array('class' => "text-bottom check")) ?>
                        <?= Assets::img("icons/16/black/checkbox-unchecked", array('class' => "text-bottom uncheck")) ?>
                    </td>
                </tr>
                <? $mycourses = BlubberPosting::getMyBlubberCourses() ?>
                <? if (count($mycourses)) : ?>
                <tr>
                    <td colspan="3"><hr></td>
                </tr>
                <tr onMousedown="$('#context_type').val('course'); $('#threadwriter .context_selector').removeAttr('class').addClass('seminar context_selector'); $(this).parent().find('.selected').removeClass('selected'); $(this).addClass('selected'); ">
                    <td style="text-align: center;">
                        <label>
                            <?= Assets::img("icons/32/black/seminar", array('class' => "text-bottom")) ?>
                            <br>
                            <?= _("Veranstaltung") ?>
                        </label>
                    </td>
                    <td>
                        <label>
                        <?= _("In Veranstaltung") ?>
                        <select name="context">
                            <? foreach (BlubberPosting::getMyBlubberCourses() as $course_id) : ?>
                            <? $seminar = new Seminar($course_id) ?>
                            <option value="<?= htmlReady($course_id) ?>"><?= htmlReady($seminar->getName()) ?></option>
                            <? endforeach ?>
                        </select>
                        </label>
                    </td>
                    <td style="width: 15%">
                        <?= Assets::img("icons/16/black/checkbox-checked", array('class' => "text-bottom check")) ?>
                        <?= Assets::img("icons/16/black/checkbox-unchecked", array('class' => "text-bottom uncheck")) ?>
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
<ul id="blubber_threads" class="globalstream" aria-live="polite" aria-relevant="additions">
    <? foreach ($threads as $thread) : ?>
    <?= $this->render_partial("streams/thread.php", array('thread' => $thread)) ?>
    <? endforeach ?>
    <? if ($more_threads) : ?>
    <li class="more"><?= Assets::img("ajax_indicator_small.gif") ?></li>
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
                "text" => _("Ein Echtzeit-Feed, den Sie selbst erstellt haben.")
            ),
            array(
                "icon" => "icons/16/black/edit",
                "text" => '<a href="'.PluginEngine::getLink($plugin, array(), 'streams/edit/'.$stream->getId()).'">'._("Diesen Stream bearbeiten.").'</a>'
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
    'picture' => StreamAvatar::getAvatar($stream->getId())->getURL(Avatar::NORMAL),
    'content' => $infobox
);