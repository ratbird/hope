<ul class="sidebar-widget-links">
<? foreach ($elements as $index => $element): ?>
    <li data-element_id="<?= htmlReady($index) ?>"><?= $element->render() ?></li>
<? endforeach; ?>
</ul>