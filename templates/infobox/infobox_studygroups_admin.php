<table class="infobox" align="center" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->

  <tr>
    <td class="infobox-img">
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
        <td width="100%" colspan="2">
          <b><?=_("Informationen")?>:</b>
          <br>
        </td>
      </tr>

      <tr>
          <td align="center" valign="top" width="1%">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/ausruf_small.gif">
          </td>
          <td width="99%" align="left">
            <?=_("Hier können Sie für Ihre Studiengruppe administrieren.")?>
            <br>
          </td>
      </tr>

      <!-- Aktionen -->

      <tr>
        <td width="100%" colspan="2">
          <b><?=_("Aktionen")?>:</b>
          <br>
        </td>
      </tr>

       <tr>
         <td align="center" valign="top" width="1%">
           <img src="<?=$GLOBALS['ASSETS_URL']?>images/link_intern.gif">
         </td>
         <td width="99%" align="left">
           <?='<a href="studygroups_admin.php?cmd=createStudygroup">' . _("Studiengruppe gründen") . '</a>'?>
           <br>
         </td>
      </tr>

       <tr>
         <td align="center" valign="top" width="1%">
           <img src="<?=$GLOBALS['ASSETS_URL']?>images/link_intern.gif">
         </td>
         <td width="99%" align="left">
           <?='<a href="studygroups_admin.php?cmd=deleteStudygroup">' . _("Studiengruppe löschen") . '</a>'?>
           <br>
         </td>
      </tr>

    </table>
    </td>
  </tr>
</table>

