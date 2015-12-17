<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<? if (isset($error)) : ?>
    <?= MessageBox::error($error) ?>
<? endif ?>

<h1><?= _("Veranstaltungsbild hochladen") ?></h1>

<div style="float: left; padding: 0 1em 1em 0;">
    <?= $avatar->getImageTag(Avatar::NORMAL) ?>
</div>

<form enctype="multipart/form-data"
      action="<?= $controller->url_for('course/avatar/put/' . $course_id) ?>"
      method="post" style="float: left">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
    <label for="upload-input"><?= _("W�hlen Sie ein Bild f�r die Veranstaltung:") ?></label>
    <input id="upload-input" name="avatar" type="file">

    <p class="quiet">
        <?= Icon::create('info-circle', 'inactive')->asImg(16, ["style" => 'vertical-align: middle;']) ?>
        <? printf(_("Die Bilddatei darf max. %d KB gro� sein, es sind nur Dateien mit den Endungen %s, %s oder %s erlaubt!"),
                  Avatar::MAX_FILE_SIZE / 1024,
                  '<b>.jpg</b>', '<b>.png</b>', '<b>.gif</b>') ?>
    </p>

    <p>
        <?= Button::createAccept(_('Absenden')) ?>
        <span class="quiet">
            <?= _("oder") ?>
            <? if ($this->studygroup_mode) : ?>
                <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL('dispatch.php/course/studygroup/edit/' . $course_id)) ?>
            <? else : ?>
                <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL('dispatch.php/course/basicdata/view/' . $course_id)) ?>
            <? endif ?>
        </span>
    </p>
</form>

<?php

$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/admin-sidebar.png');

if ($avatar->is_customized()) {
    $actions = new ActionsWidget();
    $actions->addLink(_('Bild l�schen'),
                      $controller->link_for('course/avatar/delete', $course_id), Icon::create('trash', 'info'),
                      array('onclick' => sprintf('return confirm(\'%s\');', _('Wirklich l�schen?'))))->asDialog(false);
    $sidebar->addWidget($actions);
}

if ($adminList) {
    $list = new SelectorWidget();
    $list->setUrl('?#admin_top_links');
    foreach ($adminList as $seminar) {
        $list->addElement(new SelectElement($seminar['Seminar_id'], $seminar['name']), 'select-' . $seminar->Seminar_id);
    }
    $list->setSelection($course_id);
    $sidebar->addWidget($list);
}

if ($adminList) {
    $infobox[] = array(
        "kategorie" => _("Veranstaltungsliste:"),
        "eintrag"   =>
            array(
                array(
                      "icon" => Icon::create('link-intern', 'clickable'),
                      "text" => $adminList->render()
                )
            )
    );
}
