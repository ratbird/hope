<li class="sem-tree-assigned-<?= $element['id'] ?>">
    <?= htmlReady($element['name']) ?>
    <?php if ($element['assignable'] && in_array($element['id'], $studyareas ?: array())) : ?>
        <?= Assets::input('icons/blue/trash.svg',
            array('name' => 'unassign['.$element['id'].']',
                'onclick' => "return STUDIP.CourseWizard.unassignNode('".$element['id']."')")) ?>
    <input type="hidden" name="studyareas[]" value="<?= $element['id'] ?>"/>
    <?php endif ?>
    <ul>
        <?php foreach ($element['children'] as $c) : ?>
        <?= $this->render_partial('studyareas/_assigned_node', array('element' => $c)) ?>
        <?php endforeach ?>
    </ul>
</li>