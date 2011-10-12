<?php
# Lifter010: TODO
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
require_once 'app/models/media_proxy.php';

/**
 * Class to create an PDF by putting in Stud.IP-formatted code.
 * Usage:
 *
 *  $doc = new ExportPDF();
 *  $doc->addPage();
 *  $doc->addContent('Hallo, %%wir%% benutzen :studip:-Formatierung.');
 *  $doc->dispatch("test_pdf");
 *  //lines following dispatch won't be accessed anymor, because dispatch
 *  //cancels all other output.
 *
 */
class ExportPDF extends TCPDF implements ExportDocument {

    private $media_proxy = NULL;
    private $config;
    private $defaults = false;
    private $page_added = false;
    private $h_title = '';
    private $h_string = '';
    private $domains;
    static protected $countEndnote = 0;

    /**
     * Create a basic document (without any content so far).
     * @param string $orientation page orientation. Possible values are (case insensitive):<ul><li>P or Portrait (default)</li><li>L or Landscape</li><li>'' (empty string) for automatic orientation</li></ul>
     * @param string $unit User measure unit. Possible values are:<ul><li>pt: point</li><li>mm: millimeter (default)</li><li>cm: centimeter</li><li>in: inch</li></ul><br />A point equals 1/72 of inch, that is to say about 0.35 mm (an inch being 2.54 cm). This is a very common unit in typography; font sizes are expressed in that unit.
     * @param mixed $format The format used for pages. It can be either: one of the string values specified at getPageSizeFromFormat() or an array of parameters specified at setPageFormat().
     * @param boolean $unicode TRUE means that the input text is unicode (default = false)
     * @param String $encoding charset encoding; default is ISO-8859-1
     */
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
     * @param string $orientation page orientation. Possible values are (case insensitive):<ul><li>P or Portrait (default)</li><li>L or Landscape</li><li>'' (empty string) for automatic orientation</li></ul>
     * @param mixed $format The format used for pages. It can be either: one of the string values specified at getPageSizeFromFormat() or an array of parameters specified at setPageFormat().
     * @param boolean $keepmargins if true overwrites the default page margins with the current margins
     * @param boolean $tocpage if true set the tocpage state to true (the added page will be used to display Table Of Content).
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
        preg_match_all("#\[comment(=.*)?\](.*)\[/comment\]#msU", $content, $matches);
        if (count($matches[0])) {
            $endnote .= "<br><br>"._("Kommentare")."<hr>";
            for ($i=0; $i < count($matches[0]); $i++) {
                $endnote .= ($i+1).") ".htmlReady(substr($matches[1][$i], 1)).": ".htmlReady($matches[2][$i])."<br>";
            }
        }
        $content = preg_replace("#\[comment(=.*)?\](.*)\[/comment\]#emsU", '$this->addEndnote("//1", "//2")', $content);
        $content = formatReady($content, true, true, true, null);
        $content = str_replace("<table", "<table border=\"1\"", $content);
        $this->writeHTML($content.$endnote);
    }

    /**
     *
     * @param <type> $commented_by
     * @param <type> $text
     * @return <type> 
     */
    public function addEndnote($commented_by, $text)
    {
        self::$countEndnote++;
        return ">>"._("Kommentar")." ".self::$countEndnote.">>";
    }

    /**
     * Dispatches the PDF to the user and cancels all other output of Stud.IP.
     * @param string $filename name of the future file without the extension.
     */
    public function dispatch($filename)
    {
        $this->Output($filename.".pdf", 'I');
    }

    /**
     * Saves the content as a file in the filesystem and returns a Stud.IP-document object.
     * @param string $filename name of the future file without the extension.
     * @param mixed $folder_id md5-id of a given folder in database or null for nothing
     * @return StudipDocument of the exported file or false if creation of StudipDocument failed.
     */
    public function save($filename, $folder_id = null)
    {
        global $user;
        $db = DBManager::get();
        $doc = new StudipDocument();
        $doc['folder_id'] = $folder_id;
        if ($folder_id) {
            $doc['seminar_id'] = $db->query(
                "SELECT range_id " .
                "FROM folder " .
                "WHERE folder_id = ".$db->quote($folder_id)." " .
            "")->fetch(PDO::FETCH_COLUMN, 0);
            $doc['range_id'] = $folder_id;
        }
        $doc['user_id'] = $user->id;
        $doc['name'] = $filename;
        $doc['filename'] = $filename.".pdf";
        $doc['description'] = "";
        $doc['author_host'] = getenv('REMOTE_ADDR');
        $doc['author_name'] = get_fullname($user->id);
        if ($doc->store()) {
            $path = get_upload_file_path($doc->getId());
            $this->Output($path, 'F');
            $doc['filesize'] = filesize($path);
            $doc->store();
            return $doc;
        }
        return false;
    }

    /**
     * Sets some default-values for the document, that tcpdf needs.
     */
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

    /**
     * Sets the title of the header of each page.
     * @param string $title title of the head
     */
    public function setHeaderTitle ($title)
    {
        $this->h_title = $title;
        $this->setHeaderData();
    }

    /**
     * Sets the subtitle of the header of each page.
     * @param string $subtitle subtitle of the head
     */
    public function setHeaderSubtitle ($subtitle)
    {
        $this->h_string = $subtitle;
        $this->setHeaderData();
    }

    /**
     * Creates a header for each page with a custom logo defined
     * @param string $ln header image logo
     * @param int $lw header image logo width in mm
     * @param string $ht string to print as title on document header
     * @param string $hs string to print on document header
     */
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

    /**
     * Overrides writeHTML-method of tcpdf to convert image-urls, so that they
     * aren't accessed via proxy but directly.
     * @param string $html text to display
     * @param boolean $ln if true add a new line after text (default = true)
     * @param boolean $fill Indicates if the background must be painted (true) or transparent (false).
     * @param boolean $reseth if true reset the last cell height (default false).
     * @param boolean $cell if true add the current left (or right for RTL) padding to each Write (default false).
     * @param string $align Allows to center or align the text. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left for LTR or right for RTL</li></ul>
     */
    public function writeHTML ($html, $ln = true, $fill = false, $reseth = false, $cell = false, $align = '')
    {
        $html = preg_replace('/src="(.*)"/Ue', "\$this->convertURL('\\1')", $html);
        parent::writeHTML($html, $ln, $fill, $reseth, $cell, $align);
    }

    /**
     * Converts URLs in images so that the webserver can access them without proxy.
     * @param string $url of an image
     * @return string " src=\"".$converted_url."\""
     */
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

    /**
     * finds an array with all domains of this Stud.IP and stores it in $this->domains
     */
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
