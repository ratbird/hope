<div class="breadcrumb print">
    <?= _('Navigation:') ?>

    <? if ($navigation instanceof CourseNavigation) : ?>
        <?= htmlReady($GLOBALS['SessSemName']['header_line']) ?>
    <? else : ?>
        <?= htmlReady($navigation->getTitle()) ?>
    <? endif ?>

    <? while ($navigation = $navigation->activeSubNavigation()) : ?>
        <? if ($navigation->isVisible()) : ?>
            » <?= htmlReady($navigation->getTitle()) ?>
        <? endif ?>
    <? endwhile ?>
</div>
