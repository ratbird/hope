<?php
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

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

<div style="min-width: 600px">

<form name="details" method="post" action="<?= $controller->url_for('course/basicdata/set' , $course_id) ?>">
<?= CSRFProtection::tokenTag() ?>
<div style="text-align:center" id="settings" class="table_row_even">

  <h2 id="bd_basicsettings" class="table_row_odd"><?= _("Grundeinstellungen") ?></h2>
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

  <h2 id="bd_inst" class="table_row_odd"><?= _("Einrichtungen") ?></h2>
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
             <td style="text-align: left" width="<?= 100-$width_column1 ?>%"><?
                if ($inst['type'] !== "select" || $inst['choices'][$inst['value']]) {
                    echo $this->render_partial("course/basicdata/_input", array('input' => $inst));
                } else {
                    $name = get_object_name($inst['value'], "inst");
                    echo htmlReady($name['name']);
                }
             ?></td>
          </tr>
      <? endforeach;
  }
  ?>
  </table></div>

  <h2 id="bd_personal" class="table_row_odd"><?= _("Personal") ?></h2>
  <div>

      <style>
          #leiterinnen_tabelle > tbody > tr > td {
              vertical-align: top;
          }
      </style>
      <script>

      </script>
      <table style="margin-left: auto; margin-right: auto; text-align: left;" id="leiterinnen_tabelle">
          <!-- Dozenten -->
          <tr>
              <td style="font-weight: bold;" colspan="6"><?= $dozenten_title ?></td>
          </tr>
          <? $num = 0; foreach($dozenten as $dozent) : ?>
          <tr>
              <td></td>
              <td>
              <? if ($perm_dozent && !$dozent_is_locked) : ?>
                  <? if ($num > 0) : ?>
                  <a href="<?= $controller->url_for('course/basicdata/priorityupfor', $course_id, $dozent["user_id"], "dozent") ?>">
                  <?= Assets::img("icons/16/yellow/arr_2up.png", array('class' => 'middle')) ?></a>
                  <? endif; if ($num < count($dozenten)-1) : ?>
                  <a href="<?= $controller->url_for('course/basicdata/prioritydownfor', $course_id, $dozent["user_id"], "dozent") ?>">
                  <?= Assets::img("icons/16/yellow/arr_2down.png", array('class' => 'middle')) ?></a>
                  <? endif; ?>
              <? endif ?>
              </td>
              <td>
                  <a href="<?= URLHelper::getLink("about.php", array('username' => $dozent['username']))?>">
                      <?= Avatar::getAvatar($dozent["user_id"], $dozent['username'])->getImageTag(Avatar::SMALL) ?>
                  </a>
              </td>
              <td>
                  <?= get_fullname($dozent["user_id"], 'full_rev', true)." (".$dozent["username"].")" ?>
              </td>
              <td>
              <? if ($perm_dozent && !$dozent_is_locked) : ?>
                  <label>
                  <?= _("Funktion") ?>:
                  <input value="<?= htmlReady($dozent["label"]) ?>" type="text" name="label[<?= htmlReady($dozent["user_id"]) ?>]" title="<?= _("Die Funktion, die die Person in der Veranstaltung erfüllt.") ?>">
                  </label>
              <? else : ?>
                  <?= $dozent["label"] ? htmlReady($dozent["label"]) : '&nbsp;' ?>
              <? endif ?>
              </td>
              <td>
                  <? if ($perm_dozent && !$dozent_is_locked) : ?>
                  <a href="<?= $controller->url_for('course/basicdata/deletedozent', $course_id, $dozent["user_id"]) ?>">
                  <?= Assets::img("icons/16/blue/trash.png") ?>
                  </a>
                  <? endif ?>
              </td>
          </tr>
          <? $num++; endforeach ?>
          <? if ($perm_dozent && !$dozent_is_locked) : ?>
              <tr>
                  <td style="padding-top: 15px"></td>
                  <td style="padding-top: 15px"><input class="middle" type="image" src="<?= Assets::image_path("icons/16/yellow/arr_2up.png") ?>" name="add_dozent" title="<?= sprintf(_("Neuen %s hinzufügen"), $dozenten_title) ?>"></td>
                  <td style="padding-top: 15px"></td>
                  <td style="padding-top: 15px"><?= $dozentensuche ?></td>
                  <td style="padding-top: 15px"></td>
                  <td style="padding-top: 15px"></td>
              </tr>
          <? endif ?>
          <? if ($deputies_enabled && ($perm_dozent || count($deputies))) : ?>
          <!-- Stellvertreter -->
          <tr>
              <td colspan="6"><hr></td>
          </tr>
          <tr>
              <td style="font-weight: bold;"  colspan="6"><?= $deputy_title ?></td>
          </tr>
          <? foreach($deputies as $deputy) : ?>
          <tr>
              <td></td>
              <td></td>
              <td>
                  <?= Avatar::getAvatar($deputy["user_id"], $deputy["username"])->getImageTag(Avatar::SMALL) ?>
              </td>
              <td>
                  <?= get_fullname($deputy["user_id"], 'full_rev', true)." (".$deputy["username"].", "._("Status").": ".$deputy['perms'].")" ?>
              </td>
              <td></td>
              <td>
              <? if ($perm_dozent && !$dozent_is_locked) : ?>
                      <a href="<?= $controller->url_for('course/basicdata/deletedeputy', $course_id, $deputy["user_id"]) ?>">
                      <?= Assets::img("icons/16/blue/trash.png") ?>
                  </a>
              <? endif ?>
              </td>
          </tr>
          <? endforeach ?>
          <? if ($perm_dozent && !$dozent_is_locked) : ?>
              <tr>
                  <td style="padding-top: 15px"></td>
                  <td style="padding-top: 15px"><input class="middle" type="image" src="<?= Assets::image_path("icons/16/yellow/arr_2up.png") ?>" name="add_deputy" title="<?= sprintf(_("Neuen %s hinzufügen"), $deputy_title) ?>"></td>
                  <td style="padding-top: 15px"></td>
                  <td style="padding-top: 15px"><?= $deputysearch ?></td>
                  <td style="padding-top: 15px"></td>
                  <td style="padding-top: 15px"></td>
              </tr>
          <? endif ?>
          <? endif ?>

          <!-- Tutoren -->
          <tr>
              <td colspan="6"><hr></td>
          </tr>
          <tr>
              <td style="font-weight: bold;" colspan="6"><?= $tutor_title ?></td>
          </tr>
          <? $num = 0; foreach($tutoren as $tutor) : ?>
          <tr>
              <td></td>
              <td>
              <? if ($perm_dozent && !$tutor_is_locked) : ?>
                  <? if ($num > 0) : ?>
                  <a href="<?= $controller->url_for('course/basicdata/priorityupfor', $course_id, $tutor["user_id"], "tutor") ?>">
                  <?= Assets::img("icons/16/yellow/arr_2up.png", array('class' => 'middle')) ?></a>
                  <? endif; if ($num < count($tutoren)-1) : ?>
                  <a href="<?= $controller->url_for('course/basicdata/prioritydownfor', $course_id, $tutor["user_id"], "tutor") ?>">
                  <?= Assets::img("icons/16/yellow/arr_2down.png", array('class' => 'middle')) ?></a>
                  <? endif; ?>
              <? endif ?>
              </td>
              <td>
                  <a href="<?= URLHelper::getLink("about.php", array('username' => $tutor['username']))?>">
                      <?= Avatar::getAvatar($tutor["user_id"], $tutor['username'])->getImageTag(Avatar::SMALL) ?>
                  </a>
              </td>
              <td>
                  <?= get_fullname($tutor["user_id"], 'full_rev', true)." (".$tutor["username"].")" ?>
              </td>
              <td>
              <? if ($perm_dozent && !$tutor_is_locked) : ?>
                  <label><?= _("Funktion") ?>:
                      <input value="<?= htmlReady($tutor["label"]) ?>" type="text" name="label[<?= htmlReady($tutor["user_id"]) ?>]" title="<?= _("Die Funktion, die die Person in der Veranstaltung erfüllt.") ?>">
                  </label>
              <? else : ?>
                <?= $tutor["label"] ? htmlReady($tutor["label"]) : '&nbsp;' ?>
              <? endif ?>
              </td>
              <td>
                  <? if ($perm_dozent && !$tutor_is_locked) : ?>
                  <a href="<?= $controller->url_for('course/basicdata/deletetutor', $course_id, $tutor["user_id"]) ?>">
                  <?= Assets::img("icons/16/blue/trash.png") ?>
                  </a>
                  <? endif ?>
              </td>
          </tr>
          <? $num++; endforeach ?>
          <? if ($perm_dozent && !$tutor_is_locked) : ?>
              <tr>
                  <td style="padding-top: 15px"></td>
                  <td style="padding-top: 15px"><input class="middle" type="image" src="<?= Assets::image_path("icons/16/yellow/arr_2up.png") ?>" name="add_tutor" title="<?= sprintf(_("Neuen %s hinzufügen"), $tutor_title) ?>"></td>
                  <td style="padding-top: 15px"></td>
                  <td style="padding-top: 15px"><?= $tutorensuche ?></td>
                  <td style="padding-top: 15px"></td>
                  <td style="padding-top: 15px"></td>
              </tr>
          <? endif ?>
      </table>
      <? if (!$perm_dozent) : ?>
        <span style="color: #ff0000"><?= _("Die Personendaten können Sie mit Ihrem Status nicht bearbeiten!") ?></span>
      <? endif; ?>
  </div>


  <h2 id="bd_description" class="table_row_odd"><?= _("Beschreibungen") ?></h2>
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
  <?= Button::create(_('Übernehmen')) ?>
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
jQuery(function () {
    jQuery("input[name^=label]").autocomplete({
        source: <?=
        json_encode(preg_split("/[\s,;]+/", studip_utf8encode(Config::get()->getValue("PROPOSED_TEACHER_LABELS")), -1, PREG_SPLIT_NO_EMPTY));
        ?>
    });
});
</script>
</div>
