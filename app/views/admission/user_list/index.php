<?php
//Infobox:
$actions = array();
$actions[] = array(
              "icon" => "icons/16/black/add.png",
              "text" => '<a href="' .
                        $controller->url_for('admission/userlist/configure').
                        '">' . _("Nutzerliste anlegen") . '</a>');
$info = array();
$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Nutzerlisten erfassen eine Menge von Personen, die ".
                        "mit modifizierten Chancen in die Platzverteilung bei ".
                        "Anmeldeverfahren eingehen. Dies k�nnen z.B. ".
                        "H�rtef�lle sein, die bevorzugt einen Platz in ".
                        "Veranstaltungen erhalten sollen.");
$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Hier sehen Sie alle Nutzerlisten, auf die Sie Zugriff ".
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
<h2><?= _('Nutzerlisten') ?></h2>
<?php
if ($userlists) {
?>
<div id="userlists">
    <?php foreach ($userlists as $list) { ?>
    <div id="userlist_<?= $list->getId() ?>">
        <a href="#" onclick="return STUDIP.Admission.toggleDetails('userlist_arrow_<?= $list->getId() ?>', 'userlist_details_<?= $list->getId() ?>')">
            <?= Assets::img('icons/16/blue/arr_1right.png',
                array('id' => 'userlist_arrow_'.$list->getId(),
                'align' => 'top', 'rel' => Assets::image_path('icons/16/blue/arr_1down.png'))) ?>
            <?= htmlReady($list->getName()) ?>
        </a>
        <a href="<?= URLHelper::getURL('dispatch.php/admission/userlist/configure/'.$list->getId()); ?>">
            <?= Assets::img('icons/16/blue/edit.png',
                array('alt' => _('Nutzerliste bearbeiten'),
                      'title' => _('Nutzerliste bearbeiten'))); ?>
        </a>
        <a href="<?= $controller->url_for('admission/userlist/delete',
            $list->getId()) ?>"
            onclick="return STUDIP.Dialogs.showConfirmDialog('<?=
                sprintf(_('Soll die Nutzerliste %s wirklich gel�scht werden?'), htmlReady($list->getName())) ?>', '<?=
                URLHelper::getURL('dispatch.php/admission/userlist/delete/'.
                $list->getId(), array('really' => 1)) ?>')">
            <?= Assets::img('icons/16/blue/trash.png',
                array('alt' => _('Nutzerliste l�schen'),
                      'title' => _('Nutzerliste l�schen'))); ?>
        </a>
    </div>
    <div id="userlist_details_<?= $list->getId() ?>" style="display: none; margin-left: 20px;">
        <?= $list->toString() ?>
    </div>
    <?php } ?>
</div>
<?php
} else {
?>
<?= MessageBox::info(sprintf(_('Es wurden keine Nutzerlisten gefunden. Sie k�nnen eine '.
    'neue %sNutzerliste anlegen%s.'), '<a href="'.
    $controller->url_for('admission/userlist/configure').'">',
    '</a>')); ?>
<?php
}
?>