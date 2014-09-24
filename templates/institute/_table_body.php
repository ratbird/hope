<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

    if ($th_title) {
        ?>
        <tr><th colspan="<?=($mail_gruppe || $mail_status) ? $group_colspan : $colspan ?>" height="20">
        <font size="-1"><b>&nbsp;
        <?= htmlReady($th_title); ?>
        </b></font></th>
        <? 
        if ($mail_gruppe) { 
            ?>
            <th colspan="2" height="20">
                <a href="<?= URLHelper::getLink("dispatch.php/messages/write?sms_source_page=dispatch.php/institute/members&filter=inst_status&who=".$key . "&group_id=" .$role_id."&subject=".rawurlencode($GLOBALS['SessSemName'][0])) ?>">
                    <?= Assets::img('icons/16/blue/mail.png',
                                    tooltip2(sprintf(_('Nachricht an alle Mitglieder mit dem Status %s verschicken'),
                                                     $th_title))) ?>
                </a>
            </th>
            <? 
        } 
        elseif ($mail_status) { 
            ?>
            <th colspan="2" height="20">
                <a href="<?= URLHelper::getLink("dispatch.php/messages/write?sms_source_page=dispatch.php/institute/members&group_id=".$role_id."&subject=".rawurlencode($GLOBALS['SessSemName'][0])) ?>">
                    <?= Assets::img('icons/16/blue/mail.png',
                                    tooltip2(sprintf(_('Nachricht an alle Mitglieder der Gruppe %s verschicken'),
                                                     $th_title))) ?>
                </a>
            </th>
            <? 
        } 
        ?> 
        </tr>
        <?
    }
    $cells = sizeof($structure);

    foreach ($members as $member) {

        $pre_cells = 0;

        $default_entries = DataFieldEntry::getDataFieldEntries(array($member['user_id'], $range_id));

        if ($member['statusgruppe_id']) {
            $role_entries = DataFieldEntry::getDataFieldEntries(array($member['user_id'], $member['statusgruppe_id']));
        }

        print "<tr>\n";
        if ($member['fullname']) {
            print "<td>";
            echo Assets::img('blank.gif', array('size' => '2@1'));
            echo '<font size="-1">';
            if ($admin_view) {
                printf("<a href=\"%s\">%s</a>\n",
                URLHelper::getLink("dispatch.php/settings/statusgruppen?username={$member['username']}&open={$range_id}#{$range_id}"), htmlReady($member['fullname']));
            } else {
                echo '<a href="'.URLHelper::getLink('dispatch.php/profile?username='.$member['username']).'">'. htmlReady($member['fullname']) .'</a>';
            }
            echo '</font></td>';
        }
        else
            print "<td>&nbsp;</td>";

        if ($structure["status"]) {
            if ($member['inst_perms']) {
                printf("<td align=\"left\"><font size=\"-1\">%s</font></td>\n",
                    htmlReady($member['inst_perms']));
            } else { // It is actually impossible !
                print "<td align=\"left\"><font size=\"-1\">&nbsp;</font></td>\n";
            }
            $pre_cells++;
        }

        if ($structure["statusgruppe"]) {
            print "<td align=\"left\"><font size=\"-1\">&nbsp;</font></td>\n";
        }

        foreach ($datafields_list as $entry) {
            if ($structure[$entry->getId()]) {
                $value = '';
                if ($role_entries[$entry->getId()]) {
                    if ($role_entries[$entry->getId()]->getValue() == 'default_value') {
                        $value = $default_entries[$entry->getId()]->getDisplayValue();
                    } else {
                        $value = $role_entries[$entry->getId()]->getDisplayValue();
                    }
                } else {
                    if ($default_entries[$entry->getId()]) {
                        $value = $default_entries[$entry->getId()]->getDisplayValue();
                    }
                }

                printf("<td align=\"left\"><font size=\"-1\">%s</font></td>\n", $value);
            }
        }

        if (sizeof($dview) == 0) {
            if ($structure['raum']) echo '<td>'. htmlReady($member['raum']) .'</td>';
            if ($structure['sprechzeiten']) echo '<td>'. htmlReady($member['sprechzeiten']) .'</td>';
            if ($structure['telefon']) echo '<td>'. htmlReady($member['Telefon']) .'</td>';
            if ($structure['email']) echo '<td>'. htmlReady($member['Email']) .'</td>';
            if ($structure['homepage']) echo '<td>'. htmlReady($member['Home']) .'</td>';
        }

        if ($structure["nachricht"]) {
            print "<td align=\"left\" width=\"1%%\"".(($admin_view) ? "" : " colspan=\"2\""). " nowrap>\n";
            printf("<a href=\"%s\">", URLHelper::getLink("dispatch.php/messages/write?rec_uname=".$member['username']));
            print Assets::img('icons/16/blue/mail.png', tooltip2(_('Nachricht an Benutzer verschicken')) + array('valign' => 'baseline'));
            print '</a>';
            print '</td>';

            if ($admin_view && !LockRules::Check($range_id, 'participants')) {
                echo '<td width="1%" nowrap>';
                if ($member['statusgruppe_id']) {    // if we are in a view grouping by statusgroups
                    echo '&nbsp;<a href="'.URLHelper::getLink('?cmd=removeFromGroup&username='.$member['username'].'&role_id='. $member['statusgruppe_id']).'">';
                } else {
                    echo '&nbsp;<a href="'.URLHelper::getLink('?cmd=removeFromInstitute&username='.$member['username']).'">';
                }
                echo Assets::img('icons/16/blue/trash.png', array('class' => 'text-top'));
                echo "</a>&nbsp\n</td>\n";
            }
        }

        echo "</tr>\n";

        // Statusgruppen kommen in neue Zeilen
        if ($structure["statusgruppe"]) {
            $statusgruppen = GetStatusgruppenForUser($member['user_id'], array_keys((array)$group_list));
            if (is_array($statusgruppen)) {
                foreach ($statusgruppen as $id) {
                    $entries = DataFieldEntry::getDataFieldEntries(array($member['user_id'], $id));

                    echo '<tr>';
                    for ($i = 0; $i <= $pre_cells; $i++) {
                        echo '<td>&nbsp;</td>';
                    }

                    echo '<td><font size="-1">';

                    if ($admin_view) {
                        echo '<a href="'.URLHelper::getLink('admin_statusgruppe.php?role_id='.$id.'&cmd=displayRole').'">'.htmlReady($group_list[$id]).'</a>';
                    } else {
                        echo htmlReady($group_list[$id]);
                    }

                    echo '</font></td>';
                    if (sizeof($entries) > 0) {
                        foreach ($entries as $e_id => $entry) {
                            if (in_array($e_id, $dview) === TRUE) {
                                echo '<td><font size="-1">';
                                if ($entry->getValue() == 'default_value') {
                                    echo $default_entries[$e_id]->getDisplayValue();
                                } else {
                                    echo $entry->getDisplayValue();
                                }
                                echo '</font></td>';
                            }
                        }
                    } else {
                        for ($i = 0; $i < sizeof($struct); $i++) {
                            echo '<td>&nbsp;</td>';
                        }
                    }
                    if ($admin_view && !LockRules::Check($range_id, 'participants')) {
                        echo '<td>';
                        echo '<a href="'.URLHelper::getLink('dispatch.php/settings/statusgruppen/switch/' . $id . '?username='.$member['username']).'"><font size="-1">';
                        echo Assets::img('icons/16/blue/edit.png');
                        echo '</font></a></td>';

                        echo '<td>';
                        echo '&nbsp;<a href="'.URLHelper::getLink('?cmd=removeFromGroup&username='.$member['username'].'&role_id='.$id).'">';
                        echo Assets::img('icons/16/blue/trash.png', array('class' => 'text-top'));
                        echo '</a>&nbsp</td>';
                    }
                    elseif ($structure["nachricht"]) {
                        echo '<td colspan=\"2\">&nbsp;</td>';
                    }
                    echo '</tr>', "\n";
                }
            }
        }
    }