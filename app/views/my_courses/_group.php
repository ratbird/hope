<? $index = 1?>
<? foreach ($course_group as $title => $course_collection) : ?>
  <? foreach(array_values($course_collection) as $course) {
        $last_modified = $course['last_modified'];
    }?>
    <tbody class="collapsed">
    <tr class="table_header header-row">
        <th class="toggle-indicator" style="white-space: nowrap; text-align: left" colspan="3">
            <a class="toggler">
                <? if (strcmp($group_field, 'sem_tree_id') === 0 && strcmp($title, '') === 0) : ?>
                    <? $title = "keine Zuordnung"; ?>
                <? endif ?>

                <? if (strcmp($this->group_field, 'gruppe') === 0) : ?>
                    <? $title = _('Gruppe') . " " . $index ?>
                <? endif ?>

                <?= htmlReady($title) ?></a>
        </th>
        <th colspan="3">
            <? if($last_modified) : ?>
                <?= tooltipIcon(_('Letzte Änderung: ') . strftime('%x, %H:%M', $last_modified), true) ?>
            <? endif?>
        </th>
    </tr>
    <?= $this->render_partial("my_courses/_course", compact('course_collection')) ?>
    </tbody>
    <? $index++?>
<? endforeach ?>

