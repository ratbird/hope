<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* Visual presentation of the Export-module.
*
* This file outputs the export-infobox, forms, messages and errors to the screen.
* The HTML-Design and table-Structure for export-pages are part of this file.
* It is used for any part of the export-module. If $o_mode is "direct" or "passthrough"
* it writes only the xml-stream or the output-file to the screen.
*
* @author       Arne Schroeder <schroeder@data.quest.de>
* @access       public
* @modulegroup      export_modules
* @module       export_view
* @package      Export
*/
if (($o_mode != "direct") AND ($o_mode != "passthrough"))
{
// Start of Output
    $CURRENT_PAGE = $export_pagename;
    Navigation::activateItem('/admin/tools/export');
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head
    if ($page == 1)
        echo "\n" . $cssSw->GetHoverJSFunction() . "\n";
    if ($page == 1)
        $cssSw->enableHover();

 ?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr valign="top">
            <td width="1%" class="blank">
            &nbsp;
            </td>
            <td class="blank">

            <table width="100%">
<?

                if (isset($export_error))
                    my_error($export_error);
                if (isset($export_msg))
                    my_msg($export_msg);
                if (isset($export_info))
                    my_info($export_info);
?>
            </table>
            <?
            echo $export_pagecontent;

            if (isset($xml_printlink))
            {
            ?>
            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <?
                    printhead ("99%", FALSE, "", "open", true, $xml_printimage, $xml_printlink, $xml_printdesc);
                    ?>
                </tr>
            </table>
            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <?
                    printcontent("99%", FALSE, $xml_printcontent, "");
                    ?>
                </tr>
            </table>
            <?
            }
            if (isset($xslt_printlink))
            {
            ?>
            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <?
                    printhead ("99%", FALSE, "", "open", true, $xslt_printimage, $xslt_printlink, $xslt_printdesc);
                    ?>
                </tr>
            </table>
            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <?
                    printcontent("99%", FALSE, $xslt_printcontent, "");
                    ?>
                </tr>
            </table>
            <?
            }
            if (isset($result_printlink))
            {
            ?>
            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <?
                    printhead ("99%", FALSE, "", "open", true, $result_printimage, $result_printlink, $result_printdesc);
                    ?>
                </tr>
            </table>
            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <?
                    printcontent("99%", FALSE, $result_printcontent, "");
                    ?>
                </tr>
            </table>
            <br>
            <?
            }
            if (isset($export_weiter_button))
            {
            ?>
            <br>
            <br>
            <?
                echo $export_weiter_button;
            }
            ?>
        </td>
        <td width="270" class="blank" align="center" valign="top">
            <?
            print_infobox ($infobox, "infoboxes/export.jpg");
            ?>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan="3">&nbsp;
        </td>
    </tr>
    </table>
<?php
    include ('lib/include/html_end.inc.php');
}
elseif ($export_error_num > 0)
{
    echo $export_error;
}
?>
