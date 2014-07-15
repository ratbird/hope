<?
# Lifter010: TODO
$sidebar = Sidebar::Get();
$list    = new SelectWidget(_('Bereichsauswahl'), $controller->url_for('admin/lockrules'), 'lock_rule_type');
foreach (array('sem' => _("Veranstaltung"), 'inst' => _("Einrichtung"), 'user' => _("Nutzer")) as $type => $desc) {
    $list->addElement(new SelectElement($type, $desc, Request::get('lock_rule_type') == $type), 'lock_rule_type-' . $type);
}

$sidebar->addWidget($list);
?>
