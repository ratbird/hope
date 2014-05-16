    <?
    echo "<h2>".htmlReady($GLOBALS['SessSemName']["header_line"]). "</h2>";
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
                        $statement->execute(array($course_id, $user->id));
                        if (!$statement->fetchColumn()) {
                            $show = true;
                        }
                        break;
                    }
                }

                if ($show) {
                    echo MessageBox::info(_("Sie haben noch nicht die für diese Veranstaltung benötigten Zusatzinformationen eingetragen."), array(
                        _('Um das nachzuholen, gehen Sie unter "TeilnehmerInnen" auf "Zusatzangaben"'),
                        _("oder") . ' <a href="' . URLHelper::getLink("teilnehmer_aux.php") . '"> ' . _("direkt zu den Zusatzangaben") . '</a>'
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
    }
?>

<?php

// Anzeige von News
show_news($course_id, $rechte, 0, $smain_data["nopen"], "100%", object_get_visit($course_id, "sem"), $smain_data);

// Anzeige von Terminen
$start_zeit=time();
$end_zeit=$start_zeit+1210000;
$show_admin = false;
if (!$studygroup_mode) {
    if ($rechte) {
        $show_admin = URLHelper::getLink("admin_dates.php?range_id=".$course_id."&ebene=sem&new_sem=TRUE");
        PageLayout::addSqueezePackage('raumzeit');
        PageLayout::addHeadElement('script', array(), "
        jQuery(function () {
            STUDIP.CancelDatesDialog.reloadUrlOnClose = '" . URLHelper::getUrl() ."';
        });");
    }
    show_dates($start_zeit, $end_zeit, $smain_data["dopen"], $course_id, 0, TRUE, $show_admin);
}

// include and show votes and tests
if (get_config('VOTE_ENABLE')) {
    show_votes($course_id, $GLOBALS["auth"]->auth["uid"], $GLOBALS["perm"], YES);
}

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
$sidebar->setImage(Assets::image_path("sidebar/seminar-sidebar.png"));
