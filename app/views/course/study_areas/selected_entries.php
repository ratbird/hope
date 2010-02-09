<ul id="study_area_selection_selected">

  <? foreach ($selection->getAreas() as $area) : ?>
    <?= $this->render_partial('course/study_areas/selected_entry', compact('area')) ?>
  <? endforeach ?>

</ul>

