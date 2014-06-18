<li>
<? if($node->required_children): ?>
    <input id='<?= htmlReady($node->id) ?>' type='checkbox' <?= $open && !in_array($layer, $dont_open) ? 'checked' : ''?>>
<? endif; ?>
    <label for='<?= htmlReady($node->id) ?>'><?= htmlReady($node->name) ?></label>
    <? if($node->required_children): ?>
    <ul>
        <? foreach($node->required_children as $child): ?>
        <?= $this->render_partial('study_area/tree.php', array('node' => $child, 'open' => $open, 'layer' => $layer + 1)) ?>
        <? endforeach; ?>
    </ul>
    <? endif; ?>
</li>