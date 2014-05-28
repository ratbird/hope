<div class="helpbar-container">
    <div class="helpbar">
        <div class="helpbar-header">
            <?= Assets::img('icons/24/white/question-circle.png') ?>
        </div>
        <div class="helpbar-content">
            <h2 class="helpbar-title"><?= _('Tipps & Hilfe') ?></h2>
            <ul class="helpbar-widgets">
            <? foreach ($widgets as $index => $widget): ?>
                <li>
                <? if ($widget->icon): ?>
                    <?= Assets::img($widget->icon, array('class' => 'helpbar-widget-icon')) ?>
                <? endif; ?>
                    <?= $widget->render(array('base_class' => 'helpbar')) ?>
                </li>
            <? endforeach; ?>
            </ul>
        </div>
    </div>
</div>
