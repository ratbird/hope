<?php if (!$search_result || in_array($node->id, $search_result)) : ?>
<li class="sem-tree-<?= htmlReady($node->id) ?> keep-node" data-id="<?= $node->id ?>">
    <?php if ($node->isAssignable()) : ?>
    <?= Assets::input('icons/yellow/arr_2left.svg',
            array('name' => 'assign['.$node->id.']',
            'onclick' => "return STUDIP.CourseWizard.assignNode('".$node->id."')",
            'class' => in_array($node->id, $values['studyareas'] ?: array()) ? 'hidden-no-js' : '')) ?>
    <?php endif ?>
    <?php if ($node->hasChildren()) : ?>
    <input type="checkbox" id="<?= htmlReady($node->id) ?>"<?= (in_array($node->id, $open_nodes) && $node->parent_id != $values['open_node']) ? ' checked="checked"' : '' ?>/>
    <label for="<?= htmlReady($node->id) ?>">
        <a href="<?= URLHelper::getLink($no_js_url,
            array('open_node' => $node->id)) ?>"
           onclick="return STUDIP.CourseWizard.getTreeChildren('<?= htmlReady($node->sem_tree_id) ?>', true)">
    <?php endif ?>
        <?= htmlReady($node->name) ?>
    <?php if ($node->hasChildren()) : ?>
        </a>
    </label>
    <ul>
        <?php if ($node->hasChildren() && in_array($node->id, $open_nodes) && $node->_parent->id != $values['open_node']) : ?>
            <?php foreach ($node->getChildren() as $child) : ?>
                <?= $this->render_partial('studyareas/_node',
                    array('node' => $child, 'stepnumber' => $stepnumber,
                        'temp_id' => $temp_id, 'values' => $values,
                        'open_nodes' => $open_nodes ?: array(),
                        'search_result' => $search_result ?: array())) ?>
            <?php endforeach ?>
        <?php endif ?>
    </ul>
    <?php endif ?>
</li>
<?php endif ?>
