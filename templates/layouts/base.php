<?php
# Lifter010: TODO

$navigation = PageLayout::getTabNavigation();
$tab_root_path = PageLayout::getTabNavigationPath();
if ($navigation) {
    $subnavigation = $navigation->activeSubNavigation();
    if ($subnavigation !== null) {
        $nav_links = new NavigationWidget();
        foreach ($subnavigation as $path => $nav) {
            if (!$nav->isVisible()) {
                continue;
            }
            $image = $nav->getImage();
            $nav_id = "nav_".implode("_", preg_split("/\//", $tab_root_path, -1, PREG_SPLIT_NO_EMPTY))."_".$path;
            $link = $nav_links->addLink(
                $nav->getTitle(),
                URLHelper::getLink($nav->getURL()),
                $image ? $image['src'] : null,
                array('id' => $nav_id)
            );
            $link->setActive($nav->isActive());
            // TODO check $nav->isEnabled() and make link ".quit" if true "<span class="quiet">"
        }
        if ($nav_links->hasElements()) {
            Sidebar::get()->insertWidget($nav_links, ':first');
        }
    }
}

// Remove help from navigation and set it to help center
if (Navigation::hasItem('/links/help')) {
    $nav = Navigation::getItem('/links/help');
    Navigation::removeItem('/links/help');

    Helpbar::get()->insertLink(_('Hilfe-Wiki'), $nav->getURL(), 'icons/16/white/link-extern.png', '_blank');

    Navigation::removeItem('/footer/help');
}

// TODO: Remove this after sidebar migration has been completed
if ($infobox && is_array($infobox)) {
    $sidebar = Sidebar::get();
    if (!$sidebar->getImage()) {
        $sidebar->setImage(is_object($infobox['picture']) ? $infobox['picture']->getURL(Avatar::NORMAL) : $infobox['picture']);
    }
    foreach (array_reverse($infobox['content']) as $entry) {
        $widget = new InfoboxWidget();
        $widget->setTitle($entry['kategorie'] . ' (Infobox)');
        if (isset($entry['eintrag']) && is_array($entry['eintrag'])) {
            foreach (@$entry['eintrag'] as $row) {
                $icon = str_replace('/black/', '/blue/', $row['icon']);
                $widget->addElement(new InfoboxElement($row['text'], $icon));
            }
        }
        $sidebar->insertWidget($widget, ':first');
    }
    unset($infobox);
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
            <?= $this->render_partial('change_view', array('changed_status' => $_SESSION['seminar_change_view_'.$GLOBALS['SessionSeminar']])) ?>
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
