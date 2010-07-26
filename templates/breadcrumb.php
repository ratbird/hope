<div class="breadcrumb print"><?= _("Navigation: ") ?>
  <? $subnavigation = Navigation::getItem('/'); $count = 0; ?>
  <?= $subnavigation->getTitle(); ?>
  <? while ($subnavigation = $subnavigation->activeSubNavigation()) : ?>
    <? if ($subnavigation->isVisible() && $subnavigation->getTitle()) : ?>
      <? if ($count !== 0) print " » " ?>
      <? if ($subnavigation->getTitle() == _("Veranstaltung")) : ?>
        <?= _("Meine Veranstaltungen") . " » " . $GLOBALS['SessSemName'][0]; ?>
      <? else : ?>
        <?= $subnavigation->getTitle(); ?>
      <? endif; ?>
    <? $count++; endif; ?>
  <? endwhile; ?>
</div>