<?
# Lifter010: TODO
$sidebar = Sidebar::Get();
$sidebar->setImage(Assets::image_path("sidebar/person-sidebar.png"));
$actions = new ViewsWidget();
$actions->addLink(_('Benutzer verwalten'), $controller->url_for('admin/user'))->setActive($action == 'index');
if (in_array("Standard", $GLOBALS['STUDIP_AUTH_PLUGIN'])) {
    $actions->addLink(_('Neuen Benutzer anlegen'), $controller->url_for('admin/user/new'))->setActive($action == 'new' && empty($args));
}
$actions->addLink(_('Neuen vorläufigen Benutzer anlegen'), $controller->url_for('admin/user/new/prelim'))->setActive($action == 'new' && $args[0] == 'prelim');
$actions->addLink(_('Benutzer zusammenführen'), $controller->url_for('admin/user/migrate'))->setActive($action == 'migrate');
$sidebar->addWidget($actions);


if (count($users) > 0) {
    $export = new ExportWidget();
    $export->addLink(_('Suchergebnis exportieren'), $controller->url_for('admin/user?export=1'), 'icons/16/blue/move_right/persons.png');
    $sidebar->addWidget($export);
}


$search = new SidebarWidget();
$search->setTitle(_('Suche'));
$searchform = '<form id="user_search" action="' . $controller->url_for('admin/user/edit') . '" method="post">'
    . CSRFProtection::tokenTag()
    . QuickSearch::get('user', new StandardSearch('user_id'))
        ->withButton()
        ->fireJSFunctionOnSelect("selectUser")
        ->render()
    . '</form>'
    . '<script>
                var selectUser = function (user_id, name) {
                    document.location = "' . $controller->url_for('admin/user/edit') . '/" + user_id;
                };
              </script>';

$search->addElement(new WidgetElement($searchform));
$sidebar->addWidget($search);
