<?php
# Lifter010: TODO

/*
 * Copyright (C) 2010 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

//Infobox:
$aktionen = array();
$aktionen[] = array(
              "icon" => "icons/16/black/edit.png",
              "text" => '<a href="' .
$controller->url_for('course/avatar/update', $course_id) .
                        '">' . _("Bild ändern") . '</a>');
$aktionen[] = array(
              "icon" => "icons/16/black/trash.png",
              "text" => '<a href="' .
$controller->url_for('course/avatar/delete', $course_id) .
                        '">' . _("Bild löschen") . '</a>');

$infobox = array(
    array("kategorie" => _("Aktionen:"),
          "eintrag"   => $aktionen
    ),
    array("kategorie" => _("Informationen:"),
          "eintrag"   =>
        array(
            array(
                  "icon" => "icons/16/black/info.png",
                      "text" => sprintf(_('Angelegt am %s'), "<b>$mkstring</b>")
            ),
            array(
                  "icon" => "icons/16/black/info.png",
                  "text" => sprintf(_('Letzte Änderung am %s'), "<b>$chstring</b>")
            ),
            array(
                  "icon" => "icons/16/black/info.png",
                  "text" => _("Mit roten Sternchen markierte Felder sind Pflichtfelder.")
            )
        )
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

$width_column1 = 20;
$width_namecolumn = 60;

$message_types = array('msg' => "success", 'error' => "error", 'info' => "info");
?>

<? if (is_array($flash['msg'])) foreach ($flash['msg'] as $msg) : ?>
     <?= MessageBox::$message_types[$msg[0]]($msg[1]) ?>
<? endforeach ?>

<? if ($adminTopLinks) : ?>
    <?= $adminTopLinks->render() ?>
<? endif ?>

<div style="min-width: 600px">

<form name="details" method="post" action="<?= $controller->url_for('course/basicdata/set' , $course_id) ?>">
<?= CSRFProtection::tokenTag() ?>
<div style="text-align:center" id="settings" class="steel1">

  <h2 id="bd_basicsettings" class="steelgraulight"><?= _("Grundeinstellungen") ?></h2>
  <div><table width="100%">
  <?php
  if (!$attributes) {
      ?>
      <tr><td colspan="2"><?= _("Fehlende Datenzeilen") ?></td></tr>
      <?php
  } else {
      foreach ($attributes as $attribute) : ?>
          <tr>
             <td style="text-align: right; width: <?= $width_column1 ?>%; vertical-align: top;">
                 <?= $attribute['title'] ?>
                 <?= $attribute['must'] ? "<span style=\"color: red; font-size: 1.6em\">*</span>" : "" ?>
             </td>
             <td style="text-align: left" width="<?= 100-$width_column1 ?>%"><?=
              $this->render_partial("course/basicdata/_input", array('input' => $attribute))
             ?></td>
          </tr>
      <? endforeach;
  }
  ?>
  </table></div>

  <h2 id="bd_inst" class="steelgraulight"><?= _("Einrichtungen") ?></h2>
  <div><table width="100%">
  <?php
  if (!$institutional) {
      ?>
      <tr><td colspan="2"><?= _("Fehlende Datenzeilen") ?></td></tr>
      <?php
  } else {
      foreach ($institutional as $inst) : ?>
          <tr>
             <td style="text-align: right; width: <?= $width_column1 ?>%; vertical-align: top;">
                <?= $inst['title'] ?>
                <?= $inst['must'] ? "<span style=\"color: red; font-size: 1.6em\">*</span>" : "" ?>
             </td>
             <td style="text-align: left" width="<?= 100-$width_column1 ?>%"><?=
             $this->render_partial("course/basicdata/_input", array('input' => $inst))
             ?></td>
          </tr>
      <? endforeach;
  }
  ?>
  </table></div>

  <h2 id="bd_personal" class="steelgraulight"><?= _("Personal") ?></h2>
  <div><table style="width: 100%">
  <tr>
    <td style="width: <?= $width_column1/2 ?>%; font-weight: bold; vertical-align: top;"><?= $dozenten_title ?></td>
    <td style="width: <?= 100-$width_column1-($width_column1/2) ?>%"><table><tr><td style="width: <?= $width_namecolumn ?>%; text-align: left">
        <ul style="list-style-type: none; padding-left: 0px;">
        <? $num = 0;
        foreach($dozenten as $dozent) : ?>
        <li style="text-align: left; padding-left: 0px;">
            <span style="display: inline-block; vertical-align: middle;">
                <?= Avatar::getAvatar($dozent["user_id"], $dozent['username'])->getImageTag(Avatar::SMALL) ?>
            </span>
            <? if ($perm_dozent && !$dozent_is_locked) : ?>
            <span style="white-space: nowrap; width: 32px; display: inline-block; vertical-align: middle;">
                <? if ($num > 0) : ?>
                <a href="<?= $controller->url_for('course/basicdata/priorityupfor', $course_id, $dozent["user_id"], "dozent") ?>">
                <?= Assets::img("icons/16/yellow/arr_2up.png", array('class' => 'middle')) ?></a>
                <? endif; if ($num < count($dozenten)-1) : ?>
                <a href="<?= $controller->url_for('course/basicdata/prioritydownfor', $course_id, $dozent["user_id"], "dozent") ?>">
                <?= Assets::img("icons/16/yellow/arr_2down.png", array('class' => 'middle')) ?></a>
                <? endif; ?>
            </span>
            <? endif; ?>
            <span style="display: inline-block; padding-left: 3px; vertical-align: middle;">
                <?= get_fullname($dozent["user_id"], 'full_rev', true)." (".$dozent["username"].")" ?>
            </span>
            <? if ($perm_dozent && !$dozent_is_locked) : ?>
            <span style="display: inline-block; vertical-align: middle;">
                <a href="<?= $controller->url_for('course/basicdata/deletedozent', $course_id, $dozent["user_id"]) ?>">
                <?= Assets::img("icons/16/blue/trash.png") ?>
                </a>
            </span>
            <? endif; ?>
        </li>
    <? $num++; endforeach; ?>
        </ul>
    </td>
    <? if ($perm_dozent && !$dozent_is_locked) : ?>
    <td style="text-align: left; width: <?= 100-$width_namecolumn ?>%">
        <?= sprintf(_("%s hinzufügen"), $dozenten_title) ?>
        <br>
            <span style="white-space: nowrap">
                <input class="middle" type="image" src="<?= Assets::image_path("icons/16/yellow/arr_2left.png") ?>" name="add_dozent">
                <?= $dozentensuche ?>
            </span>
        <br><?= _("Geben Sie zur Suche den Vor-, Nach- oder Benutzernamen ein.") ?>
    </td>
    <? endif; ?>
    </tr></table><hr style="clear:both"></td>
  </tr>
  <? if ($deputies_enabled) { ?>
  <tr>
    <td style="width: <?= $width_column1/2 ?>%; font-weight: bold; vertical-align: top;"><?= $deputy_title ?></td>
    <td style="width: <?= 100-$width_column1-($width_column1/2) ?>%"><table><tr><td style="width: <?= $width_namecolumn ?>%">
    <ul style="list-style-type: none; padding-left: 0px;">
    <? foreach($deputies as $deputy) : ?>
        <li style="text-align: left; padding-left: 0px;">
            <span style="display: inline-block; vertical-align: middle;">
                <?= Avatar::getAvatar($deputy["user_id"], $deputy["username"])->getImageTag(Avatar::SMALL) ?>
            </span>
            <span style="display: inline-block; padding-left: 3px; vertical-align: middle;">
                <?= get_fullname($deputy["user_id"], 'full_rev', true)." (".$deputy["username"].", "._("Status").": ".$deputy['perms'].")" ?>
            </span>
            <? if ($perm_dozent && !$dozent_is_locked) : ?>
            <span style="vertical-align: middle">
                <a href="<?= $controller->url_for('course/basicdata/deletedeputy', $course_id, $deputy["user_id"]) ?>">
                <?= Assets::img("icons/16/blue/trash.png") ?></a>
            </span>
            <? endif; ?>

        </li>
    <? endforeach; ?>
    </ul>
    </td>
    <? if ($perm_dozent && !$dozent_is_locked) : ?>
    <td style="text-align: left; width: <?= 100-$width_namecolumn ?>%">
        <?= sprintf(_("%s hinzufügen"), $deputy_title) ?>
        <br>
            <span style="white-space: nowrap">
                <input class="middle" type="image" src="<?= Assets::image_path("icons/16/yellow/arr_2left.png") ?>" name="add_deputy">
                <?= $deputysearch ?>
            </span>
        <br><?= _("Geben Sie zur Suche den Vor-, Nach- oder Benutzernamen ein.") ?>
    </td>
    <? endif; ?>
    </tr></table><hr style="clear:both"></td>
  </tr>
  <? } ?>
  <tr>
    <td style="width: <?= $width_column1/2 ?>%;  font-weight: bold; vertical-align: top;"><?= $tutor_title ?></td>
    <td style="width: <?= 100-$width_column1-($width_column1/2) ?>%"><table><tr><td style="width: <?= $width_namecolumn ?>%; text-align: left">
    <ul style="list-style-type: none; padding-left: 0px;">
    <? $num = 0;
        foreach($tutoren as $tutor) : ?>
        <li style="text-align: left; padding-left: 0px;">
            <span style="display: inline-block; vertical-align: middle;">
                <?= Avatar::getAvatar($tutor["user_id"], $tutor["username"])->getImageTag(Avatar::SMALL) ?>
            </span>
            <? if ($perm_dozent && !$tutor_is_locked) : ?>
            <span style="white-space: nowrap; width: 32px; display: inline-block; vertical-align: middle;">
                <? if ($num > 0) : ?>
                <a href="<?= $controller->url_for('course/basicdata/priorityupfor', $course_id, $tutor["user_id"], "tutor") ?>">
                <?= Assets::img("icons/16/yellow/arr_2up.png", array('class' => 'middle')) ?></a>
                <? endif; if ($num < count($tutoren)-1) : ?>
                <a href="<?= $controller->url_for('course/basicdata/prioritydownfor', $course_id, $tutor["user_id"], "tutor") ?>">
                <?= Assets::img("icons/16/yellow/arr_2down.png", array('class' => 'middle')) ?></a>
                <? endif; ?>
            </span>
            <? endif; ?>
            <span style="display: inline-block; padding-left: 3px; vertical-align: middle;">
                <?= get_fullname($tutor["user_id"], 'full_rev', true)." (".$tutor["username"].")" ?>
            </span>
            <? if ($perm_dozent && !$tutor_is_locked) : ?>
            <span style="display: inline-block; vertical-align: middle;">
                <a href="<?= $controller->url_for('course/basicdata/deletetutor', $course_id, $tutor["user_id"]) ?>">
                <?= Assets::img("icons/16/blue/trash.png") ?>
                </a>
            </span>
            <? endif; ?>
        </li>
    <? $num++; endforeach; ?>
    </ul>
    </td>
    <? if ($perm_dozent && !$tutor_is_locked) : ?>
    <td style="text-align: left; width: <?= 100-$width_namecolumn ?>%">
        <?= sprintf(_("%s hinzufügen"), $tutor_title) ?>
        <br>
            <span style="white-space: nowrap">
                <input class="middle" type="image" src="<?= Assets::image_path("icons/16/yellow/arr_2left.png") ?>" name="add_tutor">
                <?= $tutorensuche ?>
            </span>
        <br><?= _("Geben Sie zur Suche den Vor-, Nach- oder Benutzernamen ein.") ?>
    </td>
    <? endif; ?>
    </tr></table>
    <? if (!$perm_dozent) : ?>
        <span style="color: #ff0000"><?= _("Die Personendaten können Sie mit Ihrem Status nicht bearbeiten!") ?></span>
    <? endif; ?>
    </td>
  </tr>
  </table></div>


  <h2 id="bd_description" class="steelgraulight"><?= _("Beschreibungen") ?></h2>
  <div><table style="width: 100%">
  <?php
  if (!$descriptions) {
      ?>
      <tr><td colspan="2"><?= _("Fehlende Datenzeilen") ?></td></tr>
      <?php
  } else {
      foreach ($descriptions as $description) : ?>
          <tr>
             <td style="text-align: right; width: <?= $width_column1 ?>%; vertical-align: top;">
                <?= $description['title'] ?>
                <?= $description['must'] ? "<span style=\"color: red; font-size: 1.6em\">*</span>" : "" ?>
             </td>
             <td style="text-align: left; width: <?= 100-$width_column1 ?>%"><?=
                $this->render_partial("course/basicdata/_input", array('input' => $description))
             ?></td>
          </tr>
      <? endforeach;
  }
  ?>
  </table></div>

</div>
<div style="text-align:center; padding: 15px">
  <? echo makeButton("uebernehmen", "input") ?>
  <input id="open_variable" type="hidden" name="open" value="<?= $flash['open'] ?>">
</div>
</form>
<script>
jQuery("#settings").accordion({
    <?= $flash['open'] ? "active: '#".$flash['open']."',\n" : "" ?>
    collapsible: true,
    autoHeight: false,
    change: function (event, ui) {
        jQuery('#open_variable').attr('value', ui.newHeader.attr('id'));
    }
});
</script>
</div>
