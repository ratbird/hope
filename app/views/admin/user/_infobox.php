<?
# Lifter010: TODO
$aktionen[] = array(
    "text" => '<a href="'.$controller->url_for('admin/user').'">'._('Benutzer verwalten').'</a>',
    "icon" => "icons/16/black/persons.png"
);
if(in_array("Standard", $GLOBALS['STUDIP_AUTH_PLUGIN'])) {
    $aktionen[] = array(
        "text" => '<a href="'.$controller->url_for('admin/user/new').'">'._('Neuen Benutzer anlegen').'</a>',
        "icon" => "icons/16/black/add/person.png"
    );
}
$aktionen[] = array(
    "text" => '<a href="'.$controller->url_for('admin/user/migrate').'">'._('Benutzer zusammenführen').'</a>',
    "icon" => "icons/16/black/move_right/persons.png"
);
if (count($users) > 0) {
    $aktionen[] = array(
    "text" => '<a href="'.$controller->url_for('admin/user?export=1').'">'._('Suchergebnis exportieren').'</a>',
    "icon" => "icons/16/black/file-xls.png"
);
}

$searchform = '<form id="user_search" action="'.$controller->url_for('admin/user/edit').'" method="post">'
            . CSRFProtection::tokenTag()
            . QuickSearch::get('user', new StandardSearch('user_id'))
                ->withButton(array('width' => 200))
                ->fireJSFunctionOnSelect("selectUser")
                ->render()
            . '</form>'
            . '<script>
                var selectUser = function (user_id, name) {
                    document.location = "'.$controller->url_for('admin/user/edit').'/" + user_id;
                };
              </script>';


$aktionen[] = array(
    "text" => $searchform,
    "icon" => "icons/16/black/search.png"
);
