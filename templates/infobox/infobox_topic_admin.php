<?
# Lifter010: TODO
?>
<table class="infobox" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->

  <tr>
    <td class="infobox-img">
      <img src="<?= $GLOBALS['ASSETS_URL']?>images/<?= $picture?>">
    </td>
  </tr>

  <tr>
    <td class="infoboxrahmen" width="100%">
    <table align="center" width="99%" border="0" cellpadding="4" cellspacing="0">

      <!-- Informationen -->

      <tr>
        <td width="100%" colspan="2">
          <b><?= _("Informationen")?>:</b>
          <br>
        </td>
      </tr>

      <tr>
          <td align="center" valign="top" width="1%">
            <?= Assets::img('icons/16/black/info.png') ?>
          </td>
          <td width="99%" align="left">
            <?= _("Hier können Sie für die einzelnen Termine Beschreibungen eingeben, Themen im Forum und Dateiordner anlegen.")?>
            <br>
          </td>
      </tr>

      <tr>
          <td align="center" valign="top" width="1%">
            <?= Assets::img('icons/16/black/info.png') ?>
          </td>
          <td width="99%" align="left">
            <?= sprintf(_("Zeitänderungen, Raumbuchungen und Termine anlegen können Sie unter %sZeiten%s."),
                '<a href="'. URLHelper::getLink('raumzeit.php') . '">', '</a>')?><br>
            <br>
            <?= $times_info?>
          </td>
      </tr>

      <!-- Ansicht -->
      <? if ($GLOBALS["RESOURCES_ENABLE_EXPERT_SCHEDULE_VIEW"]) : ?>
      <tr>
        <td width="100%" colspan="2">
          <b><?= _("Ansicht")?>:</b>
          <br>
        </td>
      </tr>

      <tr>
        <td align="center" valign="top" width="1%">
          <img src="<?= $GLOBALS['ASSETS_URL']?>images/icons/16/red/arr_1right.png">
        </td>
        <td width="99%" align="left">
            <a href="<?= URLHelper::getLink('themen.php?cmd=changeViewMode&newFilter=simple') ?>"><?= _("Standardansicht")?></a>
          <br>
        </td>
      </tr>

      <tr>
        <td align="center" valign="top" width="1%">
          <img src="<?= $GLOBALS['ASSETS_URL']?>images/icons/16/black/arr_1right.png">
        </td>
        <td width="99%" align="left">
          <a href="<?= URLHelper::getLink('themen.php?cmd=changeViewMode&newFilter=expert') ?>"><?= _("Erweiterte Ansicht")?></a>
          <br>
        </td>
      </tr>
      <? endif ?>

      <!-- Semesterauswahl -->

      <?= $this->render_partial("infobox/infobox_dropdownlist_partial.php") ?>

      <!-- Aktionen -->

      <tr>
        <td width="100%" colspan="2">
          <b><?= _("Aktionen")?>:</b>
          <br>
        </td>
      </tr>

       <tr>
         <td align="center" valign="top" width="1%">
           <img src="<?= $GLOBALS['ASSETS_URL']?>images/icons/16/black/schedule.png">
         </td>
         <td width="99%" align="left">
           <a href="<?= URLHelper::getLink('raumzeit.php?cmd=createNewSingleDate#newSingleDate') ?>"><?= _("Einen neuen Termin anlegen") ?></a>
           <br>
         </td>
      </tr>

    </table>
    </td>
  </tr>
</table>

