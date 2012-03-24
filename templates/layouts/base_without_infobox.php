<?
# Lifter010: TODO
?>
<!DOCTYPE html>
<html class="no-js">
<head>
    <meta charset="WINDOWS-1252">
    <title>
      <?= htmlReady(PageLayout::getTitle() . ' - ' . $GLOBALS['UNI_NAME_CLEAN']) ?>
    </title>
    <?= PageLayout::getHeadElements() ?>
    <!--[if IE]>
    <link rel="stylesheet" href="<?= Assets::stylesheet_path('ie.css') ?>" media="screen,print">
    <![endif]-->

    <script src="<?= URLHelper::getLink('dispatch.php/localizations/' . $_SESSION['_language']) ?>"></script>

    <script>
      STUDIP.ABSOLUTE_URI_STUDIP = "<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>";
      STUDIP.ASSETS_URL = "<?= $GLOBALS['ASSETS_URL'] ?>";
      String.locale = "<?= htmlReady(strtr($_SESSION['_language'], '_', '-')) ?>";
    </script>
</head>

<body id="<?= $body_id ? $body_id : PageLayout::getBodyElementId() ?>">
<div id="layout_wrapper">
    <? SkipLinks::insertContainer() ?>
    <? SkipLinks::addIndex(_("Hauptinhalt"), 'layout_container', 100, true) ?>
    <?= PageLayout::getBodyElements() ?>

    <? include 'lib/include/header.php' ?>

    <div id="layout_container">
        <?= implode(PageLayout::getMessages()) ?>
        <?= $content_for_layout ?>
        <div class="clear"></div>
    </div>
</div>
<!-- Ende Page -->
    <div id="layout_push"></div>
</div>

    <? include 'templates/footer.php'; ?>

    <?= SkipLinks::getHTML() ?>

</body>
</html>
