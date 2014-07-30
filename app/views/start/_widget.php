<div class="ui-widget_head widget-header" id="widget-<?= $widget->widget_id ?>">
    <span id="widgetName<?= $widget->widget_id ?>">
        <?= htmlReady($widget->getPluginName()) ?>
    </span>
    <span class="header-options">
    <? if (method_exists($widget, 'getHeaderOptions')): ?>
        <? $options = $widget->getHeaderOptions()?>

        <? foreach ($options as $option): ?>
            <? if (isset($option['id'])): ?> 
                <div id="<?= $option['id'] ?>"></div>
            <? endif; ?>
                <a href="<?= $option['url'] ?>" <? if (isset($option['onclick'])): ?> onclick="<?= $option['onclick'] ?>" <? endif; ?> <? if (isset($option['rel'])): ?> rel="<?= $option['rel'] ?>" <? endif; ?> <? if (isset($option['data-dialog'])): ?> data-dialog="<?= $option['data-dialog'] ?>" <? endif; ?>>
                    <?= Assets::img($option['img']) ?>
                </a>
            <?endforeach; ?>
        <? endif; ?>

        <a href="<?= $controller->url_for('start/delete/' . $widget->widget_id) ?>">
            <?= Assets::img('icons/16/blue/decline.png', tooltip2(_('Widget entfernen'))) ?>
        </a>
    </span>
</div>
<div id="wid<?=$widget->widget_id?>">
<? if (($template = $widget->getPortalTemplate()) instanceof Flexi_Template): ?>
    <?= $template->render() ?>
<? elseif (is_string($template)): ?>
    <?= $template ?>
<? endif; ?>
</div>
