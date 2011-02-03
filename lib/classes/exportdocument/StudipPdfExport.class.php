<?php
/**
 * StudipPdfExport.class.php - PDF export functions
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>, Suchi & Berg Gmbh <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */


require_once dirname(__file__).'/tcpdf/tcpdf.php';
require_once 'app/models/media_proxy.php';

class StudipPdfExport extends TCPDF
{

    private $media_proxy = NULL;
    private $config;
    private $defaults = false;
    private $page_added = false;
    private $h_title = '';
    private $h_string = '';

    function __construct ($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = false, $encoding = 'ISO-8859-1')
    {
        $this->config = Config::GetInstance();
        if ($this->config->getValue('LOAD_EXTERNAL_MEDIA') == 'proxy') {
            $this->media_proxy = new MediaProxy();
        }
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, false);
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
        //$this->SetFont('liberationsans', '', 10, '', true);
        //$this->SetFont('helvetica', '', 10, '', true);

        // set default page header
        $this->setHeaderData();

        /* .. */
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
        $ln = ($ln == '' ? '../../../../../assets/images/logos/logoklein.gif' : $ln);
        $lw = 30;
        $ht = ($ht == '' ? $this->h_title : $ht);
        $hs = ($hs == '' ? $this->h_string : $hs);

        parent::setHeaderData($ln, $lw, $ht, $hs);
        //$this->header_logo = ($ln == '' ? $this->config->getValue('PDF_EXPORT_HEADER_LOGO') : $ln);
        //$this->header_logo = ($ln == '' ? '../../../../../assets/images/logos/logoklein.gif' : $ln);
        //$this->header_logo_width = ($lw == 0 ? $this->config->getValue('PDF_EXPORT_HEADER_LOGO_WIDTH') : $lw);
        //$this->header_logo_width = 30;
   //     $this->header_title = ($ht == '' ? $this->header_title : $ht);
   //     $this->header_string = ($hs == '' ? $this->header_string : $hs);
    }

    public function addPage ($orientation = '', $format = '', $keepmargins = false, $tocpage = false)
    {
        $this->page_added = true;
        parent::AddPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false);
    }

    public function writeHTML ($html, $ln = true, $fill = false, $reseth = false, $cell = false, $align = '')
    {
        if (!is_null($this->media_proxy)) {
            $html = preg_replace('/src="(.*)"/Ue', "\$this->convertURL('\\1')", $html);
        }
        parent::writeHTML($html, $ln, $fill, $reseth, $cell, $align);
    }

    public function export ($html, $filename)
    {
        if (!$this->defaults) {
            $this->setDefaults();
        }
        if (!$this->page_added) {
            $this->addPage();
        }

        $this->writeHTML($html);

        $this->Output($filename, 'I');
    }

    private function convertURL($url) {
        $convurl = $url;
        $url_elements = @parse_url($url);
        if (strpos($url_elements['path'].'?'.$url_elements['query'], 'dispatch.php/media_proxy?url=') !== false) {
            $targeturl = urldecode(substr($url_elements['query'], 4));
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
        }
        return 'src="' . $convurl . '"';
    }
}