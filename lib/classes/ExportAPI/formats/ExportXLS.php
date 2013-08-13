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
        require_once $GLOBALS['BASE_URI_STUDIP'] . 'vendor/PHPExcel/PHPExcel.php';
        $this->workbook = new PHPExcel();
        $this->worksheet = $this->workbook->getActiveSheet();
    }

    public function finish() {
        $objWriter = PHPExcel_IOFactory::createWriter($this->workbook, 'Excel5');

        // We'll be outputting an excel file
        header('Content-type: application/vnd.ms-excel');

        // It will be called file.xls
        header('Content-Disposition: attachment; filename="' . $this->filename . '.xls"');

        // Write file to the browser
        $objWriter->save('php://output');
    }

    public function exportTable($content) {
        $rowcount = &$this->rowcount;
        $colcount = &$this->colcount;

        //write header
        if ($content->getHeader() != null) {
            foreach ($content->getHeader() as $entry) {
                $this->worksheet->setCellValueByColumnAndRow($colcount, $rowcount, utf8_encode($entry));
                $colcount++;
            }
            $rowcount++;
            $colcount = 0;
        }

        //write entries
        foreach ($content->getContent() as $row) {
            foreach ($row as $entry) {
                $this->worksheet->setCellValueByColumnAndRow($colcount, $rowcount, utf8_encode($entry));
                $colcount++;
            }
            $rowcount++;
            $colcount = 0;
        }
        for ($col = 'A'; $col != 'ZZZ'; $col++) {
            $this->workbook->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
            $this->worksheet->getPageSetup()->setFitToPage(true);
        }
    }

    public function exportText($content) {
        $this->worksheet->setCellValueByColumnAndRow($this->colcount, $this->rowcount, utf8_encode($content->content));
        $this->rowcount++;
        $this->colcount = 0;
    }

    public function exportNewline($content) {
        $this->rowcount++;
        $this->colcount = 0;
    }

    public function exportTimetable($content) {

        $this->worksheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        $endcol = "A";
        for ($i = 0; $i <= $content->getDays(); $i++) {
            $endcol++;
        }

        // set header
        if ($content->header) {
            $this->worksheet->mergeCells('A1:' . $endcol . '1');
            $this->worksheet->getRowDimension(1)->setRowHeight(60);
            $this->worksheet->setCellValueByColumnAndRow($this->colcount, $this->rowcount, utf8_encode($content->header));
            $this->rowcount++;
            $this->colcount = 1;
            $style = $this->worksheet->getStyle('A1');
            $style->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            $style->getAlignment()->setWrapText(true);
        }

        $rowAfterHeader = $this->rowcount;

        // set Timeline
        $this->rowcount++;
        $this->colcount = 0;
        $entryStartRow = $this->rowcount;
        foreach ($content->getTimeAxis() as $timeAxis) {
            $this->worksheet->setCellValueByColumnAndRow($this->colcount, $this->rowcount, $timeAxis);
            $this->rowcount++;
            $this->colcount = 0;
        }
        $this->workbook->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);

        // set Dayline
        $this->rowcount = $rowAfterHeader;
        $this->colcount = 1;
        foreach ($content->getDayAxis() as $dayAxis) {
            $this->worksheet->setCellValueByColumnAndRow($this->colcount, $this->rowcount, $dayAxis);
            $timewidth = $this->worksheet->getColumnDimension('A1')->getWidth();
            $this->worksheet->getColumnDimension(chr(65 + $this->colcount))->setWidth((100 - $timewidth) / count($content->getDayAxis()));
            $this->colcount++;
        }

        if ($content->day) {
            foreach ($content->day as $key => $day) {
                $endcol = "A";
                for ($i = 0; $i <= $key; $i++) {
                    $endcol++;
                }
                $this->colcount = $key + 1;
                foreach ($day as $start => $event) {
                    $stlye = $this->worksheet->getStyle($endcol . ($entryStartRow + $start));
                    $style->getAlignment()->setWrapText(true);
                    $stlye->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
                    $style->getFont()->setSize(6);
                    $this->worksheet->setCellValueByColumnAndRow($this->colcount, $entryStartRow + $start, utf8_encode($event['content']));
                    $merge = $endcol . ($entryStartRow + $start) . ':' . $endcol . ($entryStartRow + $event['end']);
                    $this->worksheet->mergeCells($merge);
                }
            }
        }
    }

    public function exportMissing($type) {
        
    }

}

?>
