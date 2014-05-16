<div id="layout_page">
        <? if (PageLayout::isHeaderEnabled() && is_object($GLOBALS['user']) && $GLOBALS['user']->id != 'nobody' && Navigation::hasItem('/course') && Navigation::getItem('/course')->isActive() && $_SESSION['seminar_change_view_'.$GLOBALS['SessionSeminar']]) : ?>
            <?= $GLOBALS["template_factory"]->open('change_view')->render() ?>
        <? endif ?>
        <? $navigation = PageLayout::getTabNavigation() ?>
        <? if (PageLayout::isHeaderEnabled() && isset($navigation)) : ?>
            <? $tabs_template = $GLOBALS["template_factory"]->open('tabs') ?>
            <? $tabs_template->set_attribute("navigation", $navigation) ?>
            <?= $tabs_template->render() ?>
        <? endif ?>