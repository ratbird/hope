<!-- SEARCHBOX -->
<script type="text/javascript">
    Event.observe(window, 'load', function() {
        new Ajax.Autocompleter('vorname',
                               'vorname_choices',
                               'dispatch.php/autocomplete/person/given',
                               { minChars: 3, paramName: 'value', method: 'get' });
        new Ajax.Autocompleter('nachname',
                               'nachname_choices',
                               'dispatch.php/autocomplete/person/family',
                               { minChars: 3, paramName: 'value', method: 'get',
                                 afterUpdateElement: function (input, item) {
                                   var username = encodeURI(item.down('span.username').firstChild.nodeValue);
                                   document.location = STUDIP.ABSOLUTE_URI_STUDIP + "about.php?username=" + username;
                                 }});
    });
</script>
<form action="<?= URLHelper::getLink() ?>" method="post">
<div class="topic"><b><?=_("Suche nach Personen")?></b></div>

<? if($sms_msg):?>
<? parse_msg($sms_msg); ?>
<? endif; ?>

<!-- form zur wahl der institute -->
<div style="width: 100%;">
<table width="100%" cellpadding="2" cellspacing="0">
    <? if (count($institutes)): ?>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td style="white-space: nowrap;">
            <b><?=_("in Einrichtungen:")?></b>
        </td>
        <td width="90%">
        <select name="inst_id" style="min-width: 200px;">
            <option value="0">- - -</option>
            <? foreach ($institutes as $institute): ?>
            <option value="<?=$institute['id']?>" <?= $institute['id'] == $inst_id ? 'selected="selected"' : '' ?>><?= htmlReady($institute['name']) ?></option>
            <? endforeach;?>
        </select>
        </td>
    </tr>
    <? endif ?>
    <!-- form zur wahl der seminare -->
    <? if (count($courses)): ?>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td style="white-space: nowrap;">
            <b><?=_("in Veranstaltungen:")?></b>
        </td>
        <td width="90%">
        <select name="sem_id" style="min-width: 200px;">
            <option value="0">- - -</option>
            <? foreach ($courses as $course): ?>
            <option value="<?=$course['id']?>" <?= $course['id'] == $sem_id ? 'selected="selected"' : '' ?>><?= htmlReady($course['name']) ?></option>
            <? endforeach;?>
        </select>
        </td>
    </tr>
    <? endif ?>
    <!-- form zur freien Suche -->
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
            <b><?=_("Vorname:")?></b>
        </td>
        <td width="90%">
            <input id="vorname" type="text" style="width: 200px" size="20" name="vorname" value="<?= htmlReady($vorname) ?>">
            <div id="vorname_choices" class="autocomplete"></div>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
            <b><?=_("Nachname:")?></b>
        </td>
        <td width="90%">
            <input id="nachname" type="text" style="width: 200px" size="20" name="nachname" value="<?= htmlReady($nachname) ?>">
            <div id="nachname_choices" class="autocomplete"></div>
        </td>
    </tr>
    <tr class="steel2">
        <td colspan="2" align="center">
            <?=makeButton('suchen', 'input', 'Suchen', 'send')?>
            <?=makeButton('zuruecksetzen', 'input', 'zuruecksetzen', 'reset')?>
        </td>
    </tr>
</table>
</div>
</form>
<br>

<!-- RESULTS -->
<? if (isset($users)):?>
<div class="topic"><b><?=_("Ergebnisse:")?></b></div>

<div style="width: 100%;">
<table width="100%" cellpadding="2" cellspacing="0">
    <tr>
        <th align="left">
            <a href="<?= URLHelper::getLink('', compact('vorname', 'nachname', 'sem_id', 'inst_id')) ?>"><?=_("Name")?></a>
        </th>
        <th align="left">
            <? if ($inst_id): ?>
            <?= _("Funktion an der Einrichtung") ?>
            <? elseif ($sem_id): ?>
            <a href="<?= URLHelper::getLink('', compact('vorname', 'nachname', 'sem_id') + array('sortby' => 'status')) ?>"><?= _("Status in der Veranstaltung") ?></a>
            <? else: ?>
            <a href="<?= URLHelper::getLink('', compact('vorname', 'nachname') + array('sortby' => 'perms')) ?>"><?= _("globaler Status") ?></a>
            <? endif; ?>
        </th>
        <th align="right">
            <?=_("Nachricht verschicken")?>
        </th>
    </tr>
    <? foreach ($users as $user): ?>
    <tr class="<?=TextHelper::cycle('cycle_odd', 'cycle_even')?>">
        <td>
            <a href="<?= URLHelper::getLink('about.php', array('username' => $user['username'])) ?>">
                <?= Avatar::getAvatar($user['user_id'], $user['username'])->getImageTag(Avatar::SMALL) ?>
                <?= htmlReady($user['fullname']) ?>
            </a>
        </td>
        <td>
            <?= htmlReady($user['status']) ?>
        </td>
        <td align="right">
            <?= $user['chat'] ?>
            <a href="<?= URLHelper::getLink('sms_send.php', array('sms_source_page' => 'browse.php', 'rec_uname' => $user['username'])) ?>">
                <img src="<?=Assets::url()?>images/nachricht1.gif" title="<?=_("Nachricht an User verschicken")?>">
            </a>
        </td>
    </tr>
    <? endforeach; ?>
</table>
</div>
<? elseif ($vorname != '' || $nachname != ''): ?>
    <?= MessageBox::info(_('Es wurde niemand gefunden.')) ?>
<? elseif (isset($vorname) || isset($nachname)): ?>
    <?= MessageBox::error(_('Bitte einen Vor- oder Nachnamen eingeben.')) ?>
<? endif; ?>

<?
$infobox = array(
    'picture' => 'infoboxes/board2.jpg',
    'content' => array(
        array("kategorie" => _("Information:"),
            "eintrag" => array(
                array(
                    "icon" => 'ausruf_small.gif',
                    "text" => _("Hier können Sie die Homepages aller NutzerInnen abrufen, die im System registriert sind.")
                ),
                array(
                    "icon" => 'ausruf_small.gif',
                    "text" => _("Sie erhalten auf den Homepages von MitarbeiternInnen an Einrichtungen auch weiterf&uuml;hrende Informationen, wie Sprechstunden und Raumangaben.")
                ),
                array(
                    "icon" => 'ausruf_small.gif',
                    "text" => _("Wählen Sie den gewünschten Bereich aus oder suchen Sie nach einem Namen!")
                )
            )
        ),
        array("kategorie" => _("Ansichten:"),
            "eintrag" => array(
                array(
                    "icon" => 'suche2.gif',
                    "text" => '<a href="'.URLHelper::getLink('score.php').'">'._("Zur Stud.IP-Rangliste").'</a>'
                )
            )
        )
    )
);
?>
