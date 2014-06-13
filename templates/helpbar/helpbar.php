<div class="helpbar-container">
    <input type="checkbox" id="helpbar-sticky">
    <div class="helpbar">
        <h2 class="helpbar-title">
            <label for="helpbar-sticky">
                <?= _('Tipps & Hilfe') ?>
            </label>
        </h2>
        <ul class="helpbar-widgets">
        <? foreach ($widgets as $index => $widget): ?>
            <li>
            <? if (false && $widget->icon): ?>
                <?= Assets::img($widget->icon, array('class' => 'helpbar-widget-icon')) ?>
            <? endif; ?>
                <?= $widget->render(array('base_class' => 'helpbar')) ?>
            </li>
        <? endforeach; ?>
        </ul>
    </div>
</div>
