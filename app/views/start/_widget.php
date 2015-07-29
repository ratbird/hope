<div class="ui-widget_head widget-header" id="widget-<?= $widget->widget_id ?>">
    <span class="header-options">
        <? if (isset($icons)): ?>
            <? foreach ($icons as $nav): ?>
                <? if ($nav->isVisible(true)): ?>
                    <? $attr = $nav->getImage() ?>
                    <a href="<?= URLHelper::getLink($nav->getURL()) ?>"
                        <? foreach ($attr as $key => $value): ?>
                            <? if ($key !== 'src'): ?>
                                <?= $key ?>="<?= htmlReady($value) ?>"
                            <? endif ?>
                        <? endforeach ?>>
                        <?= Assets::img($attr['src']) ?>
                    </a>
                <? endif ?>
            <?endforeach ?>
        <? endif ?>

        <? if (isset($admin_url)): ?>
            <a href="<?= URLHelper::getLink($admin_url) ?>">
                <?= Assets::img('icons/16/blue/admin.png', tooltip2($admin_title)) ?>
            </a>
        <? endif ?>

        <a href="<?= $controller->url_for('start/delete/' . $widget->widget_id) ?>">
            <?= Assets::img('icons/16/blue/decline.png', tooltip2(_('Entfernen'))) ?>
        </a>
    </span>
    <span id="widgetName<?= $widget->widget_id ?>" class="widget-title">
        <?= htmlReady(isset($title) ? $title : $widget->getPluginName()) ?>
    </span>
</div>
<div id="wid<?=$widget->widget_id?>">
    <?= $content_for_layout ?>
</div>
