<div class="helpbar-container">
    <input type="checkbox" id="helpbar-sticky" <? if ($open) echo 'checked'; ?>>
    <div class="helpbar">
        <h2 class="helpbar-title">
            <label for="helpbar-sticky">
                <?= _('Tipps & Hilfe') ?>
            </label>
        </h2>
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
<? if ($tour_data['active_tour_id']) : ?>
    <script>
        STUDIP.Tour.init('<?=$tour_data['active_tour_id']?>', '<?=$tour_data['active_tour_step_nr']?>')
    </script>
<? endif ?>
