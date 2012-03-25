<?
# Lifter010: TODO
?>
<table class="infobox" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->

  <tr>
    <td class="infobox-img">
      <?= Assets::img('infobox/institutes.jpg') ?>
    </td>
  </tr>

  <tr>
    <td class="infoboxrahmen" width="100%">
    <table background="<?=$GLOBALS['ASSETS_URL']?>images/blank.gif" align="center" width="99%" border="0" cellpadding="4" cellspacing="0">

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

                            <?= sprintf(_("Auf dieser Seite k&ouml;nnen Sie Personen der Einrichtung %s zuordnen."), "<b>" . htmlReady($inst_name) . "</b>") ?><br>
                             <?= _("Um weitere Personen als Mitarbeiter hinzuzuf&uuml;gen, benutzen Sie die Suche."); ?>

            <br>
          </td>
      </tr>

    </table>
    </td>
  </tr>
</table>

