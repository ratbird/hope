<ul class="<?= implode(' ', $css_classes) ?>">
<? foreach ($elements as $element): ?>
    <li<?= $element->icon ? ' style="' . Icon::create($element->icon)->render(Icon::CSS_BACKGROUND) .'"' : "" ?><?= $element->active ? ' class="active"' : '' ?>>
        <?= $element->render() ?>
    </li>
<? endforeach; ?>
</ul>