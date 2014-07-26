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

<? if ($flash['question']): ?>
    <?= $flash['question'] ?>
<? endif; ?>

<div class="start-widgetcontainer">
    <ul class="portal-widget-list">
        <? foreach ($left as $widget) : ?>
            <li class="studip-widget-wrapper" id="<?= $widget->widget_id ?>">
                <div class="ui-widget-content studip-widget">
                    <?= $this->render_partial('start/_widget', compact('widget')) ?>
                </div>
            </li>
        <? endforeach; ?>
    </ul>
    <ul class="portal-widget-list">
        <? foreach ($right as $widget) : ?>
            <li class="studip-widget-wrapper" id="<?= $widget->widget_id ?>">
                <div class="ui-widget-content studip-widget">
                    <?= $this->render_partial('start/_widget', compact('widget')) ?>
                </div>
            </li>
        <? endforeach; ?>
    </ul>
</div>
