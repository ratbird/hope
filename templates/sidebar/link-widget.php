<ul class="sidebar-widget-links">
<? foreach ($elements as $element): ?>
    <li><?= $element->render() ?></li>
<? endforeach; ?>
</ul>