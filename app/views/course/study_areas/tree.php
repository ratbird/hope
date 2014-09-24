<?
# Lifter010: TODO
?>
<input type="hidden" name="study_area_selection[last_selected]" value="<?= htmlReady($selection->getSelected()->getID()) ?>">

<?
  TextHelper::reset_cycle();
  $trail = $selection->getTrail();
  $last = end($trail);
?>

<? foreach ($trail as $id => $area) : ?>
  <ul>
    <li class="trail_element">
      <?= $this->render_partial('course/study_areas/entry', array('area' => $area, 'show_link' => $area !== $last)) ?>

      <? if ($area === $last) : ?>
        <?= Assets::input('sem_tree.gif',
                          tooltip2(_('Alle Unterebenen einblenden')) +
                          array('name' => 'study_area_selection[showall_button]')) ?>
      <? endif ?>
<? endforeach ?>

<?= $this->render_partial("course/study_areas/subtree",
                          array("subtree" => $selection->getSelected())); ?>

<? foreach ($trail as $id => $area) : ?>
    </li>
  </ul>
<? endforeach ?>

