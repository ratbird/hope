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

    <script src="<?= URLHelper::getScriptLink('dispatch.php/localizations/' . $_SESSION['_language']) ?>"></script>

    <script>
      STUDIP.ABSOLUTE_URI_STUDIP = "<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>";
      STUDIP.ASSETS_URL = "<?= $GLOBALS['ASSETS_URL'] ?>";
      String.locale = "<?= htmlReady(strtr($_SESSION['_language'], '_', '-')) ?>";
      <? if (PersonalNotifications::isActivated() && $GLOBALS['perm']->have_perm("autor")) : ?>
      STUDIP.jsupdate_enable = true;
      <? endif ?>
    </script>
</head>

<body id="<?= $body_id ? $body_id : PageLayout::getBodyElementId() ?>">
<div id="layout_wrapper">
    <? SkipLinks::insertContainer() ?>
    <? SkipLinks::addIndex(_("Hauptinhalt"), 'layout_content', 100, true) ?>
    <?= PageLayout::getBodyElements() ?>

    <? include 'lib/include/header.php' ?>

        <div id="layout_container" class="flex-container">
            <div id="layout_content" class="flex-item">
                <?= implode(PageLayout::getMessages()) ?>
                <?= $content_for_layout ?>
            </div>
            <? if ($infobox) : ?>
            <div id="layout_sidebar" class="flex-item">
                <div id="layout_infobox">
                    <?= is_array($infobox) ? $this->render_partial('infobox/infobox_generic_content', $infobox) : $infobox ?>
                </div>
            </div>
            <? endif ?>                
        </div>
    </div> <? // Closes #layout_page opened in included templates/header.php ?>

    <!-- Ende Page -->
    <div id="layout_push"></div>
</div>

    <? include 'templates/footer.php'; ?>

    <?= SkipLinks::getHTML() ?>
</body>
</html>
