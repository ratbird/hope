<?php
# Lifter002: TODO
# Lifter005: TODO - JS required to change background color on mouse hover; should  be done with CSS
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
ZebraTable.class.php - striped HTML Table for Stud.IP
Copyright (C) 2003 Tobias Thelen <tthelen@uni-osnabrueck.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

require_once("lib/classes/Table.class.php");

class ZebraTable extends Table {

    /**
    * Constructor for a HTML table.
    * @param    array   List of attribute/value pairs for html styles.
    **/
    function ZebraTable($styles="", $switcherClass = "", $headerClass = "",$hovercolor = "",$nohovercolor = "")
    {
        Table::Table($styles);

        $this->classcnt = 0;                //Counter
        $this->JSenabled = $GLOBALS["auth"]->auth["jscript"];
        $this->hoverenabled = FALSE;
        if (is_array($switcherClass)) {
            $this->switcherClass = $switcherClass;
        } else {
            $this->switcherClass = array("table_row_odd", "table_row_even");
        }
        if ($headerClass) {
            $this->header_class = $headerClass;
        } else {
            $this->header_class = "steel";
        }
        if (is_array($hovercolor)) {
            $this->hovercolor = $hovercolor;
        } else {
            $this->hovercolor = array("#B7C2E2","#CED8F2");
        }
        if (is_array($nohovercolor)) {
            $this->nohovercolor = $nohovercolor;
        } else {
            $this->nohovercolor = array("#E2E2E2","#F2F2F2");
        }
    }

    function enableHover($hovercolor = "",$nohovercolor = ""){
        if (is_array($hovercolor)) $this->hovercolor = $hovercolor;
        if (is_array($nohovercolor)) $this->nohovercolor = $nohovercolor;
        if ($this->JSenabled) $this->hoverenabled = TRUE;
        echo $this->GetHoverJSFunction();
    }

    function disableHover(){
        $this->hoverenabled = FALSE;
    }

    function getHover(){
        $ret=array();
        if($this->hoverenabled && $this->JSenabled){
            $ret["onMouseOver"]="'doHover(this,\"".$this->nohovercolor[$this->classcnt]."\",\"".$this->hovercolor[$this->classcnt]."\")'";
            $ret["onMouseOut"]="'doHover(this,\"".$this->hovercolor[$this->classcnt]."\",\"".$this->nohovercolor[$this->classcnt]."\")'";
        }
        return $ret;
    }

    function getFullClass(){
        $ret = ($this->hoverenabled) ?  " style=\"background-color:".$this->nohovercolor[$this->classcnt]."\" " : " class=\"" . $this->switcherClass[$this->classcnt] . "\" ";
        return $ret;
    }

    function getClass() {
        return ($this->hoverenabled) ? "\"  style=\"background-color:".$this->nohovercolor[$this->classcnt]." " : $this->class[$this->classcnt];
    }

    function getHeaderClass() {
        return $this->headerClass;
    }

    function resetClass() {
        return $this->classcnt = 0;
    }

    function switchClass() {
        $this->classcnt++;
        if ($this->classcnt >= sizeof($this->switcherClass))
            $this->classcnt = 0;
    }

    function GetHoverJSFunction()
    {
        static $is_called = FALSE;
        $ret = "";
        if($GLOBALS["auth"]->auth["jscript"] && !$is_called) {
            $ret = "<script type=\"text/javascript\">
                    function convert(x, n, m, d){
                        if (x == 0) return '00';
                        var r = '';
                        while (x != 0){
                            r = d.charAt((x & m)) + r;
                            x = x >>> n
                        }
                        return (r.length%2) ? '0' + r : r;
                    }

                    function toHexString(x){
                        return convert(x, 4, 15, '0123456789abcdef');
                    }

                    function rgbToHex(rgb_str){
                        var ret = '#';
                        var rgb_arr = rgb_str.substring(rgb_str.indexOf('(')+1,rgb_str.lastIndexOf(')')).split(',');
                        for(var i = 0; i < rgb_arr.length; ++i){
                            ret += toHexString(rgb_arr[i]);
                        }
                        return ret;
                    }

                    function doHover(theRow, theFromColor, theToColor){
                        if (theFromColor == '' || theToColor == '') {
                            return false;
                        }
                        if (document.getElementsByTagName) {
                            var theCells = theRow.getElementsByTagName('td');
                        }
                        else if (theRow.cells) {
                            var theCells = theRow.cells;
                        } else {
                            return false;
                        }
                        if (theRow.tagName.toLowerCase() != 'tr'){
                            if ((theRow.style.backgroundColor.toLowerCase() == theFromColor.toLowerCase()) || (rgbToHex(theRow.style.backgroundColor) == theFromColor.toLowerCase())) {
                                theRow.style.backgroundColor = theToColor;
                            }
                        } else {
                            var rowCellsCnt  = theCells.length;
                            for (var c = 0; c < rowCellsCnt; c++) {
                                if ((theCells[c].style.backgroundColor == theFromColor.toLowerCase()) || (rgbToHex(theCells[c].style.backgroundColor) == theFromColor.toLowerCase())) {
                                    theCells[c].style.backgroundColor = theToColor;
                                }
                            }
                        }
                        return true;
                    }
                    </script>";
        }
        $is_called = TRUE;
        return $ret;
    }

    function openHeaderRow($styles="")
    {
        $this->in_header_row = 1;
        $this->safed_cell_class = $this->cell_class;
        $this->cell_class = $this->header_class;
        return $this->openRow($styles, 0);
    }

    function headerRow($cells, $styles="") {
        $code = "";
        $code .= $this->openHeaderRow($styles);
        foreach ($cells as $i) {
            $code .= $this->cell($i);
        }
        $code .= $this->closeRow();
        return $code;
    }

    function openRow($styles="", $do_switch=1)
    {
        if (!is_array($styles)) {
            $styles=array();
        }
        if ($do_switch) {
            $this->switchClass();
        }
        if (!$styles["class"]) {
            $styles["class"]=$this->switcherClass[$this->classcnt];
        }
        $s = array_merge((array)$styles, (array)$this->getHover());
        return Table::openRow($s);
    }

    function row($cells, $styles="", $do_switch=1)
    {
        $code = "";
        $code .= $this->openRow($styles, $do_switch);
        foreach ($cells as $i) {
            $code .= $this->cell($i);
        }
        $code .= $this->closeRow();
        return $code;
    }

    function closeRow($styles="")
    {
        if ($this->in_header_row) {
            $this->cell_class = $this->safed_cell_class;
        }
        return Table::closeRow($styles);
    }


    function blankRow($do_switch=1) {
        return $this->row(array("&nbsp;"), $do_switch);
    }

}
?>
