<table align="center" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->

  <tr>
    <td class="infobox" width="100%" align="right">
      <?= Assets::img('einrichtungen.jpg') ?>
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
            <font size="-1">
							<?= sprintf(_("Auf dieser Seite k&ouml;nnen Sie Personen der Einrichtung %s zuordnen."), "<b>" . htmlReady($inst_name) . "</b>") ?><br>
							 <?= _("Um weitere Personen als Mitarbeiter hinzuzuf&uuml;gen, benutzen Sie die Suche."); ?>
						</font>
            <br>
          </td>
      </tr>

    </table>
    </td>
  </tr>
</table>

