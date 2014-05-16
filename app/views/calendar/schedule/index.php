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
    $actions->addLink(_("Neuer Eintrag"), $controller->url_for('calendar/schedule/entry'), 'icons/16/black/add/date.png');
}
$link = new LinkElement();
$link->label = _("Druckansicht");
$link->url = $controller->url_for('calendar/schedule/index/'. implode(',', $days)
           .  '?printview=true&semester_id=' . $current_semester['semester_id']);
$link->setTarget("_blank");
$link->icon = Assets::image_path("icons/16/black/print.png");
$actions->addElement($link, 'print');
$actions->addLink(_("Darstellung ändern"), $controller->url_for('calendar/schedule/index?show_settings=true'), 'icons/16/black/admin.png');
if (!$show_hidden) {
    $actions->addLink(_("Ausgeblendete Veranstaltungen anzeigen"), $controller->url_for('calendar/schedule/?show_hidden=1'), 'icons/16/black/visibility-visible.png');
} else {
    $actions->addLink(_("Ausgeblendete Veranstaltungen verbergen"), $controller->url_for('calendar/schedule/?show_hidden=0'), 'icons/16/black/visibility-visible.png');
}
$sidebar->addWidget($actions);

$views = new LinksWidget();
$views->setTitle(_("Darstellungsgröße"));
$views->addLink(_("klein"), URLHelper::getURL('', array('zoom' => 0)), 'icons/16/'. ($zoom == 0 ? 'red' : 'black') . '/schedule.png');
$views->addLink(_("mittel"), URLHelper::getURL('', array('zoom' => 1)), 'icons/16/'. ($zoom == 1 ? 'red' : 'black') . '/schedule.png');
$views->addLink(_("groß"), URLHelper::getURL('', array('zoom' => 2)), 'icons/16/'. ($zoom == 2 ? 'red' : 'black') . '/schedule.png');
$sidebar->addWidget($views, 'view');

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
