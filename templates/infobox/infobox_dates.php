<table align="center" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->

  <tr>
    <td class="infobox" width="100%" align="right">
      <img src="<?=$GLOBALS['ASSETS_URL']?>images/<?=$picture?>">
    </td>
  </tr>

  <tr>
    <td class="infoboxrahmen" width="100%">
    <table background="<?=$GLOBALS['ASSETS_URL']?>images/white.gif" align="center" width="99%" border="0" cellpadding="4" cellspacing="0">

      <!-- Statusmeldungen -->
      <? if ($messages) :
            // render status messages partial
            echo $this->render_partial("infobox/infobox_statusmessages_partial.php");
         endif;
      ?>

      <!-- Informationen -->

      <tr>
        <td class="infobox" width="100%" colspan="2">
          <font size="-1"><b><?=_("Informationen")?>:</b></font>
          <br>
        </td>
      </tr>

      <tr>
          <td class="infobox" align="center" valign="top" width="1%">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/ausruf_small.gif">
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1"><?=_("Hier finden Sie alle Termine der Veranstaltung.")?></font>
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
        <td class="infobox" width="100%" colspan="2">
          <font size="-1"><b><?=_("Aktionen")?>:</b></font>
          <br>
        </td>
      </tr>

       <tr>
         <td class="infobox" align="center" valign="top" width="1%">
           <img src="<?=$GLOBALS['ASSETS_URL']?>images/link_intern.gif">
         </td>
         <td class="infobox" width="99%" align="left">
           <font size="-1">
                       <a href="<?= URLHelper::getLink("raumzeit.php?cmd=createNewSingleDate#newSingleDate")?>"><?= _("Einen neuen Termin anlegen") ?></a>
                     </font>
           <br>
         </td>
      </tr>

       <tr>
         <td class="infobox" align="center" valign="top" width="1%">
           <img src="<?=$GLOBALS['ASSETS_URL']?>images/link_intern.gif">
         </td>
         <td class="infobox" width="99%" align="left">
           <font size="-1">
                       <a href="<?= URLHelper::getLink("dispatch.php/course/management/index/dates")?>"><?= _("Zur Terminverwaltung") ?></a>
                     </font>
           <br>
         </td>
      </tr>

       <tr>
         <td class="infobox" align="center" valign="top" width="1%">
           <img src="<?=$GLOBALS['ASSETS_URL']?>images/link_intern.gif">
         </td>
         <td class="infobox" width="99%" align="left">
           <font size="-1">
                       <a href="<?= URLHelper::getLink("themen.php?section=topics")?>"><?= _("Zur Ablaufplanverwaltung") ?></a>
                     </font>
           <br>
         </td>
      </tr>

       <tr>
         <td class="infobox" align="center" valign="top" width="1%">
           <img src="<?=$GLOBALS['ASSETS_URL']?>images/link_intern.gif">
         </td>
         <td class="infobox" width="99%" align="left">
           <font size="-1">
                       <a href="<?= URLHelper::getLink("dates.php?export=true")?>"><?= _("Exportieren") ?></a>
                     </font>
           <br>
         </td>
      </tr>
            <? endif; ?>

    </table>
    </td>
  </tr>
</table>

