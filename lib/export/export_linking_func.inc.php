<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Export-functions to create links to the export-module.
* 
* In this file there are three functions which help to include the export-module into Stud.IP-pages. 
*
* @author       Arne Schroeder <schroeder@data.quest.de>
* @access       public
* @modulegroup      export_modules
* @module       export_linking_functions
* @package      Export
*/

use Studip\Button, Studip\LinkButton;

/**
* Generates a form that can be put into Stud.IP-pages to link to the export-module.
*
* This function returns a string with a HTML-form that links to the export-module. 
* It passes the given parameters in order to allow to jump to a specific part of the export-module.
*
* @access   public        
* @param        string  $range_id   export-range
* @param        string  $ex_type    type of data to be exported
* @param        string  $filename   filename for data-file
* @param        string  $format file-format for export
* @param        string  $filter grouping-category for export
* @return       string
*/
function export_form($range_id, $ex_type = "", $filename = "", $format = "", $filter = "")
{
    global $output_formats, $PATH_EXPORT, $xslt_filename;
    $filename = $xslt_filename;
    require_once ($PATH_EXPORT . "/export_xslt_vars.inc.php");
    $export_string .= "<form action=\"" . "export.php\" method=\"post\">";
    $export_string .= CSRFProtection::tokenTag();
    $export_string .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr><td class=\"steel1\"> &nbsp; &nbsp; &nbsp; ";

    $export_string .= "<font size=\"-1\"><b> "._("Diese Daten exportieren: ") .  "</b></font>";
    $export_string .= "</td><td align=\"center\" class=\"steel1\">";
    $export_string .= "<select name=\"format\">";
    while (list($key, $val) = each($output_formats))
    {
        $export_string .= "<option value=\"" . $key . "\"";
        if ($format==$key) $export_string .= " selected";
        $export_string .= ">" . $val;
    }
    $export_string .= "</select>";

    $export_string .= "</td><td align=\"right\" class=\"steel1\">";
    $export_string .= Button::create(_('Export'), 'export', array('title' => _('Diese Daten Exportieren')));

    $export_string .= "<input type=\"hidden\" name=\"range_id\" value=\"$range_id\">";
    $export_string .= "<input type=\"hidden\" name=\"o_mode\" value=\"choose\">";
    $export_string .= "<input type=\"hidden\" name=\"page\" value=\"1\">";
    $export_string .= "<input type=\"hidden\" name=\"ex_type\" value=\"$ex_type\">";
    $export_string .= "<input type=\"hidden\" name=\"filter\" value=\"$filter\">";
    $export_string .= "<input type=\"hidden\" name=\"xslt_filename\" value=\"$filename\">";
    $export_string .= "</td></tr></table>";
    $export_string .= "</form>";
    return $export_string;
}
    
/**
* Generates a link to the export-module that can be put into Stud.IP-pages.
*
* This function returns a string with a  link to the export-module. 
* It passes the given parameters in order to allow to jump to a specific part of the export-module.
*
* @access   public        
* @param        string  $range_id   export-range
* @param        string  $ex_type    type of data to be exported
* @param        string  $filename   filename for data-file
* @param        string  $format file-format for export
* @param        string  $choose xslt-Script for transformation
* @param        string  $filter grouping-category for export
* @return       string
*/
function export_link($range_id, $ex_type = "", $filename = "", $format = "", $choose = "", $filter = "", $content = "", $o_mode = 'processor')
{
    global $PATH_EXPORT, $xslt_filename, $i_page;

    $filename = preg_replace('/[\x7f-\x9f]/', '_', $filename);
    $export_string = "";
    if ($choose != "")
        $export_string .= "<a href=\"" . "export.php?range_id=$range_id&ex_type=$ex_type&xslt_filename=".rawurlencode($filename)."&format=$format&choose=$choose&o_mode=$o_mode&filter=$filter&jump=$i_page\">";
    elseif ($ex_type != "")
        $export_string .= "<a href=\"" . "export.php?range_id=$range_id&ex_type=$ex_type&xslt_filename=".rawurlencode($filename)."&o_mode=choose&filter=$filter\">";
    else
        $export_string .= "<a href=\"" . "export.php?range_id=$range_id&o_mode=start\">";
    $export_string .= ($content ? $content : _("Diese Daten exportieren"));
    $export_string .= "</a>";
    return $export_string;
}
    
/**
* Generates a Button with a link to the export-module that can be put into Stud.IP-pages.
*
* This function returns a string containing an export-button with a link to the export-module. 
* It passes the given parameters in order to allow to jump to a specific part of the export-module.
*
* @access   public        
* @param        string  $range_id   export-range
* @param        string  $ex_type    type of data to be exported
* @param        string  $filename   filename for data-file
* @param        string  $format file-format for export
* @param        string  $choose xslt-Script for transformation
* @param        string  $filter grouping-category for export
* @return       string
*/
function export_button($range_id, $ex_type = "", $filename = "", $format = "", $choose = "", $filter = "")
{
    global $PATH_EXPORT, $xslt_filename, $i_page;
    $filename = $xslt_filename;
    $filename = preg_replace('/[\x7f-\x9f]/', '_', $filename);
    if ($choose != "")
        $export_link .= "export.php?range_id=$range_id&ex_type=$ex_type&xslt_filename=$filename&format=$format&choose=$choose&o_mode=processor&filter=$filter&jump=$i_page";
    elseif ($ex_type != "")
        $export_link .= "export.php?range_id=$range_id&ex_type=$ex_type&xslt_filename=$filename&o_mode=choose&filter=$filter";
    else
        $export_link .= "export.php?range_id=$range_id&o_mode=start";
    $export_string .= LinButton::create(_('Export'), URLHelper::getURL($export_link));
    return $export_string;
}
    
?>
