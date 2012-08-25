<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
Table.class.php - HTML Table abstraction for Stud.IP
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

/**
* HTML table abstraction class.
*
* Encapsulates creation of html tables. Tables, rows and cells are created by 
* openXXX methods and closed by closeXXX methods. Closing of embedded elements
* can be omitted, i.e. openRow() closes the current row, if still open.
* Rows and cells have default attributes that can be set by corresponding
* setRowXXX and setCellXXX methods and are overridden by explicit parameters
* to single openRow and openCell calls.
*
* @access public
* @author Tobias Thelen <tthelen@uni-osnabrueck.de>
*
**/
class Table {

    /**
    * Constructor for a HTML table.
    * @param    array   List of attribute/value pairs for html styles.
    **/
    function Table($styles="") 
    {
        // properties for entire table
        $this->table_width=$styles["width"];
        $this->table_bgcolor=$styles["bgcolor"];
        $this->table_border=$styles["border"];
        $this->table_align=$styles["align"];
        $this->table_padding=$styles["padding"];
        $this->table_spacing=$styles["spacing"];
        $this->table_class=$styles["class"];
        $this->table_style=$styles["style"];
        $this->table_id=$styles["id"];
    
        // default properties for rows
        $this->row_bgcolor="";
        $this->row_align="";
        $this->row_valign="";
        $this->row_class="";
        $this->row_style="";
        $this->row_id="";
        $this->row_mouse_over="";
        $this->row_mouse_out="";

        // default properties for cells
        $this->cell_bgcolor="";
        $this->cell_align="";
        $this->cell_valign="";
        $this->cell_height="";
        $this->cell_width="";
        $this->cell_colspan="";
        $this->cell_nowrap="";
        $this->cell_class="";
        $this->cell_style="";
        $this->cell_id="";

        // state variables
        $this->tableopen=FALSE;
        $this->rowopen=FALSE;
        $this->cellopen=FALSE;
    }

    /**
    * Set Css class for table.
    * Only takes effect if table is not opened yet.
    * @param    string  Value for class attribute
    **/
    function setTableClass($class) 
    {
        $this->table_class=$class;
    }

    /**
    * Set CSS style for table.
    * Only takes effect if table is not opened yet.
    * @param    string  Value for style attribute
    **/
    function setTableStyle($style) 
    {
        $this->table_style=$style;
    }

    /**
    * Set CSS ID for table.
    * Only takes effect if table is not opened yet.
    * @param    string  Value for id attribute
    **/
    function setTableID($id) 
    {
        $this->table_id=$id;
    }

    /**
    * Set width attribute for table.
    * Only takes effect if table is not opened yet.
    * @param    string  Value for width attribute
    **/
    function setTableWidth($width)
    {
        $this->table_width=$width;
    }

    /**
    * Set border attribute for table.
    * Only takes effect if table is not opened yet.
    * @param    string  Value for border attribute
    **/
    function setTableBorder($border)
    {
        $this->table_border=$border;
    }

    /**
    * Set cellpadding attribute for table.
    * Only takes effect if table is not opened yet.
    * @param    string  Value for cellpadding attribute
    **/
    function setTableCellpadding($padding)
    {
        $this->table_padding=$padding;
    }

    /**
    * Set cellspacing attribute for table.
    * Only takes effect if table is not opened yet.
    * @param    string  Value for cellspacing attribute
    **/
    function setTableCellspacing($spacing)
    {
        $this->table_spacing=$spacing;
    }

    /**
    * Set align attribute for table.
    * Only takes effect if table is not opened yet.
    * @param    string  Value for align attribute
    **/
    function setTableAlign($align)
    {
        $this->table_align=$align;
    }

    /**
    * Set bgcolor attribute for table.
    * Only takes effect if table is not opened yet.
    * @param    string  Value for bgcolor attribute
    **/
    function setTableBgcolor($bgcolor)
    {
        $this->table_bgcolor=$bgcolor;
    }

    /**
    * Return HTML code opening the table.
    * Includes code for closing table if there's a table still open.
    * @param    array   Key/value pairs overriding default attributes for table
    **/
    function open($styles="")
    {
        $code="";
        if ($this->tableopen==TRUE) {
            $this->close();
        }
        $code .= "\n<table ";
        $width=$styles["width"] ? $styles["width"] : $this->table_width;
        $border=$styles["border"] ? $styles["border"] : $this->table_border;
        $align=$styles["align"] ? $styles["align"] : $this->table_align;
        $padding=$styles["padding"] ? $styles["padding"] : $this->table_padding;
        $spacing=$styles["spacing"] ? $styles["spacing"] : $this->table_spacing;
        $bgcolor=$styles["bgcolor"] ? $styles["bgcolor"] : $this->table_bgcolor;
        $class=$styles["class"] ? $styles["class"] : $this->table_class;
        $style=$styles["style"] ? $styles["style"] : $this->table_style;
        $id=$styles["id"] ? $styles["id"] : $this->table_id;
        if ($width) { $code .= "width=\"".$width."\" "; }
        if ($class) { $code .= "class=\"".$class."\" "; }
        if ($style) { $code .= "style=\"".$style."\" "; }
        if ($id) { $code .= "id=\"".$id."\" "; }
        if ($align) { $code .= "align=\"".$align."\" "; }
        if ($bgcolor) { $code .= "bgcolor=\"".$bgcolor."\" "; }
        if ($border) { 
            $code .= "border=\"".$border."\" ";
        } else {
            $code .= "border=\"0\" ";
        }
        if ($padding) {
            $code .= "cellpadding=\"".$padding."\" ";
        } else {
            $code .= "cellpadding=\"0\" ";
        }
        if ($spacing) {
            $code .= "cellspacing=\"".$spacing."\" ";
        } else {
            $code .= "cellspacing=\"0\" ";
        }
        $code .= ">\n";
        $this->rowopen=FALSE;
        $this->cellopen=FALSE;
        $this->tableopen=TRUE;
        return $code;
    }

    /**
    * Return HTML code for closing table.
    * Includes code for closing still open cells and rows
    **/
    function close()
    {
        $code = $this->closeRow();
        $code .= "</table>\n";
        $this->tableopen=FALSE;
        return $code;
    }

    function setRowBgcolor($bgcolor) 
    {
        $this->row_bgcolor=$bgcolor;
    }

    function setRowAlign($align) 
    {
        $this->row_align=$align;
    }

    function setRowVAlign($valign) 
    {
        $this->row_valign=$valign;
    }

    function setRowClass($class) 
    {
        $this->row_class=$class;
    }

    function setRowStyle($style) 
    {
        $this->row_style=$style;
    }

    function setRowID($id) 
    {
        $this->row_id=$id;
    }

    function openRow($styles="")
    {
        $code = "";
        if (!$this->tableopen) {
            $code .= $this->open();
        }
        if ($this->rowopen) {
            $code .= $this->closeRow();
        }
        $code .= "<tr ";
        $bgcolor= $styles["bgcolor"] ? $styles["bgcolor"] : $this->row_bgcolor;
        $align = $styles["align"] ? $styles["align"] : $this->row_align;
        $valign = $styles["valign"] ? $styles["valign"] : $this->row_valign;
        $class = $styles["class"] ? $styles["class"] : $this->row_class;
        $style = $styles["style"] ? $styles["style"] : $this->row_style;
        $mouseover = $styles["onMouseOver"] ? $styles["onMouseOver"] : $this->row_mouseover;
        $mouseout = $styles["onMouseOut"] ? $styles["onMouseOut"] : $this->row_mouseout;
        $id = $styles["id"] ? $styles["id"] : $this->row_id;
        if ($bgcolor) { $code .= "bgcolor=\"" . $bgcolor . "\" "; }
        if ($align) { $code .= "align=\"" . $align . "\" "; }
        if ($valign) { $code .= "valign=\"" . $valign . "\" "; }
        if ($class) { $code .= "class=\"" . $class . "\" "; }
        if ($style) { $code .= "style=\"" . $style . "\" "; }
        if ($mouseover) { $code .= "onMouseOver=" . $mouseover . " "; }
        if ($mouseout) { $code .= "onMouseOut=" . $mouseout . " "; }
        if ($id) { $code .= "id=\"" . $id . "\" "; }
        $code .= ">";
        $this->rowopen=TRUE;
        return $code;
    }

    function closeRow()
    {
        $code = "";
        if ($this->rowopen) {
            $code .= "</tr>\n";
            $this->rowopen=FALSE;
        }
        return $code;
    }

    function setCellBgcolor($bgcolor) 
    {
        $this->cell_bgcolor=$bgcolor;
    }

    function setCellAlign($align) 
    {
        $this->cell_align=$align;
    }

    function setCellVAlign($valign) 
    {
        $this->cell_valign=$valign;
    }

    function setCellHeight($height) 
    {
        $this->cell_height=$height;
    }

    function setCellWidth($width) 
    {
        $this->cell_width=$width;
    }

    function setCellColspan($colspan) 
    {
        $this->cell_colspan=$colspan;
    }

    function setCellClass($class) 
    {
        $this->cell_class=$class;
    }

    function setCellStyle($style) 
    {
        $this->cell_style=$style;
    }

    function setCellID($id) 
    {
        $this->cell_id=$id;
    }

    function setCellNowrap($nowrap) 
    {
        $this->cell_nowrap=$nowrap;
    }

    function openHeaderCell($styles="") 
    {
        $this->openCell($styles, "th");
    }

    function openCell($styles="", $tag="td")
    {
        $code = "";
        if (!$this->tableopen) {
            $code .= $this->open();
        }
        if (!$this->rowopen) {
            $code .= $this->openRow();
        }
        if ($this->cellopen) {
            $code .= $this->closeCell();
        }
        $code .= "<".$tag." ";
        $bgcolor= $styles["bgcolor"] ? $styles["bgcolor"] : $this->cell_bgcolor;
        $align = $styles["align"] ? $styles["align"] : $this->cell_align;
        $valign = $styles["valign"] ? $styles["valign"] : $this->cell_valign;
        $height = $styles["height"] ? $styles["height"] : $this->cell_height;
        $width = $styles["width"] ? $styles["width"] : $this->cell_width;
        $colspan = $styles["colspan"] ? $styles["colspan"] : $this->cell_colspan;
        $nowrap = $styles["nowrap"] ? $styles["nowrap"] : $this->cell_nowrap;
        $class = $styles["class"] ? $styles["class"] : $this->cell_class;
        $style = $styles["style"] ? $styles["style"] : $this->cell_style;
        $id = $styles["id"] ? $styles["id"] : $this->cell_id;

        if ($bgcolor) { $code .= "bgcolor=\"" . $bgcolor . "\" "; }
        if ($align) { $code .= "align=\"" . $align . "\" "; }
        if ($valign) { $code .= "valign=\"" . $valign . "\" "; }
        if ($width) { $code .= "width=\"" . $width . "\" "; }
        if ($height) { $code .= "height=\"" . $height . "\" "; }
        if ($colspan) { $code .= "colspan=\"" . $colspan . "\" "; }
        if ($nowrap) { $code .= "nowrap "; }
        if ($class) { $code .= "class=\"" . $class . "\" "; }
        if ($style) { $code .= "style=\"" . $style . "\" "; }
        if ($id) { $code .= "id=\"" . $id . "\" "; }

        $code .= ">";
        $this->cellopen=TRUE;
        $this->cellopentag=$tag;
        return $code;
    }

    function closeCell()
    {
        $code = "";
        if ($this->cellopen) {
            $code .= "</".$this->cellopentag.">";
            $this->cellopen=FALSE;
        }
        return $code;
    }

    function cell($content, $styles="")
    {
        $code = "";
        $code .= $this->openCell($styles);
        $code .= $content;
        $code .= $this->closeCell();
        return $code;
    }

    function headerCell($content, $styles="")
    {
        $code = "";
        $code .= $this->openHeaderCell($styles);
        $code .= $content;
        $code .= $this->closeCell();
        return $code;
    }

    function blankCell($styles="")
    {
        return $this->cell("&nbsp;",$styles);
    }

    function row($cells, $styles="") 
    {
        $code = "";
        $code .= $this->openRow($styles);
        foreach ($cells as $i) {
            $code .= $this->cell($i);
        }
        $code .= $this->closeRow();
        return $code;
    }

    function blankRow($styles="")
    {
        return $this->row(array("&nbsp;"),$styles);
    }
}
/**
* Table Class for entire area filled by single scipts
*
* The container table usually contains:
* - a 100%-width blank table
* - An header (class table_header_bold) with name of seminar, name of admin area etc.
* - The content area
* - a blank footer row
*
* For an example see ContentTable class
*
* @access public
* @author Tobias Thelen <tthelen@uni-osnabrueck.de>
*
**/
class ContainerTable extends Table {
    function ContainerTable($styles="") 
    {
        Table::Table($styles);
        if (! $styles["width"]) {
            $this->table_width="100%";
        }
        if (! $styles["class"]) {
            $this->table_class="blank";
        }
        $this->row_class="blank";
        $this->cell_class="blank";
    }

    function headerRow($header, $styles=array()) {
        if (!$styles["class"]) {
            $styles["class"]="table_header_bold";
        }
        if (!$styles["colspan"]) {
            $styles["colspan"]="2";
        }
        $code="";
        $code.=$this->openRow();
        $code.=$this->openCell($styles);
        $code.=$header;
        $code.=$this->closeRow();
        $code.=$this->blankRow();
        return $code;
    }
}

/**
* Table Class for content areas of Stud.IP pages
*
* The content area usually contains:
* - a 99%-width centred table with 2 columns
* - the content
* Content areas are normally part of a container table.
* 
* Example:
*
* $container=new ContainerTable();
* echo $container->headerRow("Name of the game...");
* echo $container->openCell();
* $content=new ContentTable();
* echo $content->open();
* ...the content...
* echo $content->close();
* echo $container->blankRow();
* echo $container->close();
*
* @access public
* @author Tobias Thelen <tthelen@uni-osnabrueck.de>
*
**/
class ContentTable extends Table {
    function ContentTable($styles="") 
    {
        Table::Table($styles);
        if (! $styles["width"]) {
            $this->table_width="99%";
        }
        if (! $styles["align"]) {
            $this->table_align="center";
        }
    }
}

?>
