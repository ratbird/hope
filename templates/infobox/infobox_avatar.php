<table align="center" width="250" border="0" cellpadding="0" cellspacing="0">

    <tr>
      <td class="infobox-avatar" align="right" style="background-image: url(<?= $picture ?>);">
      </td>
    </tr>

    <tr>
      <td class="infoboxrahmen" width="100%">
        <table background="<?= $GLOBALS['ASSETS_URL']?>images/white.gif" align="center" width="99%" border="0" cellpadding="4" cellspacing="0">

          <? for ($i = 0; $i < count($content); $i++) : ?>
            <? if ($content[$i]) : ?>

              <tr>
                <td class="infobox" width="100%" colspan="2">
                  <font size="-1"><b><?=$content[$i]["kategorie"]?></b></font>
                  <br>
                </td>
              </tr>

              <? for ($j = 0; $j < count($content[$i]["eintrag"]); $j++) : ?>

                <tr>
                  <td class="infobox" width="1%" align="center" valign="top">
                    <img src="<?=$GLOBALS['ASSETS_URL']."images/".$content[$i]["eintrag"][$j]["icon"]?>">
                  </td>
                  <td class="infobox" width="99%" align="left">
                    <?=$content[$i]["eintrag"][$j]["text"]?>
                    <br>
                  </td>
                </tr>

              <? endfor; ?>

            <? endif; ?>
          <? endfor; ?>

        </table>
      </td>
    </tr>
  </table>
