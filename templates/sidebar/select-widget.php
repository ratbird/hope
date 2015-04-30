<form action="<?= $url ?>" method="<?= $method ?>">
<? foreach ($params as $key => $value): ?>
    <input type="hidden" name="<?= htmlReady($key) ?>" value="<?= htmlReady($value) ?>">
<? endforeach; ?>
    <select class="sidebar-selectlist" name="<?= htmlReady($name) ?>" <? if ($size) printf('size="%u"', $size); ?> <?= $attributes ?>>
    <? foreach ($elements as $element): ?>
        <option value="<?= htmlReady($element->getid()) ?>" <? if ($element->isActive()) echo 'selected'; ?>>
            <? $label = $element->getLabel() ?>
            <? if ($label[0] === " ") {
                for ($prefix = 0; $prefix < strlen($label); $prefix++) {
                    if ($label[$prefix + 1] !== " ") {
                        echo "&nbsp;";
                        break;
                    }
                }
                $prefix++;
                $label = substr($label, $prefix);
            } ?>
            <?= htmlReady(my_substr($label, 0, 30)) ?>
        </option>
    <? endforeach; ?>
    </select>
    <noscript>
        <?= Studip\Button::create(_('Zuweisen')) ?>
    </noscript>
</form>
