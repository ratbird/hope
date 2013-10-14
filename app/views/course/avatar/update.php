<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<? if (isset($error)) : ?>
    <?= MessageBox::error($error) ?>
<? endif ?>

<h1><?= _("Veranstaltungsbild hochladen") ?></h1>

<div style="float: left; padding: 0 1em 1em 0;">
    <? if ($this->studygroup_mode) : ?>
    <?= StudygroupAvatar::getAvatar($course_id)->getImageTag(Avatar::NORMAL) ?>
    <? else: ?>
    <?= CourseAvatar::getAvatar($course_id)->getImageTag(Avatar::NORMAL) ?>
    <? endif ?>
</div>

<form enctype="multipart/form-data"
      action="<?= $controller->url_for('course/avatar/put/' . $course_id) ?>"
      method="post" style="float: left">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
    <label for="upload-input"><?= _("W�hlen Sie ein Bild f�r die Veranstaltung:") ?></label>
    <input id="upload-input" name="avatar" type="file">

    <p class="quiet">
        <?= Assets::img("icons/16/grey/info-circle.png", array('style' => 'vertical-align: middle;')) ?>
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

<?

$aktionen = array();
$aktionen[] = array(
              "icon" => "icons/16/black/trash.png",
              "text" => '<a href="' .
$controller->url_for('course/avatar/delete', $course_id) .
                        '" onClick="return confirm(\''._("Wirklich l�schen?").'\');">' . _("Bild l�schen") . '</a>');

$infobox = array(
    array("kategorie" => _("Aktionen:"),
          "eintrag"   => $aktionen
    )
);
if ($adminList) {
    $infobox[] = array(
        "kategorie" => _("Veranstaltungsliste:"),
        "eintrag"   =>
            array(
                array(
                      "icon" => "icons/16/black/link-intern.png",
                      "text" => $adminList->render()
                )
            )
    );
}
$infobox = array('content' => $infobox,
                 'picture' => Assets::image_path("infobox/administration.png")
);