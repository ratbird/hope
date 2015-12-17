<?php
Helpbar::get()->addPlainText(_('Info'),"Personenlisten erfassen eine Menge von Personen, die ".
                                       "mit modifizierten Chancen in die Platzverteilung bei ".
                                       "Anmeldeverfahren eingehen. Dies k�nnen z.B. ".
                                       "H�rtef�lle sein, die bevorzugt einen Platz in ".
                                       "Veranstaltungen erhalten sollen.");
Helpbar::get()->addPlainText(_('Info'), "Hier sehen Sie alle Personenlisten, auf die Sie Zugriff ".
                                        "haben.");
?>
<?= $this->render_partial('dialog/confirm_dialog') ?>
<h1><?= _('Personenlisten') ?></h1>
<?php
if ($userlists) {
?>
<div id="userlists">
    <?php foreach ($userlists as $list) { ?>
    <div id="userlist_<?= $list->getId() ?>">
        <a href="#" onclick="return STUDIP.Admission.toggleDetails('userlist_arrow_<?= $list->getId() ?>', 'userlist_details_<?= $list->getId() ?>')">
            <?= Icon::create('arr_1right', 'clickable')->asImg(16, ["id" => 'userlist_arrow_'.$list->getId(), "align" => 'top', "rel" => Icon::create('arr_1down', 'clickable')->asImagePath(16)]) ?>
            <?= htmlReady($list->getName()) ?>
        </a>
        <a href="<?= URLHelper::getURL('dispatch.php/admission/userlist/configure/'.$list->getId()); ?>">
            <?= Icon::create('edit', 'clickable', ['title' => _('Nutzerliste bearbeiten')])->asImg(16, ["alt" => _('Nutzerliste bearbeiten')]); ?>
        </a>
        <a href="<?= $controller->url_for('admission/userlist/delete',
            $list->getId()) ?>"
            onclick="return STUDIP.Dialogs.showConfirmDialog('<?=
                sprintf(_('Soll die Nutzerliste %s wirklich gel�scht werden?'), htmlReady($list->getName())) ?>', '<?=
                URLHelper::getURL('dispatch.php/admission/userlist/delete/'.
                $list->getId(), array('really' => 1)) ?>')">
            <?= Icon::create('trash', 'clickable', ['title' => _('Personenliste l�schen')]) ?>
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
<?= MessageBox::info(sprintf(_('Es wurden keine Personenlisten gefunden. Sie k�nnen eine '.
    'neue %sPersonenliste anlegen%s.'), '<a href="'.
    $controller->url_for('admission/userlist/configure').'">',
    '</a>')); ?>
<?php
}
?>
