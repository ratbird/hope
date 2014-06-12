<form action="<?= $url ?>" method="<?= $method ?>" <? if (isset($id)) printf('id="%s"', htmlReady($id)); ?> class="sidebar-search">
<? foreach ($url_params as $key => $value): ?>
    <input type="hidden" name="<?= htmlReady($key) ?>" value="<?= htmlReady($value) ?>">
<? endforeach; ?>
    <ul class="needles">
    <? foreach ($needles as $needle): ?>
        <li>
            <label for="needle-<?= $hash = md5($url . '|' . $needle['name']) ?>" <? if ($needle['placeholder']) echo 'style="display:none;"'; ?>>
                <?= htmlReady($needle['label']) ?>
            </label>
            <input type="search" id="needle-<?= $hash ?>"
                   name="<?= htmlReady($needle['name']) ?>"
                   value="<?= htmlReady($needle['value']) ?>"
                   <? if ($needle['placeholder']) printf('placeholder="%s"', htmlReady($needle['label'])); ?>>
            <input type="submit" value="<?= _('Suchen') ?>">
        </li>
    <? endforeach; ?>
    </ul>
<? if (!empty($filters)): ?>
    <ul class="filters">
    <? foreach ($filters as $key => $label): ?>
        <label>
            <input type="checkbox" name="<?= htmlReady($key) ?>" value="1" <? if (!$has_data || Request::int($key)) echo 'checked'; ?>>
            <?= htmlReady($label) ?>
        </label>
    <? endforeach; ?>
    </ul>
<? endif; ?>
</form>
