<h1 class="sr-only">
    <? if ($GLOBALS['perm']->have_perm('root')) :?>
        <?= _("Startseite für Root bei Stud.IP")?>
    <? elseif ($GLOBALS['perm']->have_perm('admin')) : ?>
        <?= _("Startseite für AdministratorInnen bei Stud.IP")?>
    <? elseif ($GLOBALS['perm']->have_perm('dozent')) :?>
        <?= _("Startseite für DozentInnen bei Stud.IP")?>
    <? else : ?>
        <?= _("Ihre persönliche Startseite bei Stud.IP")?>
    <? endif ?>
</h1>

<?php
// display a random banner if the module is enabled
if (get_config('BANNER_ADS_ENABLE')) {
    echo Banner::getRandomBanner()->toHTML();
}
?>

<?= $this->render_partial("start/_addclip", array('widgets' => $suitable_widgets)) ?>

<div style="clear:both;"> </div>
<?= $this->render_partial("start/_feedback", array('flash' => $flash)) ?>

<div class="ui-widgetContainer start-widgetcontainer">

    <div  class="ui-widget_columnl" id="0" >
        <ul id="sort0" style="list-style-type: none;margin-top:5px;">
            <? foreach($left as $widget) : ?>
                <li class="studip-widget-wrapper" >
                    <div class="ui-widget-content studip-widget" id="<?= $widget->widget_id ?>">
                        <?=$this->render_partial("start/_widget", array('widget' => $widget))?>
                    </div>
                 </li>
            <? endforeach ; ?>
        </ul>
    </div>
    <br style="clear: both;" />
</div>
