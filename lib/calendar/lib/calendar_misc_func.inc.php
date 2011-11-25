<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter010: TODO

/**
 * calendar_misc_func.inc.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */

function cmp($a, $b)
{
    $start_a = date("Gi", $a->getStart());
    $start_b = date("Gi", $b->getStart());
    if ($start_a == $start_b)
        return 0;
    if ($start_a < $start_b)
        return -1;
    return 1;
}

function cmp_list($a, $b)
{
    $start_a = $a->getStart();
    $start_b = $b->getStart();
    if ($start_a == $start_b)
        return 0;
    if ($start_a < $start_b)
        return -1;
    return 1;
}

function print_js_export()
{
    echo "\n<script LANGUAGE=\"JavaScript\">
    <!-- Begin

    var exportproc=false;

    function export_end() {
      if (exportproc) {
        msg_window.close();
      }
      return;
    }

    function export_start() {
      msg_window=window.open(\"\",\"messagewindow\",\"height=250,width=200,left=20,top=20,scrollbars=no,resizable=no,toolbar=no\");
      msg_window.document.write(\"<html><head><title>" . _("Daten-Export") . "</title></head>\");
      msg_window.document.write(\"<body bgcolor='#ffffff'><center><p><img src='" . $GLOBALS['ASSETS_URL'] . "images/alienupload.gif' width='165' height='125'></p>\");
      msg_window.document.write(\"<p><font face='arial, helvetica, sans-serif'><b>&nbsp;";
    printf(_("Die Daten werden exportiert. %sBitte haben Sie etwas Geduld!"), "<br>&nbsp;");
    echo "<br></font></p></body></html>\");
      exportproc=true;
      return true;
    }
    // End -->
    </script>
    <body onUnLoad=\"export_end()\">";
}


function print_js_import () {
    //displays the templates for upload windows now
    //for upload code see application.js : STUDIP.OldUpload
    ?>
    <div id="upload_window_template" style="display: none">
        <?= htmlReady(
            "<html><head><title>Datei Upload</title></head>" .
            '<body bgcolor="#ffffff"><center><p><img src="'. $GLOBALS['ASSETS_URL'] .'images/alienupload.gif" width="165" height="125"></p>' .
            "<p><font face='arial, helvetica, sans-serif'><b>&nbsp;:file_only</b><br>&nbsp;"._("wird hochgeladen.") ."<br>&nbsp;" ._("Bitte haben Sie etwas Geduld!"). "<br></font></p></body></html>"
        ) ?>
    </div>
    <div id="upload_error_message_wrong_type" style="display: none;"><?= 
        _("Dieser Dateityp ist nicht zugelassen!")
    ?></div>
    <div id="upload_select_file_message" style="display: none;"><?= 
        _("Bitte wählen Sie eine Datei aus!")
    ?></div>
    <div id="upload_file_types" style="display: none;"><?= 
        json_encode(
            $UPLOAD_TYPES[$SessSemName["art_num"]]
            ? array(
                'allow' => $UPLOAD_TYPES[$SessSemName["art_num"]]["type"] === "allow" ? 0 : 1,
                'types' => $UPLOAD_TYPES[$SessSemName["art_num"]]["file_types"]
            )
            : array(
                'allow' => $UPLOAD_TYPES["default"]["type"] === "allow" ? 0 : 1,
                'types' => $UPLOAD_TYPES["default"]["file_types"]
            )
        );
    ?></div>
<?
}
?>