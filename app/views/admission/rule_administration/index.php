<?php
//Infobox:
$actions = array();
$actions[] = array(
              "icon" => 'icons/16/black/add/plugin.png',
              "text" => _('Weitere Anmelderegeln installieren').
                        $this->render_partial('admission/rule_administration/upload-drag-and-drop'));
$info = array();
$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Sie können hier neue Anmelderegeln hochladen und installieren.");
$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Legen Sie fest, welche der installierten ".
                        "Anmelderegeln im System benutzt werden dürfen.");

$infobox = array(
    array("kategorie" => _('Informationen:'),
          "eintrag" => $info
    ),
    array("kategorie" => _("Aktionen:"),
          "eintrag"   => $actions
    )
);
$infobox = array('content' => $infobox,
                 'picture' => 'infobox/administration.png'
);

if (isset($flash['error'])) {
    echo MessageBox::error($flash['error'], $flash['error_detail']);
} elseif (isset($flash['success'])) {
    echo MessageBox::success($flash['success']);
}
?>
<?= $this->render_partial('dialog/confirm_dialog') ?>
<?php
if ($ruleTypes) {
?>
<table class="default" id="admissionrules">
	<caption><?= _('Installierte Anmelderegeln:') ?></caption>
    <thead>
        <th><?= _('aktiv?') ?></th>
        <th><?= _('Art der Anmelderegel') ?></th>
        <th><?= _('Aktionen') ?></th>
    </thead>
    <tbody>
    <?php
    foreach ($ruleTypes as $type => $details) {
    	if ($details['active']) {
    		$text = _('Diese Regel ist aktiv. Klicken Sie hier, um die Einstellungen zu bearbeiten.');
			$img = 'checkbox-checked';
    	} else {
    		$text = _('Diese Regel ist inaktiv. Klicken Sie hier, um die Einstellungen zu bearbeiten.');
			$img = 'checkbox-unchecked';
    	}
    ?>
    <tr id="ruletype_<?= $type ?>">
		<td>
            <a href="<?= $controller->url_for('admission/ruleadministration/check_activation', $type) ?>" rel="lightbox">
                <?= Assets::img('icons/16/blue/'.$img.'.png', 
                    array(
                    	'alt' => $text,
                    	'title' => $text
					)); ?>
            </a>
		</td>
        <td>
            <b><?= $details['name'] ?></b> (<?= $type ?>)
            <br/>
            <?= $details['description'] ?>
        </td>
        <td>
            <a href="<?= $controller->url_for('admission/ruleadministration/download', $type) ?>">
                <?= Assets::input('icons/16/blue/download.png', 
                    array('type' => 'image', 'name' => 'activate_'.$type,
                        'alt' => 'Regeldefinition als ZIP herunterladen',
                        'title' => 'Regeldefinition als ZIP herunterladen')); ?>
            </a>
            <?php if ($details['deleteable']) { ?>
            <a href="<?= $controller->url_for('admission/ruleadministration/uninstall', $type) ?>"
                onclick="return STUDIP.Dialogs.showConfirmDialog('<?= 
                    sprintf(_('Soll die Anmelderegel vom Typ %s wirklich '.
                    'gelöscht werden? Dabei werden auch alle damit verbundenen '.
                    'Daten entfernt, z.B. Zuordnungen zu Anmeldesets oder '.
                    'Daten von Studierenden!'), $details['name']) ?>', '<?= 
                    URLHelper::getURL('dispatch.php/admission/ruleadministration/uninstall/'.
                    $type, array('really' => 1)) ?>')">
                <?= Assets::img('icons/16/blue/trash.png', 
                    array('alt' => _('Anmelderegel löschen'), 
                          'title' => _('Anmelderegel löschen'))); ?>
            </a>
            <?php } ?>
        </td>
    </tr>
    <?php } ?>
    </tbody>
</table>
<br/>
<?php
} else {
?>
<?= MessageBox::info(sprintf(_('Sie haben noch keine Anmelderegeln installiert!'))); ?>
<?php
}
?>