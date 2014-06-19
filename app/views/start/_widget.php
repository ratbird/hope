<div class="ui-widget_head widget-header">
    <span id="widgetName<?=$widget->widget_id?>"><?= $widget->getPluginName() ;?></span>
    <span class="header-options">
        <? if(method_exists($widget,'getHeaderOptions')) : ?>
            <? $options = $widget->getHeaderOptions()?>

            <?foreach($options as $option) : ?>
        <? if(array_key_exists('id', $option)): ?>  <div id="<?=$option['id']?>"> </div> <? endif; ?><a href="<?= $option['url'] ?>" <? if(array_key_exists('onclick', $option)): ?> onclick="<?= $option['onclick'] ?>" <? endif; ?> >
                    <img src="<?= Assets::image_path($option['img']) ?>">

                </a>
            <?endforeach; ?>
        <? endif; ?>

     <!--
        <a rel="togglewidget" href="#" data-wid="<?=$widget->widget_id?>">
             <img src="<?= Assets::image_path('icons/16/blue/checkbox-unchecked.png') ?>"
            <?= tooltip(_("Widget minimieren")) ?>>
        </a> -->
        <a href="<?= $controller->url_for('start/delete/'.$widget->widget_id) ?>">
            <img src="<?= Assets::image_path('icons/16/blue/decline.png') ?>"
            <?= tooltip(_("Widget entfernen")) ?>>
        </a>
    </span>
</div>
<div style="clear:both" id="wid<?=$widget->widget_id?>">
<? if ($template = $widget->getPortalTemplate()) : ?>
    <?= $template->render() ?>
<? endif ?>
</div>
