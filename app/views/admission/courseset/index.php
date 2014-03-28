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
<table class="default nohover">
    <tr>
        <th width="60%"><?= _('Name des Sets') ?></th>
        <th width="25%"><?= _('Besitzer') ?></th>
        <th width="5%"><?= _('Privat')?></th>
        <th width="5%"><?= _('Anzahl')?></th>
        <th style="text-align:center"><?= _('Aktionen') ?></th>
    </tr>
    <? foreach ($coursesets as $courseset) : ?>
    <tr>
        <td><?= htmlReady(my_substr($courseset->getName(),0,70)) ?></td>
        <td><?= htmlReady(get_fullname($courseset->getUserId(), 'no_title_rev')) ?></td>
        <td><?= $courseset->getPrivate() ? _('Ja') : _('Nein') ?></td>
        <td><?= count($courseset->getCourses()) ?></td>
        <td>
        <div style="width:100px;text-align:right;white-space: nowrap">
            <a class="load-in-new-row" href="<?= $controller->link_for('', array('course_set_details' => $courseset->getId())); ?>">
                <?= Assets::img('icons/16/blue/info.png', array('title' => _('Weitere Informationen einblenden'))) ?>
            </a>
            <? if ($courseset->isUserAllowedToEdit($GLOBALS['user']->id)) : ?>
            <a href="<?= $controller->link_for('admission/courseset/configure/'.$courseset->getId()); ?>">
                    <?= Assets::img('icons/16/blue/edit.png',
                        array('alt' => _('Anmeldeset bearbeiten'),
                              'title' => _('Anmeldeset bearbeiten'))); ?>
                </a>
                <a href="<?= $controller->link_for('admission/courseset/delete/'.
                    $courseset->getId()) ?>"
                    onclick="return STUDIP.Dialogs.showConfirmDialog('<?=
                        sprintf(_('Soll das Anmeldeset %s wirklich gel�scht werden?'), htmlReady($courseset->getName())) ?>', '<?=
                        URLHelper::getURL('dispatch.php/admission/courseset/delete/'.
                        $courseset->getId(), array('really' => 1)) ?>')">
                    <?= Assets::img('icons/16/blue/trash.png',
                        array('alt' => _('Anmeldeset l�schen'),
                              'title' => _('Anmeldeset l�schen'))); ?>
                </a>
             <? endif ?>
        </div>
        </td>
    </tr>
    <? if ($course_set_details == $courseset->getId()) : ?>
        <tr>
            <td colspan="5">
                <?= $courseset->toString() ?>
            </td>
        </tr>
    <? endif ?>
    <? endforeach ?>
</table>
<?php
} else {
?>
<?= MessageBox::info(sprintf(_('Es wurden keine Anmeldesets gefunden. Sie k�nnen ein '.
    'neues %sAnmeldeset anlegen%s.'), '<a href="'.
    $controller->url_for('admission/courseset/configure').'">',
    '</a>')); ?>
<?php
}
?>