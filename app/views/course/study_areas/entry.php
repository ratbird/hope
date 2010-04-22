<?
$id = htmlReady($area->getID());
$name = isset($show_path)
        ? htmlReady($area->getPath(' · '))
        : htmlReady($area->getName());
$expand_id = $area->hasChildren() ? $id : $area->getParentId();
?>
<input class="study_area_selection_add_<?= $id ?> {id: '<?= $id ?>', course_id: '<?= htmlReady($course_id) ?>'}"
        type="image"
        name="study_area_selection[add][<?= $id ?>]"
        src="<?= Assets::image_path('move_left.gif') ?>"
        title="<?= _("Diesen Studienbereich zuordnen") ?>"
        alt="<?= _("Diesen Studienbereich zuordnen") ?>"
        <?= !$area->isAssignable() || $selection->includes($area)
            ? 'style="visibility:hidden;"' : '' ?>>
<? if (isset($show_link) && $show_link) : ?>
  <a class="study_area_selection_expand {id: '<?= htmlReady($expand_id) ?>', course_id: '<?= htmlReady($course_id) ?>'}"
     href="<?= URLHelper::getLink(isset($url) ? $url : '',
                   array('study_area_selection[selected]' => htmlReady($expand_id))) ?>">
    <?= $name ?>
  </a>
<? else : ?>
  <?= $name ?>
<? endif ?>
<? if($area->isModule()) echo $area->getModuleInfoHTML($semester_id); ?>
