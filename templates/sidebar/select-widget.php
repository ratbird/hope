<form action="<?= $url ?>" method="<?= $method ?>">
<? foreach ($params as $key => $value): ?>
    <input type="hidden" name="<?= htmlReady($key) ?>" value="<?= htmlReady($value) ?>">
<? endforeach; ?>
    <select class="sidebar-selectlist" name="<?= htmlReady($name) ?>" <? if ($size) printf('size="%u"', $size); ?> <?= $attributes ?>>
    <? foreach ($elements as $element): ?>
        <option value="<?= htmlReady($element->getid()) ?>" <? if ($element->isActive()) echo 'selected'; ?> style="text-indent: <?= $element->getIndentLevel() ?>ex;">
            <?= htmlReady(my_substr($element->getLabel(), 0, 30)) ?>
        </option>
    <? endforeach; ?>
    </select>
    <noscript>
        <?= Studip\Button::create(_('Zuweisen')) ?>
    </noscript>
</form>
