<div class="breadcrumb print"><?= _("Navigation: ") ?>
  <? $subnavigation = Navigation::getItem('/'); $count = 0; ?>
  <?= htmlready($subnavigation->getTitle()); ?>
  <? while ($subnavigation = $subnavigation->activeSubNavigation()) : ?>
    <? if ($subnavigation->isVisible() && $subnavigation->getTitle()) : ?>
      <? if ($count !== 0) print " » " ?>
      <? if ($subnavigation->getTitle() == _("Veranstaltung")) : ?>
        <?= htmlready($GLOBALS['SessSemName']['header_line']); ?>
      <? else : ?>
        <?= htmlready($subnavigation->getTitle()); ?>
      <? endif; ?>
    <? $count++; endif; ?>
  <? endwhile; ?>
</div>