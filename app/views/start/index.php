<h1 class="sr-only">
    <? if ($GLOBALS['perm']->have_perm('root')) :?>
        <?= _("Startseite f�r Root bei Stud.IP")?>
    <? elseif ($GLOBALS['perm']->have_perm('admin')) : ?>
        <?= _("Startseite f�r AdministratorInnen bei Stud.IP")?>
    <? elseif ($GLOBALS['perm']->have_perm('dozent')) :?>
        <?= _("Startseite f�r Lehrende")?>
    <? else : ?>
        <?= _("Ihre pers�nliche Startseite")?>
    <? endif ?>
</h1>

<?php
// display a random banner if the module is enabled
if (get_config('BANNER_ADS_ENABLE')) {
    echo Banner::getRandomBanner()->toHTML();
}
?>

<? if ($flash['question']): ?>
    <?= $flash['question'] ?>
<? endif; ?>

<div class="start-widgetcontainer">
    <? foreach (array($left, $right) as $column): ?>
        <ul class="portal-widget-list">
            <? foreach ($column as $widget): ?>
                <li class="studip-widget-wrapper" id="<?= $widget->widget_id ?>">
                    <div class="ui-widget-content studip-widget">
                        <? if ($template = $widget->getPortalTemplate()): ?>
                            <? $template->set_layout($this->_factory->open('start/_widget')) ?>
                            <?= $this->render_partial($template, compact('widget')) ?>
                        <? else: ?>
                            <?= $this->render_partial('start/_widget', compact('widget')) ?>
                        <? endif ?>
                    </div>
                </li>
            <? endforeach ?>
        </ul>
    <? endforeach ?>
</div>
