<ul class="<?= implode(' ', $css_classes) ?>">
<? foreach ($elements as $index => $element): ?>
    <li<?= $element->icon ? ' style="' . Icon::create($element->icon)->render(Icon::CSS_BACKGROUND) .'"' : "" ?><?= $element->active ? ' class="active"' : '' ?> id="<?= htmlReady($index) ?>">
        <?= $element->render() ?>
    </li>
<? endforeach; ?>
</ul>