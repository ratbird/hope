<table align="center" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->

  <tr>
    <td class="infobox" width="100%" align="right">
      <img src="<?=$GLOBALS['ASSETS_URL']?>images/infoboxes/<?=$picture?>">
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
            <font size="-1"><?=_("Hier können Sie für die einzelnen Termine Beschreibungen eingeben, Themen im Forum und Dateiordner anlegen.")?></font>
            <br>
          </td>
      </tr>

      <tr>
          <td class="infobox" align="center" valign="top" width="1%">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/ausruf_small.gif">
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1"><?=sprintf(_("Zeitänderungen, Raumbuchungen und Termine anlegen können Sie unter %sZeiten%s."), '<a href="raumzeit.php">', '</a>')?></font><br>
            <br>
                        <?=$times_info?>
          </td>
      </tr>
            <!-- Ansicht -->
            <? if ($GLOBALS["RESOURCES_ENABLE_EXPERT_SCHEDULE_VIEW"]) : ?>
        <tr>
        <td class="infobox" width="100%" colspan="2">
          <font size="-1"><b><?=_("Ansicht")?>:</b></font>
          <br>
        </td>
      </tr>

      <tr>
        <td class="infobox" align="center" valign="top" width="1%">
          <img src="<?=$GLOBALS['ASSETS_URL']?>images/forumrot.gif">
        </td>
        <td class="infobox" width="99%" align="left">
          <font size="-1"><a href="themen.php?cmd=changeViewMode&newFilter=simple"><?=_("Standardansicht")?></a></font>
          <br>
        </td>
      </tr>

        <tr>
        <td class="infobox" align="center" valign="top" width="1%">
          <img src="<?=$GLOBALS['ASSETS_URL']?>images/forumgrau.gif">
        </td>
        <td class="infobox" width="99%" align="left">
          <font size="-1"><a href="themen.php?cmd=changeViewMode&newFilter=expert"><?=_("Erweiterte Ansicht")?></a></font>
          <br>
        </td>
      </tr>

            <? endif; // globaler Schalter ?>

      <!-- Semesterauswahl -->

      <?
        // render "semesterauswahl" selection list partial
        echo $this->render_partial("infobox/infobox_dropdownlist_partial.php");
      ?>

      <!-- Aktionen -->

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
           <font size="-1"><?='<a href="raumzeit.php?cmd=createNewSingleDate#newSingleDate">' . _("Einen neuen Termin anlegen") . '</a>'?></font>
           <br>
         </td>
      </tr>

    </table>
    </td>
  </tr>
</table>

