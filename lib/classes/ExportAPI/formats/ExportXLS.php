<?php
/**
 * exportxls - exporttype for xls
 *
 * Supports:
 * - export_newline
 * - export_table
 * - export_text
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
class ExportXLS extends exportFormat {

    private $worksheet;
    private $workbook;
    private $rowcount = 1;
    private $colcount = 0;

    public function start() {
        require_once $GLOBALS['BASE_URI_STUDIP'].'vendor/PHPExcel/PHPExcel.php';
        $this->workbook = new PHPExcel();
        $this->worksheet = $this->workbook->getActiveSheet();
    }

    public function finish() {
        $objWriter = PHPExcel_IOFactory::createWriter($this->workbook, 'Excel5');

        for ($col = 'A'; $col != 'ZZZ'; $col++) {
            $this->workbook->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
        }
        // We'll be outputting an excel file
        header('Content-type: application/vnd.ms-excel');

        // It will be called file.xls
        header('Content-Disposition: attachment; filename="'.$this->filename.'.xls"');

        // Write file to the browser
        $objWriter->save('php://output');
    }

    public function exportTable($content) {
        $rowcount = &$this->rowcount;
        $colcount = &$this->colcount;

        //write header
        if ($content->getHeader() != null) {
            foreach ($content->getHeader() as $entry) {
                $this->worksheet->setCellValueByColumnAndRow($colcount, $rowcount, $entry);
                $colcount++;
            }
            $rowcount++;
            $colcount = 0;
        }

        //write entries
        foreach ($content->getContent() as $row) {
            foreach ($row as $entry) {
                $this->worksheet->setCellValueByColumnAndRow($colcount, $rowcount, $entry);
                $colcount++;
            }
            $rowcount++;
            $colcount = 0;
        }
    }

    public function exportText($content) {
        $this->worksheet->setCellValueByColumnAndRow($this->colcount, $this->rowcount, $content->content);
        $this->rowcount++;
        $this->colcount = 0;
    }

    public function exportNewline($content) {
        $this->rowcount++;
        $this->colcount = 0;
    }

    public function exportMissing($type) {
        
    }

}

?>
