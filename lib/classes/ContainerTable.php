<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
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
        if (!@$styles["width"]) {
            $this->table_width="100%";
        }
        if (!@$styles["class"]) {
            $this->table_class="blank";
        }
        $this->row_class="blank";
        $this->cell_class="blank";
    }

    function headerRow($header, $styles=array()) {
        if (!@$styles["class"]) {
            $styles["class"]="table_header_bold";
        }
        if (!@$styles["colspan"]) {
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
