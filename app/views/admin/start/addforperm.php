<form id="edit_startpage" action="<?= $controller->url_for('admin/start/addchoices') ?>" method="POST">
    <div style="float:left;width:45%;" id="widget_choices">
    <input type='hidden' name='context' value="<?=$context?>"></input>
    <? foreach($all_widgets as $widget) :?>
        <input type="checkbox" name="widgets[]" id="<?=$widget->getPluginId(); ?>" value="<?=$widget->getPluginId(); ?>" checked="checked" data-description="<?= $widget->getPluginName();?>" onclick="$('#widget_description').html($(this).data('description'));"> <?= $widget->getPluginName();?><br>
    <? endforeach; ?>

    </div>
    <div id="widget_description">
        <?= _("Wählen Sie links ein Widget aus, um sich hier weitere Informationen anzeigen zu lassen.")?>
    </div>
</form>
