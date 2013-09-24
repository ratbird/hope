<?
/**
 * Somehow there should be only a single page for institute selection.
 * Not sure how trails could handle that.
 * As soon as all institute pages use that single page we should rework it
 */
?>
<form name="links_admin_search" action="<?= URLHelper::getLink() ?>" method="POST">
    <?= CSRFProtection::tokenTag() ?>
    <table cellpadding="0" cellspacing="0" border="0" width="99%" align="center">
        <tr>
            <td class="table_row_even">
                <br>
                <b><?= _("Bitte wählen Sie die Einrichtung aus, die Sie bearbeiten wollen:") ?></b><br>
                <br>
            </td>
        </tr>
        <tr>
            <td class="table_row_even">
                <select name="admin_inst_id" size="1" style="vertical-align:middle">
                    <?
                    $dbparams = array();
                    if ($GLOBALS['perm']->have_perm("root")) {
                        $dbquery = "SELECT Institut_id, Name, 1 AS is_fak  FROM Institute WHERE Institut_id=fakultaets_id ORDER BY Name";
                    } elseif ($GLOBALS['perm']->have_perm("admin")) {
                        $dbquery = "SELECT a.Institut_id,Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak FROM user_inst a LEFT JOIN Institute b USING (Institut_id)
                                        WHERE a.user_id='$user->id' AND a.inst_perms='admin' ORDER BY is_fak,Name";
                    } else {
                        $dbquery = "SELECT a.Institut_id,Name FROM user_inst a LEFT JOIN Institute b USING (Institut_id) WHERE inst_perms IN('tutor','dozent') AND user_id = ? ORDER BY Name";
                        $dbparams = array($user->id);
                    }
                    $dbstatement = DBManager::get()->prepare($dbquery);
                    $dbstatement->execute($dbparams);

                    printf("<option value=\"NULL\">%s</option>\n", _("-- bitte Einrichtung auswählen --"));
                    while ($dbrow = $dbstatement->fetch(PDO::FETCH_ASSOC)) {
                        printf("<option value=\"%s\" style=\"%s\">%s </option>\n", $dbrow['Institut_id'], ($dbrow['is_fak'] ? "font-weight:bold;" : ""), htmlReady(substr($dbrow['Name'], 0, 70)));
                        if ($dbrow['is_fak']) {
                            $db2query = "SELECT Institut_id, Name FROM Institute WHERE fakultaets_id='" . $dbrow['Institut_id'] . "' AND institut_id!='" . $dbrow['Institut_id'] . "' ORDER BY Name";
                            $db2statement = DBManager::get()->prepare($db2query);
                            $db2statement->execute();
                            while ($db2row = $db2statement->fetch(PDO::FETCH_ASSOC)) {
                                printf("<option value=\"%s\">&nbsp;&nbsp;&nbsp;&nbsp;%s </option>\n", $db2row['Institut_id'], htmlReady(substr($db2row['Name'], 0, 70)));
                            }
                        }
                    }
                    ?>
                </select>
                <?= Studip\Button::create(_('Einrichtung auswählen')) ?>
            </td>
        </tr>
        <tr>
            <td class="table_row_even">&nbsp;

            </td>
        </tr>
        <tr>
            <td class="blank">&nbsp;

            </td>
        </tr>


    </table>
</form>