<form action="<?= $url ?>" method="get">
<? foreach ($params as $key => $value): ?>
    <input type="hidden" name="<?= htmlReady($key) ?>" value="<?= htmlReady($value) ?>">
<? endforeach; ?>
    <select class="sidebar-selectlist" <? if ($size) printf('size="%u"', $size); ?> name="<?= htmlReady($name) ?>" onchange="$(this).closest('form').submit();">
    <? foreach ($elements as $element): ?>
        <option value="<?= htmlReady($element->getid()) ?>" <? if ($element->isActive()) echo 'selected'; ?>>
            <?= htmlReady(my_substr($element->getLabel(), 0, 30)) ?>
        </option>
    <? endforeach; ?>
    </select>
    <noscript>
        <?= Studip\Button::create(_('Zuweisen')) ?>
    </noscript>
</form>
