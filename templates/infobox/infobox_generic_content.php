<?
# Lifter010: TODO
?>
<?
//
// Standard Infobox-Layout as of july 2007 used by the method print_infobox() in visual.inc.php
// WARNING : This type of infobox is deprecated and will be subject to changes!
//           It's provided for compatibility reasons only.
//
?>
<? SkipLinks::addIndex('Infobox', 'infobox_content', 10000); ?>
<table class="infobox" width="250" cellpadding="0" cellspacing="0">

    <? if ($picture) : ?>

      <tr>
          <?
            $image_url = Assets::image_path($picture);
            $image_path = str_replace($GLOBALS['ABSOLUTE_URI_STUDIP'], "", $image_url);
            $image_size = getimagesize($GLOBALS['STUDIP_BASE_PATH']."/public/".$image_path);
            if ($image_size) {
                $height = floor($image_size[1] / $image_size[0] * 250);
                $height = $height > 250 ? 250 : $height;
            } else {
                $height = 250;
            }
          ?>
        <td class="infobox-img">
            <div style="background-image: url('<?= Assets::image_path($image_url) ?>'); min-height: <?= $height ?>px;"></div>
        </td>
      </tr>

    <? endif ?>

    <tr>
      <td class="infoboxrahmen">
        <table id="infobox_content" cellpadding="4" cellspacing="0">

          <? foreach ($content as $category) : ?>
            <tr>
              <td colspan="2">
                <b><?= $category["kategorie"] ?></b>
              </td>
            </tr>

            <? if (isset($category['eintrag'])) : ?>
              <? foreach ($category['eintrag'] as $item) : ?>

                <tr>
                  <td width="1%" align="center" valign="top">
                    <?= Assets::img($item['icon']) ?>
                  </td>
                  <td width="99%">
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
