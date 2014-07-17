<div id="quickSelectionEdit">
    <form id="configure_quickselection" action="#" data-url="<?=PluginEngine::getURL($plugin, array(), 'save') ?>" method="POST" class="studip_form">
        <fieldset>
            <legend><?= _("Inhalte des Schnellzugriff-Widget:") ?></legend>
            <fieldset>
            <? foreach ($links as $key=>$nav) : ?>
                <label>
                    <input type="checkbox" name="add_removes[]" value="<?= htmlReady($key) ?>" <?= empty($config) || in_array($key, $config) ? 'checked' : ''?>>
                    <?= htmlReady($nav->getTitle()) ?>
                </label>
            <? endforeach ?>
            </fieldset>
        </fieldset>
    </form>
</div>
