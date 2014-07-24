<table cellpadding="5" border="0" width="100%" id="main_content"><tr><td colspan="2">
            <?
            //
            if ($anzahl_seminare_class > 0) {
                print $GLOBALS['SEM_CLASS'][$_SESSION['sem_portal']["bereich"]]["description"]."<br>" ;
            } elseif ($_SESSION['sem_portal']["bereich"] != "all") {
                print "<br>"._("In dieser Kategorie sind keine Veranstaltungen angelegt.<br>Bitte w&auml;hlen Sie einen andere Kategorie!");
            }

            echo "</td></tr>";


            ?>

</table>

<? $sem_browse_obj->do_output() ?>

<?
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/seminar-sidebar.png"));

// add search options to sidebar
$widget = new OptionsWidget();
$widget->setTitle(_('Suchoptionen'));
$widget->addCheckbox(_('Erweiterte Suche anzeigen'),
                     $_SESSION['sem_browse_data']['cmd'] == "xts",
                     URLHelper::getLink('?cmd=xts&level=f'),
                     URLHelper::getLink('?cmd=qs&level=f'));

$sidebar->addWidget($widget);

if ($sem_browse_obj->show_result && count($_SESSION['sem_browse_data']['search_result'])){
    $actions = new ActionsWidget();
    $actions->addLink(_("Download des Ergebnisses"), URLHelper::getURL("dispatch.php/search/courses/export_results"), 'icons/16/blue/file-office.png');
    $sidebar->addWidget($actions);

    $group_by_links = "";
    $grouping = new OptionsWidget();
    $grouping->setTitle(_("Suchergebnis gruppieren:"));
    foreach ($sem_browse_obj->group_by_fields as $i => $field){
        $grouping->addRadioButton(
            $field['name'],
            URLHelper::getLink('?', array('group_by' => $i, 'keep_result_set' => 1)),
            $_SESSION['sem_browse_data']['group_by'] == $i
        );
    }
    $sidebar->addWidget($grouping);
} else {
    $toplist_names = array("dummy",_("Teilnehmeranzahl"), _("die meisten Materialien"), _("aktivste Veranstaltungen"),_("neueste Veranstaltungen"));
    $toplist = new LinksWidget();
    $toplist->setTitle(_("Topliste: ").$toplist_names[$_SESSION['sem_portal']["toplist"] ?: 4]);
    foreach ((array) $toplist_entries as $key => $entry) {
        $toplist->addLink(
            ($key + 1).". ".$entry['name'],
            URLHelper::getURL("dispatch.php/course/details/", array('sem_id' => $entry['seminar_id'],
                'cid' => null,
                'send_from_search' => 1,
                'send_from_search_page' => URLHelper::getUrl(basename($_SERVER['PHP_SELF']), array('cid' => null)))
            ),
            null
        );
    }

    $sidebar->addWidget($toplist);

    $toplist_switcher = new LinksWidget();
    $toplist_switcher->setTitle(_("Weitere Toplisten"));
    foreach (array(4,1,2,3) as $i) {
        $toplist_switcher->addLink(
            $toplist_names[$i],
            URLHelper::getURL("?", array('choose_toplist' => $i)),
            $_SESSION['sem_portal']["toplist"] == $i ? "icons/16/red/arr_1right" : null
        );
    }
    $sidebar->addWidget($toplist_switcher);
}
