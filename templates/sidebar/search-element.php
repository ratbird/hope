<form action="<?= $url ?>" method="<?= $method ?>" id="<?= $id ?>" class="sidebar-search">
<? foreach ($params as $key => $value): ?>
    <input type="hidden" name="<?= htmlReady($key) ?>" value="<?= htmlReady($value) ?>">
<? endforeach; ?>
    <input type="search" name="<?= htmlReady($needle_name) ?>" value="<?= htmlReady($needle) ?>" placeholder="<?= htmLReady($placeholder) ?>">
    <input type="image" src="<?= Assets::image_path('icons/16/black/search.png') ?>" title="<?= htmlReady($title) ?>">
<? if (!empty($filters)): ?>
    <ul class="filters">
    <? foreach ($filters as $key => $label): ?>
        <label>
            <input type="checkbox" name="<?= htmlReady($key) ?>" value="1" <? if (!Request::get('needle_name') || Request::int($key)) echo 'checked'; ?>>
            <?= htmlReady($label) ?>
        </label>
    <? endforeach; ?>
    </ul>
<? endif; ?>
</form>
