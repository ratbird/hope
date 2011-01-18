<!DOCTYPE html>
<html>
  <head>
    <meta charset="WINDOWS-1252">
    <title>
      <?= htmlReady(PageLayout::getTitle() . ' - ' . $GLOBALS['UNI_NAME_CLEAN']) ?>
    </title>
    <?= PageLayout::getHeadElements() ?>

    <script src="<?= URLHelper::getLink('dispatch.php/localizations/' . $_SESSION['_language']) ?>"></script>

    <script>
      STUDIP.ABSOLUTE_URI_STUDIP = "<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>";
      STUDIP.ASSETS_URL = "<?= $GLOBALS['ASSETS_URL'] ?>";
      String.locale = "<?= strtr($_SESSION['_language'], '_', '-') ?>";
    </script>
  </head>

  <body id="<?= $body_id ? $body_id : PageLayout::getBodyElementId() ?>">
    <?= PageLayout::getBodyElements() ?>
    <div id="overdiv_container"></div>

    <? include 'lib/include/header.php' ?>

    <div id="layout_container" style="padding: 1em">
      <div id="layout_infobox">
        <? $infobox = isset($infobox)
                      ? $infobox
                      : array('picture' => 'infobox/warning.jpg',
                              'content' => array(
                                             array('kategorie' => _("Infobox fehlt."))
                                           )) ?>
        <?= $GLOBALS['template_factory']->render('infobox/infobox_generic_content', $infobox) ?>
      </div>
      <div id="layout_content">
        <?= $content_for_layout ?>
        <div class="clear"></div>
      </div>
    </div>
  </body>
</html>
