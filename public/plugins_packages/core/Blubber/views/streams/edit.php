<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<input type="hidden" id="base_url" value="plugins.php/blubber/streams/">
<style>
table.select td {
    border: thin solid #aaaaaa;
    opacity: 0.3;
    text-align: center;
}
table.select td.selected {
    opacity: 1;
}
#additional_settings table {
    width: 100%;
}
#additional_settings input[type=text], #additional_settings select {
    width: 100%;
}
</style>
<form action="?" method="post" id="edit_stream" enctype="multipart/form-data">
<div id="additional_settings">
    <table>
        <tr>
            <td width="50%">
                <label for="stream_name"><?= _("Titel") ?></label>
            </td>
            <td width="50%">
                <input type="text" name="name" id="stream_name" required value="<?= htmlReady($stream['name']) ?>" style="font-size: 1.2em; font-weight: bold;">
            </td>
        </tr>
        <tr>
            <td>
                <label for="stream_sort"><?= _("Sortierung der Threads") ?></label>
            </td>
            <td>
                <select id="stream_sort" name="sort">
                    <option value="activity"<?= $stream['sort'] === "activity" ? " selected" : "" ?>><?= _("Nach neuster Aktivität") ?></option>
                    <option value="age"<?= $stream['sort'] === "age" ? " selected" : "" ?>><?= _("Nach Alter") ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label for="stream_image"><?= _("Bild") ?></label>
            </td>
            <td>
                <input type="file" name="image" id="stream_image">
            </td>
        </tr>
        <tr>
            <td>
                <label for="stream_defaultstream"><?= _("Standardstream wenn man auf Community klickt") ?></label>
            </td>
            <td>
                <input type="checkbox" id="stream_defaultstream" name="defaultstream" value="1"<?= $stream['defaultstream'] ? " checked" : "" ?>>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("Bisherige Anzahl Threads in diesem Stream") ?>
            </td>
            <td id="number_of_threads">
                <?= $stream->isNew() ? "0" : $stream->fetchNumberOfThreads() ?>
            </td>
        </tr>
    </table>    
</div>

<table class="default select">
    <tbody>
        <tr>
            <th colspan="3">
                <h3><?= _("Sammlung") ?></h3>
                <p class="info"><?= _("Definiere, welche Postings Dein Stream alle umfassen soll.") ?></p>
            </th>
        </tr>
        <tr>
            <td width="33%" class="<?= $stream['pool_courses'] && count($stream['pool_courses']) ? "selected " : "" ?>">
                <label for="pool_courses_check">
                    <div>
                        <?= Assets::img("icons/32/black/seminar.png") ?>
                        <br>
                        <?= _("Veranstaltungen") ?>
                    </div>
                </label>
                <? $label = _("Wählen Sie die Veranstaltungen aus, deren Blubber im Stream auftauchen sollen.") ?>
                <select multiple name="pool_courses[]" style="max-width: 220px;" size="8" 
                        aria-label="<?= $label ?>" title="<?= $label ?>"
                        onClick="if (!jQuery('#pool_courses_check').is(':checked')) { jQuery('#pool_courses_check').trigger('click'); }">
                    <option value="all"<?= in_array("all", (array) $stream['pool_courses']) ? " selected" : "" ?>><?= _("alle") ?></option>
                    <? foreach (User::find($GLOBALS['user']->id)->course_memberships as $membership) : ?>
                    <option value="<?= $membership['Seminar_id'] ?>"<?= in_array($membership['Seminar_id'], (array) $stream['pool_courses']) ? " selected" : "" ?>><?= htmlReady($membership->course['name']) ?></option>
                    <? endforeach ?>
                </select>
                <br>
                <input type="checkbox" name="pool_courses_check" id="pool_courses_check" onChange="jQuery(this).closest('td').toggleClass('selected');" value="1"<?= $stream['pool_courses'] && count($stream['pool_courses']) ? " checked" : "" ?>>
            </td>
            <td width="33%" class="<?= $stream['pool_groups'] && count($stream['pool_groups']) ? "selected " : "" ?>">
                <label for="pool_groups_check">
                    <div>
                        <?= Assets::img("icons/32/black/community.png") ?>
                        <br>
                        <?= _("Kontaktgruppen") ?>
                    </div>
                </label>
                <? $label = _("Wählen Sie die Kontaktgruppen aus, deren Blubber im Stream erscheinen sollen.") ?>
                <select multiple name="pool_groups[]" style="max-width: 220px;" 
                        aria-label="<?= $label ?>" title="<?= $label ?>" size="8"
                        onClick="if (!jQuery('#pool_groups_check').is(':checked')) { jQuery('#pool_groups_check').trigger('click'); }">
                    <option value="all"<?= in_array("all", (array) $stream['pool_groups']) ? " selected" : "" ?>><?= _("alle Buddies") ?></option>
                </select>
                <br>
                <input type="checkbox" name="pool_groups_check" id="pool_groups_check" onChange="jQuery(this).closest('td').toggleClass('selected');" value="1"<?= $stream['pool_groups'] && count($stream['pool_groups'])? " checked" : "" ?>>
            </td>
            <td width="33%" class="<?= $stream['pool_hashtags'] ? "selected " : "" ?>">
                <label for="pool_hashtags_check">
                    <div>
                        <img src="<?= $assets_url."/images/hash.png" ?>">
                        <br>
                        <?= _("Hashtags") ?>
                    </div>
                </label>
                <? $label = _("Bennen Sie beliebig viele mit Leerzeichen getrennte #Hashtags. Alle für Sie potentiell sichtbaren Blubber (öffentlich, privat oder aus Veranstaltungen) mit dem Hashtag tauchen dann im Stream auf.") ?>
                <div>
                <textarea name="pool_hashtags" rows="6" style="width: 98%; max-width: 220px;" 
                          aria-label="<?= $label ?>" title="<?= $label ?>" 
                          placeholder="<?= _("z.B. #opensource #mathematik") ?>"
                          onClick="if (!jQuery('#pool_hashtags_check').is(':checked')) { jQuery('#pool_hashtags_check').trigger('click'); }"
                          ><?= $stream['pool_hashtags'] ? htmlReady("#".implode(" #", $stream['pool_hashtags'])) : "" ?></textarea>
                </div>
                <input type="checkbox" name="pool_hashtags_check" id="pool_hashtags_check" onChange="jQuery(this).closest('td').toggleClass('selected');" value="1"<?= $stream['pool_hashtags'] ? " checked" : "" ?>>
            </td>
        </tr>
    </tbody>
</table>
<table class="default select">
    <tbody>
        <tr>
            <th colspan="5">
                <h3><?= _("Filterung") ?></h3>
                <p class="info"><?= _("Grenze die oben definierte Sammlung an Postings ein mit Filtermöglichkeiten") ?></p>
            </th>
        </tr>
        <tr>
            <td width="20%" class="<?= $stream['filter_type'] && count($stream['filter_type']) ? "selected " : "" ?>">
                <label for="filter_type_check">
                    <div>
                        <?= Assets::img("icons/32/black/doit.png") ?>
                        <br>
                        <?= _("Blubber-Typen") ?>
                    </div>
                </label>
                <? $label = _("Nur Blubber von folgendem Typ einbeziehen.") ?>
                <select multiple name="filter_type[]" style="max-width: 220px;" size="8" 
                        aria-label="<?= $label ?>" title="<?= $label ?>"
                        onClick="if (!jQuery('#filter_type_check').is(':checked')) { jQuery('#filter_type_check').trigger('click'); }">
                    <option value="public"<?= in_array("public", (array) $stream['filter_type']) ? " selected" : "" ?>><?= _("Öffentlich") ?></option>
                    <option value="private"<?= in_array("private", (array) $stream['filter_type']) ? " selected" : "" ?>><?= _("Privat") ?></option>
                    <option value="course"<?= in_array("course", (array) $stream['filter_type']) ? " selected" : "" ?>><?= _("Veranstaltungsblubber") ?></option>
                </select>
                <br>
                <input type="checkbox" name="filter_type_check" id="filter_type_check" onChange="jQuery(this).closest('td').toggleClass('selected');" value="1"<?= $stream['filter_type'] && count($stream['filter_type']) ? " checked" : "" ?>>
            </td>
            <td width="20%" class="<?= $stream['filter_courses'] && count($stream['filter_courses']) ? "selected " : "" ?>">
                <label for="filter_courses_check">
                    <div>
                        <?= Assets::img("icons/32/black/seminar.png") ?>
                        <br>
                        <?= _("Veranstaltungen") ?>
                    </div>
                </label>
                <? $label = _("Wählen Sie Veranstaltungen aus, die nicht im Stream berücksichtigt werden sollen.") ?>
                <select multiple name="filter_courses[]" style="max-width: 220px;" size="8" 
                        aria-label="<?= $label ?>" title="<?= $label ?>"
                        onClick="if (!jQuery('#filter_courses_check').is(':checked')) { jQuery('#filter_courses_check').trigger('click'); }">
                    <option value="all"<?= in_array("all", (array) $stream['filter_courses']) ? " selected" : "" ?>><?= _("alle") ?></option>
                    <? foreach (User::find($GLOBALS['user']->id)->course_memberships as $membership) : ?>
                    <option value="<?= $membership['Seminar_id'] ?>"<?= in_array($membership['Seminar_id'], (array) $stream['filter_courses']) ? " selected" : "" ?>><?= htmlReady($membership->course_name) ?></option>
                    <? endforeach ?>
                </select>
                <br>
                <input type="checkbox" name="filter_courses_check" id="filter_courses_check" onChange="jQuery(this).closest('td').toggleClass('selected');" value="1"<?= $stream['filter_courses'] && count($stream['filter_courses']) ? " checked" : "" ?>>
            </td>
            <td width="20%" class="<?= $stream['filter_groups'] && count($stream['filter_groups']) ? "selected " : "" ?>">
                <label for="filter_groups_check">
                    <div>
                        <?= Assets::img("icons/32/black/community.png") ?>
                        <br>
                        <?= _("Kontaktgruppen") ?>
                    </div>
                </label>
                <? $label = _("Wählen Sie die Kontaktgruppen aus, deren Blubber im Stream nicht erscheinen sollen.") ?>
                <select multiple name="filter_groups[]" style="max-width: 220px;" 
                        aria-label="<?= $label ?>" title="<?= $label ?>" size="8"
                        onClick="if (!jQuery('#filter_groups_check').is(':checked')) { jQuery('#filter_groups_check').trigger('click'); }">
                    <option value="all"<?= in_array("all", (array) $stream['filter_groups']) ? " selected" : "" ?>><?= _("alle Buddies") ?></option>
                </select>
                <br>
                <input type="checkbox" name="filter_groups_check" id="filter_groups_check" onChange="jQuery(this).closest('td').toggleClass('selected');" value="1"<?= $stream['filter_groups'] && count($stream['filter_groups'])? " checked" : "" ?>>
            </td>
            <td width="20%" class="<?= $stream['filter_hashtags'] ? "selected " : "" ?>">
                <label for="filter_hashtags_check">
                    <div>
                        <img src="<?= $assets_url."/images/hash.png" ?>">
                        <br>
                        <?= _("Nur mit Hashtags") ?>
                    </div>
                </label>
                <? $label = _("Bennen Sie beliebig viele mit Leerzeichen getrennte #Hashtags. ") ?>
                <div>
                <textarea name="filter_hashtags" rows="6" style="width: 98%; max-width: 220px;" 
                          aria-label="<?= $label ?>" title="<?= $label ?>" 
                          placeholder="<?= _("z.B. #opensource #mathematik") ?>"
                          onClick="if (!jQuery('#filter_hashtags_check').is(':checked')) { jQuery('#filter_hashtags_check').trigger('click'); }"
                          ><?= $stream['filter_hashtags'] ? htmlReady("#".implode(" #", $stream['filter_hashtags'])) : "" ?></textarea>
                </div>
                <input type="checkbox" name="filter_hashtags_check" id="filter_hashtags_check" onChange="jQuery(this).closest('td').toggleClass('selected');" value="1"<?= $stream['filter_hashtags'] ? " checked" : "" ?>>
            </td>
            <td width="20%" class="<?= $stream['filter_nohashtags'] ? "selected " : "" ?>">
                <label for="filter_nohashtags_check">
                    <div>
                        <img src="<?= $assets_url."/images/hash.png" ?>">
                        <br>
                        <?= _("Ohne Hashtags") ?>
                    </div>
                </label>
                <? $label = _("Folgende Hashtags dürfen nicht in den Blubberpostings des Streams vorkommen.") ?>
                <div>
                <textarea name="filter_nohashtags" rows="6" style="width: 98%; max-width: 220px;" 
                          aria-label="<?= $label ?>" title="<?= $label ?>" 
                          placeholder="<?= _("z.B. #catcontent") ?>"
                          onClick="if (!jQuery('#filter_nohashtags_check').is(':checked')) { jQuery('#filter_nohashtags_check').trigger('click'); }"
                          ><?= $stream['filter_nohashtags'] ? htmlReady("#".implode(" #", $stream['filter_nohashtags'])) : "" ?></textarea>
                </div>
                <input type="checkbox" name="filter_nohashtags_check" id="filter_nohashtags_check" onChange="jQuery(this).closest('td').toggleClass('selected');" value="1"<?= $stream['filter_nohashtags'] ? " checked" : "" ?>>
            </td>
        </tr>
    </tbody>
</table>
    
<?= \Studip\Button::createAccept(_("Speichern"), array()) ?>


</form>

<script>
jQuery(function () {
    jQuery("#edit_stream select, #edit_stream input").bind("change", STUDIP.Blubber.update_streams_threadnumber);
});
</script>

<?
$action = array(
    "kategorie" => _("Aktionen"),
    "eintrag"   => array()
);
if (!$stream->isNew()) {
    $action['eintrag'][] = array(
        "icon" => "icons/16/red/decline",
        "text" => '<a href="'.PluginEngine::getLink($plugin, array('delete_stream' => $stream->getId()), "streams/global").'">'._("Diesen Stream löschen")."</a>"
    );
}

$infobox = array(
    array("kategorie" => _("Informationen"),
          "eintrag"   =>
        array(
            array(
                "icon" => "icons/16/black/info",
                "text" => _("Benutzerdefinierte Streams sind ideal, damit Sie genau die Blubber sehen, die Sie sehen wollen. Für Vielblubberer ein Muss.")
            ),
            array(
                "icon" => "icons/16/black/activity",
                "text" => _("Ein Blubberstream ist immer eine Kombination aus Sammlung von Blubbern und eine anschließende Filterung dieser Sammlung.")
            )
        )
    ),
    (count($action['eintrag']) > 0 ? $action : null)
);
$infobox = array(
    'picture' => StreamAvatar::getAvatar($stream->getId())->getURL(Avatar::NORMAL),
    'content' => $infobox
);