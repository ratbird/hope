<div id="quickSelectionEdit" title="<?=_('Links konfigurieren') ?>">
    <form id="configure_quickselection" action="#" data-url="<?=PluginEngine::getURL($plugin, array(), 'save') ?>" method="POST" >
        <div style="float:left;width:65%;" id="link_choices" >
            <? foreach ($links as $key=>$nav) : ?>
            <? $name=studip_utf8encode($nav->getTitle()) ?>
            <label>
            <input type="checkbox" name="add_removes[]" id="<?= studip_utf8encode($key); ?>" value="<?= studip_utf8encode($key); ?>" <?= empty($config) || in_array($key, $config) ? 'checked' : ''?>> <?= studip_utf8encode(htmlReady($nav->getTitle()));?>
            </label>
            <br>
            <? endforeach; ?>

        </div>
        <div id="link_description">
            <?= _("W&auml;hlen Sie links einen Link aus, um ihn im Schnellzugriff Widget anzuzeigen.")?>
        </div>
    </form>
</div>
