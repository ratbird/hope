<?php
//Infobox:
$actions = array();
$actions[] = array(
              "icon" => "icons/16/black/add.png",
              "text" => '<a href="' .
                        $controller->url_for('admission/courseset/configure').
                        '">' . _("Anmeldeset anlegen") . '</a>');
$info = array();
$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Anmeldesets legen fest, wer sich zu den zugeordneten ".
                        "Veranstaltungen anmelden darf.");
$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Hier sehen Sie alle Anmeldesets, auf die Sie Zugriff ".
                        "haben.");

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
?>
<?= $this->render_partial('dialog/confirm_dialog') ?>
<h2><?= _('Anmeldesets') ?></h2>
<?= $this->render_partial('admission/courseset/_institute_choose.php') ?>
<?php
if ($coursesets) {
?>
<div id="coursesets">
    <?php foreach ($coursesets as $courseset) { ?>
    <div id="courseset_<?= $courseset->getId() ?>" class="hover_box">
        <a href="#" onclick="return STUDIP.Admission.toggleDetails('courseset_arrow_<?= $courseset->getId() ?>', 'courseset_details_<?= $courseset->getId() ?>')">
            <?= Assets::img('icons/16/blue/arr_1right.png',
                array('id' => 'courseset_arrow_'.$courseset->getId(),
                'align' => 'top', 'rel' => Assets::image_path('icons/16/blue/arr_1down.png'))) ?>
            <?= htmlReady($courseset->getName()) ?>
        </a>
        <span class="hover_symbols">
	        <a href="<?= URLHelper::getURL('dispatch.php/admission/courseset/configure/'.$courseset->getId()); ?>">
	            <?= Assets::img('icons/16/blue/edit.png',
	                array('alt' => _('Anmeldeset bearbeiten'),
	                      'title' => _('Anmeldeset bearbeiten'))); ?>
	        </a>
	        <a href="<?= $controller->url_for('admission/courseset/delete',
	            $courseset->getId()) ?>"
	            onclick="return STUDIP.Dialogs.showConfirmDialog('<?=
	                sprintf(_('Soll das Anmeldeset %s wirklich gelöscht werden?'), htmlReady($courseset->getName())) ?>', '<?=
	                URLHelper::getURL('dispatch.php/admission/courseset/delete/'.
	                $courseset->getId(), array('really' => 1)) ?>')">
	            <?= Assets::img('icons/16/blue/trash.png',
	                array('alt' => _('Anmeldeset löschen'),
	                      'title' => _('Anmeldeset löschen'))); ?>
	        </a>
        </span>
    </div>
    <div id="courseset_details_<?= $courseset->getId() ?>" style="display: none; margin-left: 20px;">
        <?= $courseset->toString(true) ?>
    </div>
    <?php } ?>
</div>
<?php
} else {
?>
<?= MessageBox::info(sprintf(_('Es wurden keine Anmeldesets gefunden. Sie können ein '.
    'neues %sAnmeldeset anlegen%s.'), '<a href="'.
    $controller->url_for('admission/courseset/configure').'">',
    '</a>')); ?>
<?php
}
?>