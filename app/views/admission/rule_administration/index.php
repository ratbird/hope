<?php
//Infobox:
$info = array();
$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Legen Sie fest, welche der installierten ".
                        "Anmelderegeln im System benutzt werden d�rfen.");
$info[] = array(
              "icon" => "icons/16/black/checkbox-checked.png",
              "text" => "Sie k�nnen Anmelderegeln systemweit oder gezielt ".
                        "f�r einzelne Einrichtungen freischalten.");

$infobox = array(
    array("kategorie" => _('Informationen:'),
          "eintrag" => $info
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