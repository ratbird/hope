<form action="<?= URLHelper::getLink($url, array(), true) ?>" method="get">
    <select class="sidebar-selectlist" size="8" name="<?= htmlReady($name) ?>" onKeyDown="if (event.keyCode === 13) { jQuery(this).closest('form')[0].submit(); }" onClick="jQuery(this).closest('form')[0].submit();" size="10" style="max-width: 200px;cursor:pointer" class="text-top" aria-label="<?= _("Wählen Sie ein Objekt aus. Sie gelangen dann zur neuen Seite.") ?>">
    <? foreach ($elements as $element): ?>
        <option value="<?= htmlReady($element->getid()) ?>"><?= htmlReady(my_substr($element->getLabel(), 0, 30)) ?></value>
    <? endforeach; ?>
    </select>
</form>