<?
//
// Standard Infobox-Layout as of july 2007 used by the method print_infobox() in visual.inc.php
// WARNING : This type of infobox is deprecated and will be subject to changes!
//           It's provided for compatibility reasons only.
//
?>

<table align="center" width="250" cellpadding="0" cellspacing="0">

    <? if ($picture) : ?>

      <tr>
        <td class="blank">
          <?= Assets::img($picture) ?>
        </td>
      </tr>

    <? endif ?>

    <tr>
      <td class="infoboxrahmen">
        <table cellpadding="4" cellspacing="0">

          <? foreach ($content as $category) : ?>
            <tr>
              <td class="infobox" colspan="2">
                <font size="-1"><b><?= $category["kategorie"] ?></b></font>
              </td>
            </tr>

            <? if (isset($category['eintrag'])) : ?>
              <? foreach ($category['eintrag'] as $item) : ?>

                <tr>
                  <td class="infobox" width="1%" align="center" valign="top">
                    <?= Assets::img($item['icon']) ?>
                  </td>
                  <td class="infobox" width="99%">
                    <?= $item["text"] ?>
                  </td>
                </tr>

              <? endforeach ?>

            <? endif ?>
          <? endforeach ?>

        </table>
      </td>
    </tr>
  </table>
