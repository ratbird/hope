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
                <?= $widget->render(array('base_class' => 'helpbar'))?>
                <div class="helpbar-widget-admin-icons">
                <? if ($widget->edit_link): ?>
                    <a href="<?=$widget->edit_link?>" data-dialog="size=auto;reload-on-close">
                    <?= Assets::img('icons/16/white/edit.png') ?></a>
                <? endif; ?>
                <? if ($widget->delete_link): ?>
                    <a href="<?=$widget->delete_link?>" data-dialog="size=auto;reload-on-close">
                    <?= Assets::img('icons/16/white/trash.png') ?></a>
                <? endif; ?>
                <? if ($widget->add_link): ?>
                    <a href="<?=$widget->add_link?>" data-dialog="size=auto;reload-on-close">
                    <?= Assets::img('icons/16/white/add.png') ?></a>
                <? endif; ?>
                </div>
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
