<? use Studip\Button, Studip\LinkButton; ?>


<?if(! is_array($choices)) : ?>
    <?= MessageBox::info(_('Für die gewählte Rechtestufe stehen keine Widgets zu Verfügung.'));?>
<? endif; ?>



<h2><?=_("Startseitenverwaltung")?></h2>


<form action="<?= $controller->url_for('admin/start') ?>" method="POST">
    <span style="float:right;">
        <select id="selected_perm" name="selected_perm" onchange="this.form.submit();">
            <? foreach($perms as $perm_name) :?>
                <option value="<?=$perm_name?>" <?= ($selected_perm == $perm_name ? 'selected ="selected"' : ''); ?> ><?=$perm_name?></option>
            <? endforeach; ?>
        </select>
        <noscript> <?= Button::create(_('Auswählen')) ?></noscript>
    </span>
    <br style="clear:both">
    <?if(is_array($choices)) : ?>
    <div id="admin_widget_container">
        <div class="ui-widget_columnl" id="main">
            <ul class="droparea ui-sortable start-admin" id="sort0" style="list-style-type: none;margin-top:5px;">
                <? foreach($left as $choice) :?>
                    <div class="studip-widget-wrapper" id="<?= $choice->getPluginId() ?>" data-instance="1">
                        <li class="ui-widget-content studip-widget" style="width: 100%;">
                            <?=$this->render_partial("admin/start/_widget", array('widget' => $choice))?>
                        </li>
                    </div>
                <? endforeach ; ?>
                <div style="clear:both"></div>
            </ul>
        </div>
        <br style="clear: both;" />
        <br>
        <div id='choices'>
            <ul class="droparea ui-sortable start-admin" id="sort3" style="list-style-type: none;margin-top:5px;">
                <? foreach($choices as $choice) :?>
                    <div class="studip-widget-wrapper" id="<?= $choice->getPluginId() ?>" data-instance="1" style="width:20%;float:left;padding:0 5px;">
                        <li class="ui-widget-content studip-widget" style="width: 100%;">
                            <?=$this->render_partial("admin/start/_widget", array('widget' => $choice))?>
                        </li>
                    </div>
                <? endforeach ; ?>
                <div style="clear:both"></div>
            </ul>

        </div>
        <br style="clear: both;" />
    </div>
    <? endif; ?>
</form>
