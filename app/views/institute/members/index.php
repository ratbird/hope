<? use Studip\Button, Studip\LinkButton; ?>
<?
if ($inst_id != '' && $inst_id != '0') {
    if ($admin_view) {
        ?>
        <table width="90%" border="0" cellpadding="2" cellspacing="0">
        <tr>
        <? 
        if (!LockRules::Check($inst_id, 'participants')) {
            ?>
            <td class="blank" width="50%" valign="top" align="center">
            <table width="90%" border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <th>
                        <font size="-1">
                            <b>&nbsp;<?=_("Neue MitarbeiterInnen hinzufügen")?></b>
                    </th>
                </tr>
                <tr>
                    <td class="table_row_even">
                        <font size="-1">
                            <br>
                            <?=sprintf(_("Klicken Sie auf %s, um neue MitarbeiterInnen hinzuzufügen."), $mp);?>
                        </font>
                    </td>
                </tr>
            </table>
            </td>
            <td class="blank" valign="top" width="50%" align="center">

            <? 
        } else {
            ?>
            <td colspan="2" class="blank" valign="top" style="padding-left:5px;">
            <?
        }
        ?>
            <!-- Mail an alle MitarbeiterInnen -->
            <table width="90%" border="0" cellpadding="2" cellspacing="0">
                <tr>
                    <th>
                        <font size="-1">
                            <b>&nbsp;<?=_("Nachricht an alle MitarbeiterInnen verschicken")?></b>
                        </font>
                    </th>
                </tr>
                <tr>
                    <td class="table_row_even">
                        <font size="-1">
                            <br>
                            <?=sprintf(_("Klicken Sie auf %s%s Rundmail an alle MitarbeiterInnen%s, um eine E-Mail an alle MitarbeiterInnen zu verschicken."), "<a href=\"mailto:" . join(",",$mail_list) . "?subject=" . urlencode(_("MitarbeiterInnen-Rundmail")) .  "\">",  '<img src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/blue/mail.png" border="0">', "</a>");?>
                        </font>
                    </td>
                </tr>
                <tr>
                    <td class="table_row_even">
                        <font size="-1">
                            <br>
                            <?=sprintf(_("Klicken Sie auf %s%s Stud.IP Nachricht an alle MitarbeiterInnen%s, um eine interne Nachricht an alle MitarbeiterInnen zu verschicken."),
                                "<a href=\"".URLHelper::getLink("sms_send.php?inst_id=$inst_id&subject=" . urlencode(_("MitarbeiterInnen-Rundmail - ". $SessSemName[0])))."\">",
                                '<img src="'.Assets::image_path('icons/16/blue/mail.png').'" border="0">',
                                "</a>"
                            );?>
                        </font>
                    </td>
                </tr>

            </table>
            </td>
        </tr>
        </table>
    <?
    }
    // add skip links
    SkipLinks::addIndex(_("Mitarbeiterliste"), 'list_institute_members');
    ?>
    <table class="default" id="list_institute_members" border="0" width="99%" cellpadding="0" cellspacing="0" align="center">
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
            printf ("<th width=\"%s\">", $field["width"]);
            $begin = FALSE;
        }
        else
            printf ("<th width=\"%s\" align=\"left\" valign=\"bottom\" ".($key == 'nachricht' ? 'colspan="2"':'').">", $field["width"]);

        if ($field["link"]) {
            printf("<a href=\"%s\">", URLHelper::getLink($field["link"]));
            printf("<font size=\"-1\"><b>%s&nbsp;</b></font>\n", htmlReady($field["name"]));
            echo "</a>\n";
        }
        else
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
$sidebar->setImage(Assets::image_path("sidebar/person-sidebar.png"));
$widget = new ViewsWidget();
$widget->addLink(_('Standard'), URLHelper::getURL('?extend=no'))->setActive($extend != 'yes');
$widget->addLink(_('Erweitert'), URLHelper::getURL('?extend=yes'))->setActive($extend == 'yes');
$sidebar->addWidget($widget);
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
$help_text = "Auf dieser Seite können Sie Personen der Einrichtung zuordnen." 
            ."Um weitere Personen als Mitarbeiter hinzuzufügen, benutzen Sie die Suche.";
if (get_config('EXPORT_ENABLE') && $GLOBALS['perm']->have_perm('tutor')) {
    $widget = new ExportWidget();
    $widget->addElement(new WidgetElement(export_form_sidebar($auswahl, "person", $GLOBALS['SessSemName'][0])));
    $sidebar->addWidget($widget);
}