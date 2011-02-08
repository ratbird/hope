<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* calendar_misc_func.inc.php
*
*
*
*
* @author       Peter Thienel <pthienel@web.de>
* @access       public
* @modulegroup  calendar
* @module       calendar
* @package  calendar_misc_func
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// calendar_misc_func.inc.php
//
// Copyright (C) 2003 Peter Thienel <pthienel@web.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+


function cmp ($a, $b) {
    $start_a = date("Gi", $a->getStart());
    $start_b = date("Gi", $b->getStart());
    if($start_a == $start_b)
        return 0;
    if($start_a < $start_b)
        return -1;
    return 1;
}

function cmp_list ($a, $b) {
    $start_a = $a->getStart();
    $start_b = $b->getStart();
    if($start_a == $start_b)
        return 0;
    if($start_a < $start_b)
        return -1;
    return 1;
}

function print_js_export () {
    echo "\n<script LANGUAGE=\"JavaScript\">
        <!-- Begin

        var exportproc=false;

        function export_end()
        {
            if (exportproc)
            {
                msg_window.close();
            }
            return;
        }

        function export_start()
        {
            msg_window=window.open(\"\",\"messagewindow\",\"height=250,width=200,left=20,top=20,scrollbars=no,resizable=no,toolbar=no\");
            msg_window.document.write(\"<html><head><title>" . _("Daten-Export") . "</title></head>\");
            msg_window.document.write(\"<body bgcolor='#ffffff'><center><p><img src='".$GLOBALS['ASSETS_URL']."images/alienupload.gif' width='165' height='125'></p>\");
            msg_window.document.write(\"<p><font face='arial, helvetica, sans-serif'><b>&nbsp;";
    printf(_("Die Daten werden exportiert. %sBitte haben Sie etwas Geduld!"),"<br>&nbsp;");
    echo "<br></font></p></body></html>\");
            exportproc=true;
            return true;
        }
        // End -->
        </script>
        <body onUnLoad=\"export_end()\">";
}


function print_js_import () {
    ?>
     <script type="text/javascript">
    <!-- Begin

    var upload=false;

    function upload_end()
    {
    if (upload)
        {
        msg_window.close();
        }
        return;
    }

    function upload_start(form_name)
    {
    file_name=form_name.importfile.value
    if (!file_name)
         {
         alert("<?=_("Bitte wählen Sie eine Datei aus!")?>");
         form_name.importfile.focus();
         return false;
         }

    if (file_name.charAt(file_name.length-1)=="\"") {
     ende=file_name.length-1; }
    else  {
     ende=file_name.length;  }

    ext=file_name.substring(file_name.lastIndexOf(".")+1,ende);
    ext=ext.toLowerCase();

    if (ext != "ics")
    {
      alert("<?=_("Dieser Dateityp ist nicht zugelassen!")?>");
      form_name.importfile.focus();

      return false;
    }

    if (file_name.lastIndexOf("/") > 0)
    {
      file_only=file_name.substring(file_name.lastIndexOf("/")+1,ende);
    }
    if (file_name.lastIndexOf("\\") > 0)
    {
      file_only=file_name.substring(file_name.lastIndexOf("\\")+1,ende);
    }

    msg_window=window.open("","messagewindow","height=250,width=200,left=20,top=20,scrollbars=no,resizable=no,toolbar=no");
    msg_window.document.write("<html><head><title>Datei Upload</title></head>");
    msg_window.document.write("<body bgcolor='#ffffff'><center><p><img src='".$GLOBALS['ASSETS_URL']."images/alienupload.gif' width='165' height='125'></p>");
    msg_window.document.write("<p><font face='arial, helvetica, sans-serif'><b>&nbsp;"+file_only+"</b><br>&nbsp;<?=_("wird hochgeladen.")?><br>&nbsp;<?=_("Bitte haben Sie etwas Geduld!")?><br></font></p></body></html>");

    upload=true;

    return true;
    }

    // End -->
    </script>
<?
}
?>
