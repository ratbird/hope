<div id="quickSelectionEdit">
    <form id="configure_quickselection" action="#" data-url="<?=PluginEngine::getURL($plugin, array(), 'save') ?>" method="POST">
        <div style="float:left; width:65%;" id="link_choices">
            <? foreach ($links as $key=>$nav) : ?>
                <label>
                    <input type="checkbox" name="add_removes[]" value="<?= htmlReady($key) ?>" <?= empty($config) || in_array($key, $config) ? 'checked' : ''?>>
                    <?= htmlReady($nav->getTitle()) ?>
                </label>
                <br>
            <? endforeach ?>
        </div>
        <div id="link_description">
            <?= _("Wählen Sie links einen Link aus, um ihn im Schnellzugriff-Widget anzuzeigen.") ?>
        </div>
    </form>
</div>
