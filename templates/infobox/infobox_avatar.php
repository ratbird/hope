<?
# Lifter010: TODO
?>
<? SkipLinks::addIndex('Infobox', 'infobox_content', 10000); ?>
<table class="infobox" width="250" border="0" cellpadding="0" cellspacing="0">

    <tr>
      <td class="infobox-avatar" align="right" style="background-image: url(<?= $picture ?>);">
      </td>
    </tr>

    <tr>
      <td class="infoboxrahmen" width="100%">
        <table cellpadding="4" cellspacing="0" id="infobox_content">

          <? for ($i = 0; $i < count($content); $i++) : ?>
            <? if ($content[$i]) : ?>

              <tr>
                <td width="100%" colspan="2">
                  <b><?=$content[$i]["kategorie"]?></b>
                  <br>
                </td>
              </tr>

              <? for ($j = 0; $j < count($content[$i]["eintrag"]); $j++) : ?>

                <tr>
                  <td width="1%" align="center" valign="top">
                    <img src="<?=$GLOBALS['ASSETS_URL']."images/".$content[$i]["eintrag"][$j]["icon"]?>">
                  </td>
                  <td width="99%" align="left">
                    <?=$content[$i]["eintrag"][$j]["text"]?>
                    <br>
                  </td>
                </tr>

              <? endfor ?>

            <? endif ?>
          <? endfor ?>

        </table>
      </td>
    </tr>
  </table>
