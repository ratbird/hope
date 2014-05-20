<?php
# Lifter010: TODO

foreach (Navigation::getItem('/') as $path1 => $nav1) {
    if ($nav1->isActive()) {
        foreach ($nav1->getSubNavigation() as $path2 => $nav2) {
            if ($nav2->isActive()) {
                $nav_links = new NavigationWidget();
                foreach ($nav2->getSubNavigation() as $path3 => $nav3) {
                    if (!$nav3->isVisible()) {
                        continue;
                    }
                    $image = $nav3->getImage();
                    $link = $nav_links->addLink(
                        $nav3->getTitle(),
                        URLHelper::getUrl($nav3->getURL(), array(), true),
                        $image ? $image['src'] : null,
                        array('id' => "nav__".$path1."_".$path2."_".$path3)
                    );
                    $link->setActive($nav3->isActive());
                }
                if ($nav_links->hasElements()) {
                    Sidebar::get()->insertWidget($nav_links, ':first');
                }
            }
        }
    }
}


$navigation = PageLayout::getTabNavigation();

// Remove help from navigation and set it to help center
if (Navigation::hasItem('/links/help')) {
    $nav = Navigation::getItem('/links/help');
    Navigation::removeItem('/links/help');

    Helpbar::get()->insertLink(_('Hilfe-Wiki'), $nav->getURL(), 'icons/16/white/link-extern.png', '_blank');

    Navigation::removeItem('/footer/help');
}
?>
<!DOCTYPE html>
<html class="no-js">
<head>
    <meta charset="WINDOWS-1252">
    <title>
      <?= htmlReady(PageLayout::getTitle() . ' - ' . $GLOBALS['UNI_NAME_CLEAN']) ?>
    </title>
    <?php
        // needs to be included in lib/include/html_head.inc.php as well
        include 'app/views/WysiwygHtmlHeadBeforeJS.php';
    ?>
    <?= PageLayout::getHeadElements() ?>

    <script src="<?= URLHelper::getScriptLink('dispatch.php/localizations/' . $_SESSION['_language']) ?>"></script>

    <script>
        STUDIP.ABSOLUTE_URI_STUDIP = "<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>";
        STUDIP.ASSETS_URL = "<?= $GLOBALS['ASSETS_URL'] ?>";
        String.locale = "<?= htmlReady(strtr($_SESSION['_language'], '_', '-')) ?>";
        <? if (is_object($GLOBALS['perm']) && $GLOBALS['perm']->have_perm('autor') && PersonalNotifications::isActivated()) : ?>
        STUDIP.jsupdate_enable = true;
        <? endif ?>
        STUDIP.URLHelper.parameters = <?= json_encode(studip_utf8encode(URLHelper::getLinkParams())) ?>;
    </script>
    <?php
        // needs to be included in lib/include/html_head.inc.php as well
        include 'app/views/WysiwygHtmlHead.php';
    ?>
</head>

<body id="<?= $body_id ? $body_id : PageLayout::getBodyElementId() ?>">
<div id="layout_wrapper">
    <? SkipLinks::insertContainer() ?>
    <? SkipLinks::addIndex(_("Hauptinhalt"), 'layout_content', 100, true) ?>
    <?= PageLayout::getBodyElements() ?>

    <? include 'lib/include/header.php' ?>

    <div id="layout_page">
        <? if (PageLayout::isHeaderEnabled() && is_object($GLOBALS['user']) && $GLOBALS['user']->id != 'nobody' && Navigation::hasItem('/course') && Navigation::getItem('/course')->isActive() && $_SESSION['seminar_change_view_'.$GLOBALS['SessionSeminar']]) : ?>
            <?= $this->render_partial('change_view') ?>
        <? endif ?>

        <? if (PageLayout::isHeaderEnabled() && isset($navigation)) : ?>
            <?= $this->render_partial('tabs', compact("navigation")) ?>
        <? endif ?>

        <?= Helpbar::get()->render() ?>
        <div id="layout_container">
            <?= Sidebar::get()->render() ?>
            <div id="layout_content">
                <?= implode(PageLayout::getMessages()) ?>
                <?= $content_for_layout ?>
            </div>
            <? if ($infobox) : ?>
            <div id="layout_sidebar">
                <div id="layout_infobox">
                    <?= is_array($infobox) ? $this->render_partial('infobox/infobox_generic_content', $infobox) : $infobox ?>
                </div>
            </div>
            <? endif ?>
        </div>
    </div> <? // Closes #layout_page opened in included templates/header.php ?>

    <? include 'templates/footer.php'; ?>
    <!-- Ende Page -->
    <? /* <div id="layout_push"></div> */ ?>
</div>


    <?= SkipLinks::getHTML() ?>
</body>
</html>
