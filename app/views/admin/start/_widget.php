<div class="ui-widget_head" style="width:100%;">
    <?= $widget->getPluginName() ;?>
</div>
<div style="clear:both" id="wid<?=$widget->getPluginId()?>">
<?//= $widget->getPortalTemplate()->render() ?>
<div style="padding:5px;">
    <div style="magin:5px;">
        <? $metadata = $widget->getMetadata();?>
        <?= ($metadata['description'] == '')  ? _("Es wurde kein Beschreibungstext f�r dieses Widget angegeben."): $metadata['description'];?>
    </div>
</div>
</div>
