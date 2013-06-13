<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Format_XLS
 *
 * @author flo
 */
class Export_XLS extends exportFormat {

    private $worksheet;
    private $workbook;
    private $rowcount;
    private $colcount;

    public function start() {
        ini_set('display_errors',1); 
 error_reporting(E_ALL);
        require 'Spreadsheet/Excel/Writer.php';
        $this->workbook = new Spreadsheet_Excel_Writer();
        $this->workbook->send("$this->filename.xls");
        $this->worksheet = & $this->workbook->addWorksheet($filename);
    }

    public function finish() {
        $this->workbook->close();
    }

    public function export_table($content) {
        $rowcount = &$this->rowcount;
        $colcount = &$this->colcount;

        //write header
        if ($content->getHeader() != null) {
            foreach ($content->getHeader() as $entry) {
                $this->worksheet->write($rowcount, $colcount, $entry);
                $colcount++;
            }
            $rowcount++;
            $colcount = 0;
        }

        //write entries
        foreach ($content->getContent() as $row) {
            foreach ($row as $entry) {
                $this->worksheet->write($rowcount, $colcount, $entry);
                $colcount++;
            }
            $rowcount++;
            $colcount = 0;
        }
    }

    public function export_text($content) {
        $this->worksheet->write($this->rowcount, $this->colcount, $content->content);
        $this->rowcount++;
        $this->colcount = 0;
    }

    public function export_newline($content) {
        $this->rowcount++;
        $this->colcount = 0;
    }

    public function export_missing($type) {
        $this->worksheet->write($this->rowcount, $this->colcount, "Export von $type wird nicht unterstützt!");
        $this->rowcount++;
        $this->colcount = 0;
    }

}

?>
