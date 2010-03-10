<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*****************************************************************************
print a row in the common stud.ip printhead/content style
/*****************************************************************************/
class ShowTreeRow {

    function ShowRow($icon, $link, $titel, $zusatz, $level='', $lines='', $weitere, $new=FALSE, $open="close", $content=FALSE, $edit='', $breite="99%") {

        ?><table border=0 cellpadding=0 cellspacing=0 width="99%" align="center">
            <tr>
                <td class="blank" valign="top" heigth=21 nowrap><?

        if (!$content)
            $content=_("Keine Beschreibung");

        //Struktur darstellen
        $striche = "";
        for ($i=0;$i<$level;$i++) {
            if ($i==($level-1)) {
                if ($this->lines[$i+1]>1)
                    $striche.= "<img src=\"".$GLOBALS['ASSETS_URL']."images/forumstrich3.gif\" border=0>";      //Kreuzung
                else
                    $striche.= "<img src=\"".$GLOBALS['ASSETS_URL']."images/forumstrich2.gif\" border=0>";      //abknickend
                $this->lines[$i+1] -= 1;
            } else {
                if ($this->lines[$i+1]==0)
                    $striche .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\" border=0>";            //Leerzelle
                else
                    $striche .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/forumstrich.gif\" border=0>";      //Strich
            }
        }

        echo $striche;
                    ?></td>
                    <?

        //Kofzeile ausgeben
         printhead ($breite, 0, $link, $open, $new, $icon, $titel, $zusatz);
            ?>
            </tr>
        </table>
        <?

         //weiter zur Contentzeile
         if ($open=="open") {
        ?><table width="99%" cellpadding=0 cellspacing=0 border=0 align="center">
            <tr>
                <?
                //wiederum Striche fuer Struktur
                ?><td class="blank" nowrap background="<?= $GLOBALS['ASSETS_URL'] ?>images/forumleer.gif"></td>
                <?
                $striche='';
                if ($level)
                    for ($i=1;$i<=$level;$i++) {
                        if ($this->lines[$i]==0) {
                            $striche.= "<td class=\"blank\" nowrap background=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\"></td>";
                            }
                        else {
                            $striche.= "<td class=\"blank\" nowrap background=\"".$GLOBALS['ASSETS_URL']."images/forumstrich.gif\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer2.gif\"></td>";
                            }
                    }

                if ($weitere)
                    $striche.= "<td class=\"blank\" nowrap background=\"".$GLOBALS['ASSETS_URL']."images/forumstrichgrau.gif\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\"></td>";
                else
                    $striche.= "<td class=\"blank\" nowrap background=\"".$GLOBALS['ASSETS_URL']."images/steel1.jpg\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\"></td>";

                echo $striche;

                //Contenzeile ausgeben
                printcontent ($breite, FALSE, $content, $edit);
                ?>
            </tr>
        </table>
        <?
        }
    }
}
?>