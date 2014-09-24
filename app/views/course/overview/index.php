    <?
    echo "<h1>".htmlReady($GLOBALS['SessSemName']["header_line"]). "</h1>";
    if ($GLOBALS['SessSemName'][3]) {
        echo "<b>" . _("Untertitel:") . " </b>";
        echo htmlReady($GLOBALS['SessSemName'][3]);
        echo "<br>";
    }

    if (!$studygroup_mode) { ?>
        <b><?= _("Zeit / Veranstaltungsort") ?>:</b><br>
        <?
        $show_link = ($GLOBALS["perm"]->have_studip_perm('autor', $course_id) && $modules['schedule']);
        echo $sem->getDatesTemplate('dates/seminar_html', array('link_to_dates' => $show_link, 'show_room' => true));
        ?>

        <br>
        <br>

        <?
        $next_date = $sem->getNextDate();
        if ($next_date) {
            echo '<b>'._("Nächster Termin").':</b><br>';
            echo $next_date . '<br>';
        } else if ($first_date = $sem->getFirstDate()) {
            echo '<b>'._("Erster Termin").':</b><br>';
            echo $first_date . '<br>';
        } else {
            echo '<b>'._("Erster Termin").':</b><br>';
            echo _("Die Zeiten der Veranstaltung stehen nicht fest."). '<br>';
        }

    $dozenten = $sem->getMembers('dozent');
    $num_dozenten = count($dozenten);
    $show_dozenten = array();
    foreach($dozenten as $dozent) {
        $show_dozenten[] = '<a href="'.URLHelper::getLink("dispatch.php/profile?username=".$dozent['username']).'">'
                            . htmlready($num_dozenten > 10 ? get_fullname($dozent['user_id'], 'no_title_short') : $dozent['fullname'])
                            . '</a>';
    }
    printf("<br><b>%s: </b>%s", get_title_for_status('dozent', $num_dozenten), implode(', ', $show_dozenten));

    ?>
        <br>
        <br>
    <?
        // Ticket #68
        if (!$GLOBALS["perm"]->have_studip_perm('dozent', $course_id)) {
            $rule = AuxLockRules::getLockRuleBySemId($course_id);
            if (isset($rule)) {
                $show = false;
                foreach ((array)$rule['attributes'] as $val) {
                    if ($val == 1) {
                        // Es gibt also Zusatzangaben. Nun noch überprüfen ob der Nutzer diese Angaben schon gemacht hat...
                        $query = "SELECT 1
                                  FROM datafields
                                  LEFT JOIN datafields_entries USING (datafield_id)
                                  WHERE object_type = 'usersemdata' AND sec_range_id = ? AND range_id = ?";
                        $statement = DBManager::get()->prepare($query);
                        $statement->execute(array($course_id, $GLOBALS['user']->id));
                        if (!$statement->fetchColumn()) {
                            $show = true;
                        }
                        break;
                    }
                }

                if ($show) {
                    echo MessageBox::info(_("Sie haben noch nicht die für diese Veranstaltung benötigten Zusatzinformationen eingetragen."), array(
                        _('Um das nachzuholen, gehen Sie unter "TeilnehmerInnen" auf "Zusatzangaben"'),
                        _("oder") . ' <a href="' . URLHelper::getLink("dispatch.php/members/additional_input") . '"> ' . _("direkt zu den Zusatzangaben") . '</a>'
                    ));
                }
            }
        }
    } else {
        echo '<b>'._('Beschreibung:').' </b><br>'. formatLinks($sem->description) .'<br><br>';
        echo '<b>'._('Moderiert von:') .'</b> ';
        $all_mods = $sem->getMembers('dozent') + $sem->getMembers('tutor');
        $mods = array();
        foreach($all_mods as $mod) {
            $mods[] = '<a href="'.URLHelper::getLink("dispatch.php/profile?username=".$mod['username']).'">'.htmlready($mod['fullname']).'</a>';
        }
        echo implode(', ', $mods);
        echo '<br><br>';
    }
?>

<?php

// Anzeige von News
echo $news;

// Anzeige von Terminen
echo $dates;

// Anzeige von Umfragen
echo $votes;

// display plugins
$plugins = PluginEngine::getPlugins('StandardPlugin', $course_id);
$layout = $GLOBALS['template_factory']->open('shared/index_box');

foreach ($plugins as $plugin) {
    $template = $plugin->getInfoTemplate($course_id);

    if ($template) {
        echo $template->render(NULL, $layout);
        $layout->clear_attributes();
    }
}


$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/seminar-sidebar.png');
$links = new ActionsWidget();
$links->addLink(_("Druckansicht"),
    URLHelper::getScriptLink("dispatch.php/course/details/index/" . $course->id),
    'icons/16/blue/print.png',
    array('class' => 'print_action', 'target' => '_blank'));
$sidebar->addWidget($links);