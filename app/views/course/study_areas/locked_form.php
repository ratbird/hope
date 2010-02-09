<div id="study_area_selection">

  <em>
    <?= _("Sie dürfen die Studienbereichszuordnung dieser Veranstaltung nicht verändern.") ?>
    <?= _("Diese Sperrung ist von einer Administratorin oder einem Administrator vorgenommen worden.") ?>
  </em>

  <h3><?= _("Bestehende Zuordnungen:") ?></h3>

  <? if ($selection->size()) : ?>
    <em id="study_area_selection_none" style="display:none;"><?= _("Bisher wurde noch keine Zuordnung vorgenommen") ?></em>
  <? else: ?>
    <em id="study_area_selection_none"><?= _("Bisher wurde noch keine Zuordnung vorgenommen") ?></em>
  <? endif ?>

  <ul>
    <? foreach ($selection->getAreas() as $area) : ?>
      <li><?= htmlReady($area->getPath(' > ')) ?></li>
    <? endforeach ?>
  </ul>

</div>

