<?
# Lifter010: TODO
?>
<table class="infobox" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->

  <tr>
    <td class="infobox-img">
      <img src="<?=$GLOBALS['ASSETS_URL']?>images/<?=$picture?>">
    </td>
  </tr>

  <tr>
    <td class="infoboxrahmen" width="100%">
    <table align="center" width="99%" border="0" cellpadding="4" cellspacing="0">
      <!-- Informationen -->

      <tr>
        <td width="100%" colspan="2">
          <b><?=_("Informationen")?>:</b>
          <br>
        </td>
      </tr>

      <tr>
          <td align="center" valign="top" width="1%">
            <?= Assets::img('icons/16/black/info.png') ?>
          </td>
          <td width="99%" align="left">
            <?=_("Hier finden Sie alle Termine der Veranstaltung.")?>
            <br>
          </td>
      </tr>

      <!-- Semesterauswahl -->

      <?
        // render "semesterauswahl" selection list partial
        echo $this->render_partial("infobox/infobox_dropdownlist_partial.php");
      ?>

      <!-- Aktionen -->

            <? if ($rechte) : ?>
      <tr>
        <td width="100%" colspan="2">
          <b><?=_("Aktionen")?>:</b>
          <br>
        </td>
      </tr>

       <tr>
         <td align="center" valign="top" width="1%">
           <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/black/add/date.png">
         </td>
         <td width="99%" align="left">
           <a href="<?= URLHelper::getLink("raumzeit.php?cmd=createNewSingleDate#newSingleDate")?>"><?= _("Einen neuen Termin anlegen") ?></a>
           <br>
         </td>
      </tr>

       <tr>
         <td align="center" valign="top" width="1%">
           <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/black/date.png">
         </td>
         <td width="99%" align="left">

                       <a href="<?= URLHelper::getLink("raumzeit.php")?>"><?= _("Zur Terminverwaltung") ?></a>

           <br>
         </td>
      </tr>

       <tr>
         <td align="center" valign="top" width="1%">
           <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/black/schedule.png">
         </td>
         <td width="99%" align="left">

                       <a href="<?= URLHelper::getLink("themen.php")?>"><?= _("Zur Ablaufplanverwaltung") ?></a>

           <br>
         </td>
      </tr>

       <tr>
         <td align="center" valign="top" width="1%">
           <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/black/download.png">
         </td>
         <td width="99%" align="left">

                       <a href="<?= URLHelper::getLink("dates.php?export=true")?>"><?= _("Exportieren") ?></a>

           <br>
         </td>
      </tr>
            <? endif ?>

    </table>
    </td>
  </tr>
</table>

