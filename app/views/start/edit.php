<form id="edit_startpage" action="<?= $controller->url_for('start/update') ?>" method="POST">
    <div style="float:left;width:45%;" id="widget_choices">

    <? foreach($all_widgets as $widget) :?>
    <? $metadata = $widget->getMetadata(); ?>
        <input type="radio" name="widgets[]" id="<?=$widget->getPluginId(); ?>" value="<?=$widget->getPluginId(); ?>" checked="checked" data-description="<?= $widget->getPluginName();?><br>" data-preview="<?=$metadata['description'];?>" data-click=""> <?= $widget->getPluginName();?><br>
    <? endforeach; ?>

    </div>
    <div id="widget_description">
        <?= _("Wählen Sie links ein Widget aus, um sich hier weitere Informationen anzeigen zu lassen.")?>
    </div>
    <input type="hidden" name="side" value="<?=$side?>">
</form>

