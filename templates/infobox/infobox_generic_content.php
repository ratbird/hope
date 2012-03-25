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
          <?= Assets::img($picture) ?>
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
