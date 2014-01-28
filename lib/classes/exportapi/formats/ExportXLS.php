<?php

/**
 * exportxls - exporttype for xls
 *
 * Supports:
 * - export_newline
 * - export_table
 * - export_text
 * - export_timetable
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
class ExportXLS {

    private $worksheet;
    private $workbook;
    private $rowcount = 1;
    private $colcount = 0;

    /**
     * Routine starts
     */
    public function start() {
        require_once $GLOBALS['BASE_URI_STUDIP'] . 'vendor/PHPExcel/PHPExcel.php';
        $this->workbook = new PHPExcel();
        $this->worksheet = $this->workbook->getActiveSheet();
    }

    /**
     * Routine ends
     */
    public function finish() {
        $objWriter = PHPExcel_IOFactory::createWriter($this->workbook, 'Excel5');

        // We'll be outputting an excel file
        header('Content-type: application/vnd.ms-excel');

        // It will be called file.xls
        header('Content-Disposition: attachment; filename="' . $this->filename . '.xls"');

        // Write file to the browser
        $objWriter->save('php://output');
    }

    /**
     * Export of table element
     */
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

    /**
     * Export of text element
     */
    public function exportText($content) {
        $this->worksheet->setCellValueByColumnAndRow($this->colcount, $this->rowcount, utf8_encode($content->content));
        $this->rowcount++;
        $this->colcount = 0;
    }

    /**
     * Export of newline element
     */
    public function exportNewline($content) {
        $this->rowcount++;
        $this->colcount = 0;
    }

    /**
     * Export of timetable element
     */
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
            $this->worksheet->setCellValueByColumnAndRow($this->colcount, $this->rowcount, $content->header);
            $this->rowcount++;
            $this->colcount = 1;
            $style = $this->worksheet->getStyle('A1');
            $style->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            $style->getAlignment()->setWrapText(true);
        }

        //set border
        $style = $this->worksheet->getStyle('B' . (1 + $content->header) . ':' . (chr(65 + (count($content->day)))) . ($content->getTimes() + 2));
        $style->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_HAIR);

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
            $this->worksheet->getColumnDimension(chr(65 + $this->colcount))->setWidth((105 - $timewidth) / count($content->getDayAxis()));
            $this->colcount++;
        }

        //write entries
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
                    $style->getFill()->applyFromArray(
                            array(
                                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                'startcolor' => array('rgb' => 'F2F2F2'),
                            )
                    );
                    $text = html_entity_decode(utf8_encode($event['content']));
                    if ($content->rich) {
                        $tmp = new PHPExcel_RichText();
                        $textBold = $tmp->createTextRun($text);
                        $textBold->getFont()->setBold(true);
                        $textBold->getFont()->setSize(8);
                        $text = $tmp;
                    } else {
                        $style->getFont()->setSize(8);
                    }
                    $this->worksheet->setCellValueByColumnAndRow($this->colcount, $entryStartRow + $start, $text);
                    $merge = $endcol . ($entryStartRow + $start) . ':' . $endcol . ($entryStartRow + $event['end']);
                    $this->worksheet->mergeCells($merge);
                }
            }
        }

        $this->colcount = 0;
        $this->rowcount = $content->getTimes() + 3;

        //write footer
        if ($content->footer) {
            $this->worksheet->mergeCells('A' . $this->rowcount . ':' . chr(65 + $content->getDays() + 1) . $this->rowcount);
            //$this->worksheet->getRowDimension(1)->setRowHeight(60);
            $this->worksheet->setCellValueByColumnAndRow($this->colcount, $this->rowcount, $content->footer);
            /* $this->rowcount++;
              $this->colcount = 1; */
            $style = $this->worksheet->getStyle('A' . $this->rowcount . ':' . chr(65 + $content->getDays() + 1) . $this->rowcount);
            $style->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            $style->getAlignment()->setWrapText(true);
        }
    }

    /**
     * Export of missing element
     */
    public function exportMissing($type) {
        
    }

}

?>
