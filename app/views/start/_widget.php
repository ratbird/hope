<div class="ui-widget_head widget-header">
    <span id="widgetName<?=$widget->widget_id?>"><?= $widget->getPluginName() ;?></span>
    <span class="header-options">
        <? if(method_exists($widget,'getHeaderOptions')) : ?>
            <? $options = $widget->getHeaderOptions()?>

            <?foreach($options as $option) : ?>
        <? if(array_key_exists('id', $option)): ?>  <div id="<?=$option['id']?>"> </div> <? endif; ?><a href="<?= $option['url'] ?>" <? if(array_key_exists('onclick', $option)): ?> onclick="<?= $option['onclick'] ?>" <? endif; ?> <? if(array_key_exists('rel', $option)): ?> rel="<?= $option['rel'] ?>" <? endif; ?> >
                    <img src="<?= Assets::image_path($option['img']) ?>">
                </a>
            <?endforeach; ?>
        <? endif; ?>

        <a href="<?= $controller->url_for('start/index/delete/'.$widget->widget_id) ?>">
            <img src="<?= Assets::image_path('icons/16/blue/decline.png') ?>"
            <?= tooltip(_("Widget entfernen")) ?>>
        </a>
    </span>
</div>
<div style="clear:both" id="wid<?=$widget->widget_id?>">

<? if (($template = $widget->getPortalTemplate()) instanceof Flexi_Template) : ?>
    <?= $template->render() ?>
<? elseif (is_string($template)) : ?>
    <?= $template ?>
<? endif ?>
</div>
