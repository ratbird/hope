<?php
// <<-- EXPORT-SETTINGS
global $export_o_modes,
       $export_ex_types,
       $export_ex_types,
       $skip_page_3,
       $xml_filename,
       $xslt_filename_default,
       $output_formats,
       $export_icon;
// Export modes
$export_o_modes = array("start","file","choose", "direct","processor","passthrough");
// Exportable output-types
$export_ex_types = array("veranstaltung", "person", "forschung");

$skip_page_3 = true;
// name of generated XML-file
$xml_filename = "data.xml";
// name of generated output-file
$xslt_filename_default = "studip";

// existing output formats
$output_formats = array(
    "html"      =>      "Hypertext Markup Language (HTML)",
    "rtf"       =>      "Rich Text Format (RTF)",
    "txt"       =>      "Text (TXT)",
    "csv"       =>      "Comma Separated Values (Excel)",
    "fo"        =>      "Adobe Postscript (PDF)",
    "xml"       =>      "Extensible Markup Language (XML)"
);

// Icons für die Ausgabeformate
$export_icon["xml"] = "icons/16/blue/file-generic.png";
$export_icon["xslt"] = "icons/16/blue/file-office.png";
$export_icon["xsl"] = "icons/16/blue/file-office.png";
$export_icon["rtf"] = "icons/16/blue/file-text.png";
$export_icon["fo"] = "icons/16/blue/file-pdf.png";
$export_icon["pdf"] = "icons/16/blue/file-pdf.png";
$export_icon["html"] = "icons/16/blue/file-text.png";
$export_icon["htm"] = "icons/16/blue/file-text.png";
$export_icon["txt"] = "icons/16/blue/file-text.png";
$export_icon["csv"] = "icons/16/blue/file-office.png";
