<?
# Lifter010: TODO

//Infobox:
$aktionen = array();
$aktionen[] = array(
              "icon" => "icons/16/black/edit.png",
              "text" => _("Navigieren Sie in der rechten Spalte und schieben Sie durch Klick auf den gelben Pfeil den Studienbereich in die linke Spalte.")
);
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
                 'picture' => CourseAvatar::getAvatar($course_id)->getUrl(Avatar::NORMAL)
);

?>
<div class="white" style="padding: 0.5em;">

  <? if (isset($error)) : ?>
    <div id="error" style="background:white;margin:0;padding:1em;">
      <table border="0" cellspacing="0" cellpadding="2">
        <tr>
          <td align="center" width="50"><?= Assets::img('icons/16/red/decline.png') ?></td>
          <td align="left"><font color="#FF2020"><?= $error ?></font></td>
        </tr>
      </table>
    </div>
  <? endif ?>

  <? if ($locked) : ?>

    <?= $this->render_partial('course/study_areas/locked_form') ?>

  <? elseif ($areas_not_allowed) : ?>

    <?= MessageBox::info(_("Für diesen Veranstaltungstyp ist die Zuordnung zu Studienbereichen nicht vorgesehen.")) ?>

  <? else : ?>

    <form method="POST" name="details"
          action="<?= $controller->url_for('course/study_areas/show/' . $course_id) ?>">

      <?= CSRFProtection::tokenTag() ?>
      <?= $this->render_partial('course/study_areas/form') ?>

    </form>

  <? endif ?>

</div>
