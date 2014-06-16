<?php
# Lifter010: TODO
$zoom = $my_schedule_settings['zoom'];

$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/schedule-sidebar.png"));

$semester_widget = new SidebarWidget();
$semester_widget->setTitle(_('Angezeigtes Semester'));
$semester_widget->addElement(new WidgetElement($this->render_partial('calendar/schedule/_semester_chooser')), 'semester');
$sidebar->addWidget($semester_widget, 'semester');

$actions = new ActionsWidget();
if (!$inst_mode) {
    $actions->addLink(_("Neuer Eintrag"), $controller->url_for('calendar/schedule/entry'), 'icons/16/blue/add/date.png');
}

$actions->addLink(_("Darstellung ändern"), $controller->url_for('calendar/schedule/index?show_settings=true'), 'icons/16/blue/admin.png');
if (!$show_hidden) {
    $actions->addLink(_("Ausgeblendete Veranstaltungen anzeigen"), $controller->url_for('calendar/schedule/?show_hidden=1'), 'icons/16/blue/visibility-visible.png');
} else {
    $actions->addLink(_("Ausgeblendete Veranstaltungen verbergen"), $controller->url_for('calendar/schedule/?show_hidden=0'), 'icons/16/blue/visibility-visible.png');
}
$sidebar->addWidget($actions);

$widget = new ExportWidget();
$widget->addLink(_('Druckansicht'),
                 $controller->url_for('calendar/schedule/index/'. implode(',', $days) .  '?printview=true&semester_id=' . $current_semester['semester_id']),
                 'icons/16/blue/print.png',
                 array('target' => '_blank'));
$sidebar->addWidget($widget);

$options = new OptionsWidget();
$options->setTitle(_("Darstellungsgröße"));
$options->addRadioButton(_("klein"), URLHelper::getURL('', array('zoom' => 0)), $zoom == 0);
$options->addRadioButton(_("mittel"), URLHelper::getURL('', array('zoom' => 1)), $zoom == 1);
$options->addRadioButton(_("groß"), URLHelper::getURL('', array('zoom' => 2)), $zoom == 2);
$sidebar->addWidget($options, 'options');

?>
<div style="text-align: center; font-weight: bold; font-size: 1.2em">
    <? if($inst_mode) : ?>
    <?= $institute_name  ?>: <?= _('Stundenplan im') ?>
    <? else : ?>
    <?= _('Mein Stundenplan im') ?>
    <? endif ?>
    <?= $current_semester['name'] ?>
</div>
<? if (Request::get('show_settings')) : ?>
    <?= $this->render_partial('calendar/schedule/settings', array('settings' => $my_schedule_settings)) ?>
<? endif ?>

<?= $calendar_view->render(array('show_hidden' => $show_hidden)) ?>
<?= $this->render_partial('calendar/schedule/_entry.php'); ?>
<?= $this->render_partial('calendar/schedule/_entry_details') ?>
