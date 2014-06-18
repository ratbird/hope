<li>
    <label for='<?= htmlReady($node->id) ?>'><?= htmlReady($node->name) ?></label>
    <input id='<?= htmlReady($node->id) ?>' type='checkbox' <?= $open ? 'checked' : ''?>>
    <? if($node->required_children): ?>
    <ul>
        <? foreach($node->required_children as $child): ?>
        <?= $this->render_partial('study_area/tree.php', array('node' => $child, 'open' => $open)) ?>
        <? endforeach; ?>
    </ul>
    <? endif; ?>
</li>