<div class="addclip-container">
    <input type="checkbox" id="addclip-sticky">
    <div class="addclip">
        <h2 class="addclip-title">
            <label id="addclip-sticky-label" for="addclip-sticky">
                <?=_("Widgets zur Startseite hinzufügen")?>
            </label>
        </h2>
        <ul class="addclip-widgets">
        <? foreach($widgets as $widget) :?>
            <? $metadata = $widget->getMetadata(); ?>
            <a href="<?= $controller->url_for('start/add_widget/'.$widget->getPluginId().'/0') ?>"
                <?= tooltip($metadata['description']) ?>>
                <li><?= $widget->getPluginName();?></li>
            </a>
        <? endforeach; ?>
        </ul>
    </div>
</div>

