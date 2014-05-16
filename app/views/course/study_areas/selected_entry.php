<?
# Lifter010: TODO
$_id = htmlReady($area->getID());
?>
<li id="study_area_selection_<?= $_id ?>" class="<?= TextHelper::cycle('odd', 'even') ?>">
<?= Assets::input("icons/16/blue/trash.png", array('type' => "image", 'class' => "middle", 'name' => "show", 'data-id' => $_id, 'data-course_id' => htmlReady($course_id), 'name' => "study_area_selection[remove][".$id."]", 'style' => "vertical-align: middle;", 'title' => _('Zuordnung entfernen'))) ?>
  <a class="study_area_selection_expand"
     data-id="<?= htmlReady($area->getParentId()) ?>" data-course_id="<?= htmlReady($course_id) ?>"
     href="<?= URLHelper::getLink(isset($url) ? $url : '',
                                  array('study_area_selection[selected]' => $area->getParentId())) ?>">
    <?= htmlReady($area->getPath(' · ')) ?>
  </a>
  <? if($area->isModule()) echo $area->getModuleInfoHTML($semester_id); ?>
  <input type="hidden" name="study_area_selection[areas][]" class="study_area_selection_area" value="<?= $_id ?>">
</li>

