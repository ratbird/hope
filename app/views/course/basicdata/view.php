<?php

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
              "icon" => "edit_transparent.gif",
              "text" => '<a href="' .
$controller->url_for('course/avatar/update', $course_id) .
                        '">' . _("Bild ändern") . '</a>');
$aktionen[] = array(
              "icon" => "trash.gif",
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
                  "icon" => 'ausruf_small.gif',
                      "text" => sprintf(_('Angelegt am %s'), "<b>$mkstring</b>")
            ),
            array(
                  "icon" => 'ausruf_small.gif',
                  "text" => sprintf(_('Letzte Änderung am %s'), "<b>$chstring</b>")
            )
        )
    )
);
$infobox = array('content' => $infobox,
                 'picture' => CourseAvatar::getAvatar($course_id)->getUrl(Avatar::NORMAL)
);
//end of infobox;
//print "<pre>";
//var_dump($flash);
//print "</pre>";
parse_msg_array($flash['msg'], $class = "blank", $colspan = 2, $add_row='', $small='');

$width_column1 = 20;
$width_namecolumn = 50;

?>

<div style="min-width: 600px">

<form name="details" method="post" action="<?= $controller->url_for('course/basicdata/set?cid='.$course_id) ?>">
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
             <td style="text-align: right" width="<?= $width_column1 ?>%"><?= ($attribute['bold'] ? "<b>" : "") . 
                 $attribute['title'] . 
                 ($attribute['bold'] ? "</b>" : "") ?></td>
             <td style="text-align: left" width="<?= 100-$width_column1 ?>%"><?=
             $attribute['locked'] 
                 ? formatReady($attribute['title'])
                 : $this->render_partial("course/basicdata/_input", array('input' => $attribute))
             ?></td>
          </tr>
      <? endforeach;
  }
  ?>
  </table></div>
  
  <h2 id="bd_inst" class="steelgraulight"><?= _("Institute") ?></h2>
  <div><table width="100%">
  <?php
  if (!$institutional) {
      ?>
      <tr><td colspan="2"><?= _("Fehlende Datenzeilen") ?></td></tr>
      <?php
  } else {
      foreach ($institutional as $inst) : ?>
          <tr>
             <td style="text-align: right" width="<?= $width_column1 ?>%"><?= ($inst['bold'] ? "<b>" : "") . 
                 $inst['title'] . 
                 ($inst['bold'] ? "</b>" : "") ?></td>
             <td style="text-align: left" width="<?= 100-$width_column1 ?>%"><?=
             $inst['locked'] 
                 ? formatReady($inst['title'])
                 : $this->render_partial("course/basicdata/_input", array('input' => $inst))
             ?></td>
          </tr>
      <? endforeach;
  }
  ?>
  </table></div>
  
  <h2 id="bd_personal" class="steelgraulight"><?= _("Personal") ?></h2>
  <div><table style="width: 100%">
  <tr>
    <td style="width: <?= $width_column1 ?>%"><?= _("Dozent/-innen") ?></td>
    <td style="width: <?= 100-$width_column1 ?>%"><table><tr><td style="width: <?= $width_namecolumn ?>%"><? $num = 0; 
        foreach($dozenten as $dozent) : ?>
        <div style="clear:both">
            <? if ($perm_dozent) : ?>
            <div style="float:left; vertical-align: middle">
                <a href="<?= $controller->url_for('course/basicdata/deletedozent', $dozent["user_id"]) ?>?cid=<?= $course_id ?>&section=<?= $section ?>">
                <?= Assets::img("trash.gif") ?></a>
            </div>
            <div style="float:left; margin: 3px; vertical-align: middle; width: 40px; ">
                <? if ($num > 0) : ?>
                <a href="<?= $controller->url_for('course/basicdata/priorityupfor', $dozent["user_id"], "dozent") ?>?cid=<?= $course_id ?>&section=<?= $section ?>">
                <?= Assets::img("move_up") ?></a>
                <? endif; if ($num < count($dozenten)-1) : ?>
                <a href="<?= $controller->url_for('course/basicdata/prioritydownfor', $dozent["user_id"], "dozent") ?>?cid=<?= $course_id ?>&section=<?= $section ?>">
                <?= Assets::img("move_down") ?></a>
                <? endif; ?>
            </div>
            <? endif; ?>
            <div style="float:left; font-weight:bold; vertical-align: middle; padding-left: 3px">
                <?= get_fullname($dozent["user_id"], 'full_rev')."<br>(".$dozent["username"].")" ?>
            </div>
            
        </div>
    <? $num++; endforeach; ?></td>
    <? if ($perm_dozent) : ?>
    <td style="text-align: left; width: <?= 100-$width_namecolumn ?>%">
        <?= _("Dozent/-in hinzufügen") ?>
        <br><input type="image" src="<?= Assets::image_path("move_left") ?>" name="add_dozent">
            <?= $dozentensuche ?>
        <br><?= _("Geben Sie zur Suche den Vor-, Nach- oder Usernamen ein.") ?>
    </td>
    <? endif; ?>
    </tr></table><hr style="clear:both"></td>
  </tr>
  <tr>
    <td style="width: <?= $width_column1 ?>%"><?= _("Tutor/-innen") ?></td>
    <td style="width: <?= 100-$width_column1 ?>%"><table><tr><td style="width: <?= $width_namecolumn ?>%; text-align: left"><? $num = 0; 
        foreach($tutoren as $tutor) : ?>
        <div style="clear:both">
            <? if ($perm_dozent) : ?>
            <div style="float:left; margin: 3px; vertical-align: middle">
                <a href="<?= $controller->url_for('course/basicdata/deletetutor', $tutor["user_id"]) ?>?cid=<?= $course_id ?>&section=<?= $section ?>">
                <?= Assets::img("trash.gif") ?></a>
            </div>
            <div style="float:left; margin: 3px; vertical-align: middle">
                <? if ($num > 0) : ?>
                <a href="<?= $controller->url_for('course/basicdata/priorityupfor', $tutor["user_id"], "tutor") ?>?cid=<?= $course_id ?>&section=<?= $section ?>">
                <?= Assets::img("move_up") ?></a>
                <? endif; if ($num < count($tutoren)-1) : ?>
                <a href="<?= $controller->url_for('course/basicdata/prioritydownfor', $tutor["user_id"], "tutor") ?>?cid=<?= $course_id ?>&section=<?= $section ?>">
                <?= Assets::img("move_down") ?></a>
                <? endif; ?>
            </div>
            <? endif; ?>
            <div style="float:left; font-weight:bold; vertical-align: middle; padding-left: 3px">
                <?= get_fullname($tutor["user_id"], 'full_rev')."<br>(".$tutor["username"].")" ?>
            </div>
            
        </div>
    <? $num++; endforeach; ?></td>
    <? if ($perm_dozent) : ?>
    <td style="text-align: left; width: <?= 100-$width_namecolumn ?>%">
        <?= _("Tutor/-in hinzufügen") ?>
        <br><input type="image" src="<?= Assets::image_path("move_left") ?>" name="add_tutor">
            <?= $tutorensuche ?>
        <br><?= _("Geben Sie zur Suche den Vor-, Nach- oder Usernamen ein.") ?>
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
             <td style="text-align: right; width: <?= $width_column1 ?>%"><?= ($description['bold'] ? "<b>" : "") . 
                 $description['title'] . 
                 ($description['bold'] ? "</b>" : "") ?></td>
             <td style="text-align: left; width: <?= 100-$width_column1 ?>%"><?=
             $description['locked'] 
                 ? formatReady($description['title'])
                 : $this->render_partial("course/basicdata/_input", array('input' => $description))
             ?></td>
          </tr>
      <? endforeach;
  }
  ?>
  </table></div>
  
</div>
<div style="text-align:center; padding: 15px">
  <? echo makeButton("uebernehmen", "input") ?>
  <input type="hidden" name="section" value="<?= $section ?>">
  <input id="open_variable" type="hidden" name="open" value="<?= $flash['open'] ?>">
</div>
</form>
<script>
$("#settings").accordion({
    <?= $flash['open'] ? "active: '#".$flash['open']."',\n" : "" ?>
    collapsible: true,
    autoHeight: false,
    change: function (event, ui) {
        $('#open_variable').attr('value', ui.newHeader.attr('id'));
    }
});
</script>
</div>