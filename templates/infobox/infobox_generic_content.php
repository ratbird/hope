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
        <td class="infobox-img">
          <? if (is_object($picture) && $picture instanceof Avatar) : ?>
            <? $picture_size = $picture->getDimension(Avatar::NORMAL) ?>
            <div style="background-image: url('<?= $picture->getURL(Avatar::NORMAL) ?>'); height: <?= $picture_size[1] ?>px;"></div>
          <? else : ?>
            <div style="background-image: url('<?= Assets::image_path($picture) ?>');"></div>
          <? endif ?>
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
                    <?= is_string($item['icon']) ? Icon::create2($item['icon'])->asImg() : $item['icon']->asImg() ?>
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
