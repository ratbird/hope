<?php
/**
 * ExportPDF.class.php - create and export or save a pdf with simple HTML-Data
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Rasmus Fuhse & Peter Thienel
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once "lib/classes/exportdocument/ExportDocument.interface.php";
require_once "vendor/tcpdf/tcpdf.php";

class ExportPDF extends TCPDF implements ExportDocument {

    private $media_proxy = NULL;
    private $config;
    private $defaults = false;
    private $page_added = false;
    private $h_title = '';
    private $h_string = '';
    private $domains;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = false, $encoding = 'ISO-8859-1')
    {
        $this->config = Config::GetInstance();
        if ($this->config->getValue('LOAD_EXTERNAL_MEDIA') == 'proxy') {
            $this->media_proxy = new MediaProxy();
        }
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, false);
        $this->getDomains();
        $this->setDefaults();
    }

    /**
     * Adding a new page to the document. This page can contain even more content
     * than for just one page. The pagebreak will be managed by tcpdf. But this function
     * will create a new pagebreak. Needs to be called at least once to addContent.
     * @param string $orientation
     * @param string $format
     * @param boolean $keepmargins
     * @param boolean $tocpage
     */
    public function addPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false)
    {
        $this->page_added = true;
        parent::AddPage($orientation, $format, $keepmargins, $tocpage);
    }

    /**
     * Adding Stud.IP formatted code to the current page of the pdf.
     * Remember to call addPage first.
     * @param string $content Stud.IP formatted code
     */
    public function addContent($content)
    {
        $content = formatReady($content, true, true, true, "all");
        $content = str_replace("<table", "<table border=\"1\"", $content);
        $this->writeHTML($content);
    }

    /**
     * Dispatches the PDF to the user and cancels all other output of Stud.IP.
     * @param string $filename name of the future file without the extension.
     */
    public function dispatch($filename)
    {
        $this->Output($filename.".pdf", 'I');
    }

    public function save($filename, $folder_id = null)
    {
        global $user;
        do {
            $id = uniqid();
        } while (file_exists(get_upload_file_path($id)));
        $contents = $this->Output(get_upload_file_path($id), "F");
        $db = DBManager::get();
        $seminar_id = $db->query(
            "SELECT seminar_id " .
            "FROM dokumente " .
            "WHERE range_id = ".$db->quote($folder_id)." " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $db->exec(
            "INSERT INTO dokumente " .
            "SET dokument_id = ".$db->quote($id).", " .
                "range_id = ".$db->quote($folder_id).", " .
                "user_id = ".$db->quote($user->id).", " .
                "seminar_id = ".$db->quote($seminar_id).", " .
                "name = ".$db->quote($filename).", " .
                "filename = ".$db->quote($filename.".pdf")." " .
                "mkdate = UNIX_TIMESTAMP(), " .
                "chdate = UNIX_TIMESTAMP(), ".
                "filesize = ".$db->quote(filesize(get_upload_file_path($id)))." " .
                "author_host = ".$db->quote(getenv('REMOTE_ADDR'))." " .
                "author_name = ".$db->quote(get_fullname($user->id))." "
        );
    }

    private function setDefaults ()
    {
        $this->defaults = true;

        // setting defaults
        $this->SetCreator('Stud.IP - ' . $this->config->getValue('UNI_NAME_CLEAN'));
        // set header and footer fonts
        $this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 8));
        $this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', 8));
        // set default monospaced font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        //set margins
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);
        //set auto page breaks
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        //set image scale factor
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // set default font subsetting mode
        $this->setFontSubsetting(true);
        // Set font
        //$this->SetFont('helvetica', '', 10, '', true);

        // set default page header
        $this->setHeaderData();

    }

    public function setHeaderTitle ($title)
    {
        $this->h_title = $title;
        $this->setHeaderData();
    }

    public function setHeaderSubtitle ($subtitle)
    {
        $this->h_string = $subtitle;
        $this->setHeaderData();
    }

    public function setHeaderData($ln = '', $lw = 0, $ht = '', $hs = '') {
        $logo_path = get_config("PDF_LOGO");
        if (!$ln) {
            $ln = $logo_path ? $logo_path : '../../../public/assets/images/logos/logoklein.gif';
        }
        $lw = 30;
        $ht = ($ht == '' ? $this->h_title : $ht);
        $hs = ($hs == '' ? $this->h_string : $hs);

        parent::setHeaderData($ln, $lw, $ht, $hs);
    }

    public function writeHTML ($html, $ln = true, $fill = false, $reseth = false, $cell = false, $align = '')
    {
        $html = preg_replace('/src="(.*)"/Ue', "\$this->convertURL('\\1')", $html);
        parent::writeHTML($html, $ln, $fill, $reseth, $cell, $align);
    }

    protected function convertURL($url)
    {
        $convurl = $url;
        $url_elements = @parse_url($url);
        $url = $url_elements['path'].'?'.$url_elements['query'];
        if (strpos(implode('#', $this->domains), $url_elements['host']) !== false) {
            if (strpos($url, 'dispatch.php/media_proxy?url=') !== false) {
                $targeturl = urldecode(substr($url, 4));
                try {
                    // is file in cache?
                    if (!$metadata = $this->media_proxy->getMetaData($targeturl)) {
                        $convurl = $targeturl;
                    } else {
                        $convurl = $this->config->getValue('MEDIA_CACHE_PATH') . '/' . md5($targeturl);
                    }
                } catch (Exception $e) {
                    $convurl = '';
                }
            } else if (stripos($url, 'download') !== false
                    || stripos($url, 'sendfile.php') !== false) {
                //// get file id
                $matches = array();
                if (preg_match('#([a-f0-9]{32})#', $url, $matches)) {
                    $document = new StudipDocument($matches[1]);
                    if ($document->checkAccess($GLOBALS['user']->id)) {
                        $convurl = get_upload_file_path($matches[1]);
                    } else {
                        $convurl = Assets::image_path("access_denied.png");
                    }
                }
            }
        }
        return 'src="' . $convurl . '"';
    }

    protected function getDomains()
    {
        $this->domains = array();
        $host_url_parsed = @parse_url($GLOBALS['ABSOLUTE_URI_STUDIP']);
        if (isset($GLOBALS['STUDIP_DOMAINS'])) {
            $this->domains = $GLOBALS['STUDIP_DOMAINS'];
        }
        $this->domains[] = $host_url_parsed['host'];
    }

}
