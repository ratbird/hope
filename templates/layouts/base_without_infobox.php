<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=WINDOWS-1252">
    <title>
      <?= $GLOBALS['HTML_HEAD_TITLE'] ?> - <?= htmlReady(PageLayout::getTitle()) ?>
    </title>
    <?= PageLayout::getHeadElements() ?>

    <script src="<?= URLHelper::getLink('dispatch.php/localizations/' . $GLOBALS['_language']) ?>"></script>

    <script>
      STUDIP.ABSOLUTE_URI_STUDIP = "<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>";
      STUDIP.ASSETS_URL = "<?= $GLOBALS['ASSETS_URL'] ?>";
      String.locale = "<?= strtr($GLOBALS['_language'], '_', '-') ?>";
    </script>
  </head>

  <body id="<?= $body_id ? $body_id : PageLayout::getBodyElementId() ?>">
    <?= PageLayout::getBodyElements() ?>
    <div id="overdiv_container"></div>

    <? include 'lib/include/header.php' ?>

    <div id="layout_container" style="padding: 1em;">
        <?= $content_for_layout ?>
        <div class="clear"></div>
    </div>
  </body>
</html>
