<? use Studip\Button, Studip\LinkButton; ?>
<?
if ($inst_id != '' && $inst_id != '0') {
    // add skip links
    SkipLinks::addIndex(_("Mitarbeiterliste"), 'list_institute_members');
    ?>
    <table class="default" id="list_institute_members" border="0" width="99%" cellpadding="0" cellspacing="0"
           align="center">
        <caption><?= _('Mitarbeiterinnen und Mitarbeiter') ?></caption>
        <colgroup>
            <?
            foreach ($table_structure as $key => $field) {
                if ($key != 'statusgruppe') {
                    printf("<col width=\"%s\">", $field["width"]);
                }
            }
            ?>
        </colgroup>
        <thead>
        <tr>
            <?
            $begin = TRUE;
            foreach ($table_structure as $key => $field) {
                if ($begin) {
                    printf("<th width=\"%s\">", $field["width"]);
                    $begin = FALSE;
                } else
                    printf("<th width=\"%s\" align=\"left\" valign=\"bottom\" " . ($key == 'nachricht' ? 'colspan="2"' : '') . ">", $field["width"]);

                if ($field["link"]) {
                    printf("<a href=\"%s\">", URLHelper::getLink($field["link"]));
                    printf("<font size=\"-1\"><b>%s&nbsp;</b></font>\n", htmlReady($field["name"]));
                    echo "</a>\n";
                } else
                    printf("<font size=\"-1\" color=\"black\"><b>%s&nbsp;</b></font>\n", htmlReady($field["name"]));
                echo "</td>\n";
            }
            ?>
        </tr>
        </thead>
        <?= $table_content ?>
    </table>
<?
}
$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/person-sidebar.png');
$widget = new ViewsWidget();
$widget->addLink(_('Standard'), URLHelper::getURL('?extend=no'))->setActive($extend != 'yes');
$widget->addLink(_('Erweitert'), URLHelper::getURL('?extend=yes'))->setActive($extend == 'yes');
$sidebar->addWidget($widget);

if ($admin_view) {

    if (!LockRules::Check($inst_id, 'participants')) {

        $edit = new SidebarWidget();
        $edit->setTitle(_('Personenverwaltung'));
        $edit->addElement(new WidgetElement($mp));
        $sidebar->addWidget($edit);
    }


    if (!empty($mail_list)) {
        $actions = new ActionsWidget();
        $actions->addLink(_('MitarbeiterInnen-Rundmail'), "mailto:" . join(",", $mail_list) . "?subject=" . urlencode(_("Rundmail")), 'icons/16/blue/move_right/mail.png');
        $actions->addLink(_('Stud.IP Nachricht'), $controller->url_for('messages/write', array('inst_id' => $inst_id)), 'icons/16/blue/mail.png', array('data-dialog' => 'size=50%'));
        $sidebar->addWidget($actions);
    }
}


$widget = new OptionsWidget();
$widget->setTitle(_('Gruppierung'));
// Admins can choose between different grouping functions
if ($GLOBALS['perm']->have_perm("admin")) {
    $widget->addRadioButton(_('Funktion'),
        URLHelper::getLink('?show=funktion'),
        $show == 'funktion');
    $widget->addRadioButton(_('Status'),
        URLHelper::getLink('?show=status'),
        $show == 'status');
    $widget->addRadioButton(_('keine'),
        URLHelper::getLink('?show=liste'),
        $show == 'liste');
} else {
    $widget->addRadioButton(_('Nach Funktion gruppiert'),
        URLHelper::getLink('?show=funktion'),
        $show == 'funktion');
    $widget->addRadioButton(_('Alphabetische Liste'),
        URLHelper::getLink('?show=liste'),
        $show == 'liste');
}
$sidebar->addWidget($widget);
if (get_config('EXPORT_ENABLE') && $GLOBALS['perm']->have_perm('tutor')) {
    $widget = new ExportWidget();
    $widget->addElement(new WidgetElement(export_form_sidebar($auswahl, "person", $GLOBALS['SessSemName'][0])));
    $sidebar->addWidget($widget);
}