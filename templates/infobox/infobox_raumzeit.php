<?
# Lifter010: TODO
?>
<table class="infobox" width="250" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="infobox-img">
      <img src="<?=$GLOBALS['ASSETS_URL']?>images/<?=$picture?>">
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
            <?=_("Hier können Sie alle Termine der Veranstaltung verwalten.")?>
            <br>
          </td>
      </tr>

      <!-- Semesterauswahl -->

      <?
        // render "semesterauswahl" selection list partial
        echo $this->render_partial("infobox/infobox_dropdownlist_partial.php");
      ?>


      <? if ($GLOBALS['RESOURCES_ENABLE'] && $GLOBALS['RESOURCES_ENABLE_BOOKINGSTATUS_COLORING']) : ?>

      <!-- Legende -->

      <tr>
        <td width="100%" colspan="2">
          <b>Legende:</b>
          <br>
        </td>
      </tr>


        <tr>
          <td width="1%" align="center" valign="top">
            <img src="<?=$GLOBALS['ASSETS_URL']?>/images/plastic_red_small.jpg" height="20" width="25" alt="">
          </td>
          <td width="99%" align="left">
            <?=_("Kein Termin hat eine Raumbuchung!")?>
            <br>
          </td>

        </tr>


        <tr>
          <td width="1%" align="center" valign="top">
            <img src="<?=$GLOBALS['ASSETS_URL']?>/images/plastic_yellow_small.jpg" height="20" width="25" alt="">
          </td>
          <td width="99%" align="left">
            <?=_("Mindestens ein Termin hat keine Raumbuchung!")?>
            <br>

          </td>
        </tr>


        <tr>
          <td width="1%" align="center" valign="top">
            <img src="<?=$GLOBALS['ASSETS_URL']?>/images/plastic_green_small.jpg" height="20" width="25" alt="">
          </td>
          <td width="99%" align="left">
            <?=_("Alle Termine haben eine Raumbuchung.")?>

            <br>
          </td>
        </tr>

        <? endif; ?>
      <? if ($adminList) : ?>
      <tr>
        <td width="100%" colspan="2">
          <b><?= _("Veranstaltungsliste:") ?></b>
          <br>
        </td>
      </tr>


        <tr>
          <td width="1%" align="center" valign="top">
              <?= Assets::img("icons/16/black/link-intern.png")?>
          </td>
          <td width="99%" align="left">
            <?= $adminList->render() ?>
            <br>
          </td>

        </tr>
        <? endif ?>

    </table>
    </td>
  </tr>
</table>

