<?
# Lifter010: TODO
$id = htmlReady($area->getID());
$name = isset($show_path)
        ? htmlReady($area->getPath(' · '))
        : htmlReady($area->getName());
$expand_id = $area->hasChildren() ? $id : $area->getParentId();
?>
<?= Assets::input("icons/16/yellow/arr_2left.png", array('type' => "image", 'class' => "study_area_selection_add_".$id, 'data-id' => $id, 'data-course_id' => htmlReady($course_id), 'name' => "study_area_selection[add][".$id."]", 'style' => !$area->isAssignable() || $selection->includes($area)? "visibility:hidden;" : "", 'title' => _('Diesen Studienbereich zuordnen'))) ?>
<? if (isset($show_link) && $show_link) : ?>
  <a class="study_area_selection_expand"
     data-id="<?= htmlReady($expand_id) ?>" data-course_id="<?= htmlReady($course_id) ?>"
     href="<?= URLHelper::getLink(isset($url) ? $url : '',
                   array('study_area_selection[selected]' => htmlReady($expand_id))) ?>">
    <?= $name ?>
  </a>
<? else : ?>
  <?= $name ?>
<? endif ?>
<? if($area->isModule()) echo $area->getModuleInfoHTML($semester_id); ?>
