<?
$_id = htmlReady($area->getID());
?>
<li id="study_area_selection_<?= $_id ?>" class="<?= TextHelper::cycle('odd', 'even') ?>">
  <input title="Zuordnung entfernen" alt="Zuordnung entfernen"
         class="{id: '<?= $_id ?>', course_id: '<?= htmlReady($course_id) ?>'}"
         style="vertical-align: middle;"
         type="image"
         name="study_area_selection[remove][<?= $_id ?>]"
         src="<?= Assets::image_path('trash.gif') ?>">
  <a class="study_area_selection_expand {id: '<?= htmlReady($area->getParentId()) ?>', course_id: '<?= htmlReady($course_id) ?>'}"
     href="<?= URLHelper::getLink(isset($url) ? $url : '',
                                  array('study_area_selection[selected]' => $area->getParentId())) ?>">
    <?= htmlReady($area->getPath(' · ')) ?>
  </a>
  <? if($area->isModule()) echo $area->getModuleInfoHTML($semester_id); ?>
  <input type="hidden" name="study_area_selection[areas][]" class="study_area_selection_area" value="<?= $_id ?>">
</li>

